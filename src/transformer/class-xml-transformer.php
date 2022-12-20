<?php
/**
 * XML_Transformer class file
 *
 * @package feed-transformer
 */

namespace Feed_Consumer\Transformer;

use Alley\WP\Block_Converter\Block_Converter;
use Feed_Consumer\Contracts\With_Presets;
use Feed_Consumer\Contracts\With_Setting_Fields;
use Feed_Consumer\Loader\Post_Loader;
use Fieldmanager_TextField;
use SimpleXMLElement;

/**
 * XML Transformer
 *
 * Transform the extracted content into an array of items by their XPath.
 * Designed for feeds of content to be converted into multiple posts.
 */
class XML_Transformer extends Transformer implements With_Setting_Fields {
	/**
	 * XPath key to the items.
	 *
	 * @var string
	 */
	public const PATH_ITEMS = 'path_items';

	/**
	 * XPath key to the item guid.
	 *
	 * @var string
	 */
	public const PATH_GUID = 'path_guid';

	/**
	 * XPath key to the item title.
	 *
	 * @var string
	 */
	public const PATH_TITLE = 'path_title';

	/**
	 * XPath key to the item link.
	 *
	 * @var string
	 */
	public const PATH_PERMALINK = 'path_permalink';

	/**
	 * XPath key to the item content.
	 *
	 * @var string
	 */
	public const PATH_CONTENT = 'path_content';

	/**
	 * XPath key to the item byline.
	 *
	 * @var string
	 */
	public const PATH_BYLINE = 'path_byline';

	/**
	 * XPath key to the item image URL.
	 *
	 * @var string
	 */
	public const PATH_IMAGE = 'path_image';

	/**
	 * XPath key to the item image description.
	 *
	 * @var string
	 */
	public const PATH_IMAGE_DESCRIPTION = 'path_image_description';

	/**
	 * XPath key to the item image caption.
	 *
	 * @var string
	 */
	public const PATH_IMAGE_CAPTION = 'path_image_caption';

	/**
	 * XPath key to the item image credit.
	 *
	 * @var string
	 */
	public const PATH_IMAGE_CREDIT = 'path_image_credit';

	/**
	 * Flag if the block converter should be used.
	 *
	 * @var bool
	 */
	public bool $convert_content_to_blocks = true;

	/**
	 * Settings to register.
	 *
	 * XML XPaths can be set with settings or presets from a extended class. If
	 * the class doesn't have any presets the settings will presented to the
	 * user when creating a new feed loader.
	 */
	public function setting_fields(): array {
		if ( $this instanceof With_Presets ) {
			return [];
		}

		return [
			static::PATH_ITEMS             => new Fieldmanager_TextField( __( 'XPath to items', 'feed-consumer' ) ),
			static::PATH_GUID              => new Fieldmanager_TextField( __( 'XPath to guid', 'feed-consumer' ) ),
			static::PATH_TITLE             => new Fieldmanager_TextField( __( 'XPath to title', 'feed-consumer' ) ),
			static::PATH_PERMALINK         => new Fieldmanager_TextField( __( 'XPath to permalink', 'feed-consumer' ) ),
			static::PATH_CONTENT           => new Fieldmanager_TextField( __( 'XPath to content', 'feed-consumer' ) ),
			static::PATH_BYLINE            => new Fieldmanager_TextField( __( 'XPath to byline', 'feed-consumer' ) ),
			static::PATH_IMAGE             => new Fieldmanager_TextField( __( 'XPath to image URL', 'feed-consumer' ) ),
			static::PATH_IMAGE_DESCRIPTION => new Fieldmanager_TextField( __( 'XPath to image description', 'feed-consumer' ) ),
			static::PATH_IMAGE_CAPTION     => new Fieldmanager_TextField( __( 'XPath to image caption', 'feed-consumer' ) ),
			static::PATH_IMAGE_CREDIT      => new Fieldmanager_TextField( __( 'XPath to image credit', 'feed-consumer' ) ),
		];
	}

	/**
	 * Retrieve the transformed data.
	 *
	 * @return array
	 */
	public function data(): array {
		$settings = $this->processor->get_settings()['transformer'] ?? [];

		if ( $this instanceof With_Presets ) {
			$settings = array_merge( $this->presets(), $settings );
		}

		// Extract the items from the response.
		if ( empty( $settings[ static::PATH_ITEMS ] ) ) {
			$this->processor->get_logger()?->error(
				'Missing required setting: ' . static::PATH_ITEMS,
				[
					'processor' => $this->processor->name(),
					'settings'  => array_keys( $settings ),
				]
			);

			return [];
		}

		$response = $this->extractor->data();

		try {
			$items = $response->xml( $settings[ static::PATH_ITEMS ] );
		} catch ( \Throwable $e ) {
			$this->processor->get_logger()?->error(
				'Error parsing XML response: ' . $e->getMessage(),
				[
					'exception' => $e,
				]
			);

			return [];
		}

		if ( empty( $items ) ) {
			return [];
		}

		return array_map(
			fn ( SimpleXMLElement $item ) => [
				Post_Loader::BYLINE            => $this->extract_by_xpath( $item, $settings[ static::PATH_BYLINE ] ?? 'author' ),
				Post_Loader::CONTENT           => $this->convert_content_to_blocks
					? (string) new Block_Converter( $this->extract_by_xpath( $item, $settings[ static::PATH_CONTENT ] ?? 'description' ) )
					: $this->extract_by_xpath( $item, $settings[ static::PATH_CONTENT ] ?? 'description' ),
				Post_Loader::GUID              => $this->extract_by_xpath( $item, $settings[ static::PATH_GUID ] ?? 'guid' ),
				Post_Loader::IMAGE             => $this->extract_by_xpath( $item, $settings[ static::PATH_IMAGE ] ?? 'image' ),
				Post_Loader::IMAGE_CAPTION     => $this->extract_by_xpath( $item, $settings[ static::PATH_IMAGE_CAPTION ] ?? 'image_caption' ),
				Post_Loader::IMAGE_CREDIT      => $this->extract_by_xpath( $item, $settings[ static::PATH_IMAGE_CREDIT ] ?? 'image_credit' ),
				Post_Loader::IMAGE_DESCRIPTION => $this->extract_by_xpath( $item, $settings[ static::PATH_IMAGE_DESCRIPTION ] ?? 'image_description' ),
				Post_Loader::PERMALINK         => $this->extract_by_xpath( $item, $settings[ static::PATH_PERMALINK ] ?? 'link' ),
				Post_Loader::TITLE             => $this->extract_by_xpath( $item, $settings[ static::PATH_TITLE ] ?? 'title' ),
			],
			(array) $items,
		);
	}

	/**
	 * Extract from an XML element by XPath or multiple XPaths.
	 *
	 * @param SimpleXMLElement $item XML element.
	 * @param string|array     $xpath XPath or array of XPaths.
	 * @return string|nulls
	 */
	protected function extract_by_xpath( SimpleXMLElement $item, string|array $xpath ): ?string {
		if ( empty( $xpath ) ) {
			return null;
		}

		if ( is_array( $xpath ) ) {
			foreach ( $xpath as $path ) {
				$value = $this->extract_by_xpath( $item, $path );

				if ( ! empty( $value ) ) {
					return $value;
				}
			}

			return null;
		}

		return trim( $item->xpath( $xpath )[0] ?? null ) ?: null;
	}
}
