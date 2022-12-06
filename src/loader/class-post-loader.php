<?php
/**
 * Post_Loader class file
 *
 * @package feed-consumer
 */

namespace Feed_Consumer\Loader;

use Feed_Consumer\Contracts\With_Presets;
use Feed_Consumer\Contracts\With_Settings;
use Fieldmanager_Select;
use InvalidArgumentException;

use function Mantle\Support\Helpers\collect;

/**
 * Post Loader
 *
 * Loader that takes transformer data and loads it into the system as a post.
 *
 * @todo Add support for pipeline.
 * @todo Add support for terms from settings.
 * @todo Add support for featured image.
 * @todo Add support for bylines.
 */
class Post_Loader extends Loader implements With_Settings {
	/**
	 * Load the data
	 *
	 * @return \WP_Post
	 */
	public function load(): mixed {
		$data = $this->transformer->data();

		if ( ! is_array( $data ) ) {
			throw new InvalidArgumentException( 'Data from transformer must be an array.' );
		}

		if ( empty( $data ) ) {
			return [];
		}

		$settings = $this->processor->settings();

		if ( $this instanceof With_Presets ) {
			$settings = array_merge( $this->presets(), $settings );
		}

		return collect( $data )
			->map(
				function ( array $postarr ) use ( $settings ) {
					// Ensure some defaults are set in the post array.
					$postarr = array_merge(
						[
							'post_status' => $settings['post_status'] ?? 'draft',
							'post_type'   => $settings['post_type'] ?? 'post',
						],
						$postarr,
					);

					if ( empty( $postarr['remote_id'] ) && empty( $postarr['guid'] ) ) {
						throw new InvalidArgumentException( 'Post guid OR remote_id are required for loading.' );
					}

					// Check for an existing post to update.
					$existing = get_posts( // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.get_posts_get_posts
						[
							'fields'           => 'ids',
							// todo: convert to class constant.
							'meta_key'         => 'feed_consumer_remote_id', // phpcs:ignore WordPress.VIP.SlowDBQuery.slow_db_query_meta_key
							'meta_value'       => $postarr['remote_id'] ?? $postarr['guid'], // phpcs:ignore WordPress.VIP.SlowDBQuery.slow_db_query_meta_value
							'post_status'      => 'any',
							'post_type'        => $postarr['post_type'],
							'posts_per_page'   => 1,
							'suppress_filters' => false,
						]
					);

					if ( ! empty( $existing[0] ) ) {
						/**
						 * Filter to update an existing post with data from the transformer.
						 *
						 * @param bool        $update   Whether to update the existing post, default false.
						 * @param \WP_Post    $existing The existing post.
						 * @param Post_Loader $loader   The post loader.
						 */
						if ( apply_filters( 'feed_consumer_update_existing_post', false, $existing[0], $this ) ) {
							$postarr['ID'] = $existing[0]->ID;
						} else {
							return null;
						}
					}

					// Ensure the remote ID is set in post meta.
					$postarr['meta_input']['feed_consumer_remote_id'] = $postarr['remote_id'] ?? $postarr['guid'];

					// Attempt to insert or update the post.
					if ( ! empty( $postarr['ID'] ) ) {
						$post_id = wp_update_post( $postarr, true );
					} else {
						$post_id = wp_insert_post( $postarr, true );
					}

					if ( is_wp_error( $post_id ) ) {
						throw new InvalidArgumentException( $post_id->get_error_message() );
					}

					return get_post( $post_id );
				}
			)
			->all();
	}

	/**
	 * Settings to register.
	 */
	public function settings(): array {
		if ( $this instanceof With_Presets ) {
			return $this->presets();
		}

		return [
			'post_type'   => new Fieldmanager_Select(
				[
					'label'   => __( 'Post Type', 'feed-consumer' ),
					'options' => get_post_types( [], 'names' ),
				]
			),
			'post_status' => new Fieldmanager_Select(
				[
					'label'   => __( 'Post Status', 'feed-consumer' ),
					'options' => get_post_statuses(),
				]
			),
		];
	}
}
