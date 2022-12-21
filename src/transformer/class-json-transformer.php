<?php
/**
 * JSON_Transformer class file
 *
 * @package feed-transformer
 */

namespace Feed_Consumer\Transformer;

use Alley\WP\Block_Converter\Block_Converter;
use Feed_Consumer\Contracts\With_Presets;
use Feed_Consumer\Contracts\With_Setting_Fields;
use Feed_Consumer\Loader\Post_Loader;
use Fieldmanager_TextField;

use function Mantle\Support\Helpers\data_get;

/**
 * JSON Transformer
 *
 * Transform the extracted content into an array of items by their JSON path.
 * Designed for feeds of content to be converted into multiple posts.
 */
class JSON_Transformer extends Transformer implements With_Setting_Fields {

	/**
	 * Settings to register.
	 *
	 * JSON paths can be set with settings or presets from a extended class. If
	 * the class doesn't have any presets the settings will presented to the
	 * user when creating a new feed loader.
	 */
	public function setting_fields(): array {
		if ( $this instanceof With_Presets ) {
			return [];
		}

		return [
			static::PATH_ITEMS             => new Fieldmanager_TextField( __( 'Path to items', 'feed-consumer' ) ),
			static::PATH_GUID              => new Fieldmanager_TextField( __( 'Path to guid', 'feed-consumer' ) ),
			static::PATH_TITLE             => new Fieldmanager_TextField( __( 'Path to title', 'feed-consumer' ) ),
			static::PATH_PERMALINK         => new Fieldmanager_TextField( __( 'Path to permalink', 'feed-consumer' ) ),
			static::PATH_CONTENT           => new Fieldmanager_TextField( __( 'Path to content', 'feed-consumer' ) ),
			static::PATH_BYLINE            => new Fieldmanager_TextField( __( 'Path to byline', 'feed-consumer' ) ),
			static::PATH_IMAGE             => new Fieldmanager_TextField( __( 'Path to image URL', 'feed-consumer' ) ),
			static::PATH_IMAGE_DESCRIPTION => new Fieldmanager_TextField( __( 'Path to image description', 'feed-consumer' ) ),
			static::PATH_IMAGE_CAPTION     => new Fieldmanager_TextField( __( 'Path to image caption', 'feed-consumer' ) ),
			static::PATH_IMAGE_CREDIT      => new Fieldmanager_TextField( __( 'Path to image credit', 'feed-consumer' ) ),
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

		$response = $this->extractor->data();

		try {
			$items = $response->json(
				! empty( $settings[ static::PATH_ITEMS ] ) ? $settings[ static::PATH_ITEMS ] : null
			);
		} catch ( \Throwable $e ) {
			$this->processor->get_logger()?->error(
				'Error parsing JSON response: ' . $e->getMessage(),
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
			fn ( array $item ) => [
				Post_Loader::BYLINE            => $this->extract_by_path( $item, $settings[ static::PATH_BYLINE ] ?? 'author' ),
				Post_Loader::CONTENT           => empty( $settings[ static::DONT_CONVERT_TO_BLOCKS] )
					? (string) new Block_Converter( $this->extract_by_path( $item, $settings[ static::PATH_CONTENT ] ?? 'description' ) )
					: $this->extract_by_path( $item, $settings[ static::PATH_CONTENT ] ?? 'description' ),
				Post_Loader::GUID              => $this->extract_by_path( $item, $settings[ static::PATH_GUID ] ?? 'guid' ),
				Post_Loader::IMAGE             => $this->extract_by_path( $item, $settings[ static::PATH_IMAGE ] ?? 'image' ),
				Post_Loader::IMAGE_CAPTION     => $this->extract_by_path( $item, $settings[ static::PATH_IMAGE_CAPTION ] ?? 'image_caption' ),
				Post_Loader::IMAGE_CREDIT      => $this->extract_by_path( $item, $settings[ static::PATH_IMAGE_CREDIT ] ?? 'image_credit' ),
				Post_Loader::IMAGE_DESCRIPTION => $this->extract_by_path( $item, $settings[ static::PATH_IMAGE_DESCRIPTION ] ?? 'image_description' ),
				Post_Loader::PERMALINK         => $this->extract_by_path( $item, $settings[ static::PATH_PERMALINK ] ?? 'link' ),
				Post_Loader::TITLE             => $this->extract_by_path( $item, $settings[ static::PATH_TITLE ] ?? 'title' ),
			],
			(array) $items,
		);
	}

	/**
	 * Extract from an array object by path or multiple paths
	 *
	 * @param array        $item JSON element.
	 * @param string|array $path Path or array of Paths.
	 * @return string|null
	 */
	protected function extract_by_path( array $item, string|array $path ): ?string {
		if ( empty( $path ) ) {
			return null;
		}

		if ( is_array( $path ) ) {
			foreach ( $path as $path ) {
				$value = $this->extract_by_path( $item, $path );

				if ( ! empty( $value ) ) {
					return $value;
				}
			}

			return null;
		}

		return trim( data_get( $item, $path, null ) );
	}
}
