<?php
/**
 * XML_Transformer class file
 *
 * @package feed-transformer
 */

namespace Feed_Consumer\Transformer;

use Alley\WP\Block_Converter\Block_Converter;
use Feed_Consumer\Contracts\With_Cursor;
use Feed_Consumer\Contracts\With_Presets;
use Feed_Consumer\Contracts\With_Setting_Fields;
use Feed_Consumer\Loader\Post_Loader;
use Fieldmanager_TextField;
use SimpleXMLElement;

use function Mantle\Support\Helpers\collect;

/**
 * XML Transformer
 *
 * Transform the extracted content into an array of items by their XPath.
 * Designed for feeds of content to be converted into multiple posts.
 */
class XML_Transformer extends Transformer implements With_Setting_Fields {
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
			static::PATH_CURSOR            => new Fieldmanager_TextField( __( 'XPath to cursor field (date)', 'feed-consumer' ) ),
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

		$processor_cursor = null;

		// Determine the processor's cursor timestamp.
		if ( $this->processor && $this->processor instanceof With_Cursor ) {
			$processor_cursor = $this->processor->get_cursor();

			// Support a numeric cursor OR a date cursor.
			if ( ! is_null( $processor_cursor ) && is_numeric( $processor_cursor ) ) {
				$processor_cursor = (int) $processor_cursor;
			} elseif ( ! is_null( $processor_cursor ) ) {
				$processor_cursor = strtotime( $processor_cursor );
			}
		}

		$items = collect( (array) $items )
			->map(
				fn ( SimpleXMLElement $item ) => [
					'cursor'                       => $this->extract_by_xpath( $item, $settings[ static::PATH_CURSOR ] ?? '' ),
					Post_Loader::BYLINE            => $this->extract_by_xpath( $item, $settings[ static::PATH_BYLINE ] ?? 'author' ),
					Post_Loader::CONTENT           => empty( $settings[ static::DONT_CONVERT_TO_BLOCKS ] )
						? (string) new Block_Converter( $this->extract_by_xpath( $item, $settings[ static::PATH_CONTENT ] ?? 'description' ) ?? '' )
						: $this->extract_by_xpath( $item, $settings[ static::PATH_CONTENT ] ?? 'description' ),
					Post_Loader::GUID              => $this->extract_by_xpath( $item, $settings[ static::PATH_GUID ] ?? 'guid' ),
					Post_Loader::IMAGE             => $this->extract_by_xpath( $item, $settings[ static::PATH_IMAGE ] ?? 'image' ),
					Post_Loader::IMAGE_CAPTION     => $this->extract_by_xpath( $item, $settings[ static::PATH_IMAGE_CAPTION ] ?? 'image_caption' ),
					Post_Loader::IMAGE_CREDIT      => $this->extract_by_xpath( $item, $settings[ static::PATH_IMAGE_CREDIT ] ?? 'image_credit' ),
					Post_Loader::IMAGE_DESCRIPTION => $this->extract_by_xpath( $item, $settings[ static::PATH_IMAGE_DESCRIPTION ] ?? 'image_description' ),
					Post_Loader::PERMALINK         => $this->extract_by_xpath( $item, $settings[ static::PATH_PERMALINK ] ?? 'link' ),
					Post_Loader::TITLE             => $this->extract_by_xpath( $item, $settings[ static::PATH_TITLE ] ?? 'title' ),
				],
			)
			->filter(
				function ( array $item ) use ( $processor_cursor ) {
					// Check if the processor supports a cursor or if one is set.
					if ( is_null( $processor_cursor ) ) {
						return true;
					}

					// Check if the item has a cursor set.
					if ( ! isset( $item['cursor'] ) ) {
						return true;
					}

					// Check if the item's cursor is newer than the processor's cursor.
					$cursor = is_numeric( $item['cursor'] )
						? (int) $item['cursor']
						: strtotime( $item['cursor'] );

					return $cursor > $processor_cursor;
				}
			)
			->values();

		// Update the processor's cursor if supported.
		if ( $this->processor && $this->processor instanceof With_Cursor ) {
			$last_item = $items->last();

			if ( ! empty( $last_item['cursor'] ) ) {
				$this->processor->set_cursor( $last_item['cursor'] );
			}
		}

		return $items->all();
	}

	/**
	 * Extract from an XML element by XPath or multiple XPaths.
	 *
	 * @param SimpleXMLElement $item XML element.
	 * @param string|array     $xpath XPath or array of XPaths.
	 * @return string|null
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

		$value = null;
		$item  = $item->xpath( $xpath );

		if ( count( $item ) > 0 ) {
			$value = trim( (string) $item[0] );
		}

		return $value;
	}
}
