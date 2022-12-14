<?php
/**
 * Post_Loader class file
 *
 * @package feed-consumer
 */

namespace Feed_Consumer\Loader;

use Feed_Consumer\Contracts\With_Presets;
use Feed_Consumer\Contracts\With_Settings;
use Fieldmanager_Autocomplete;
use Fieldmanager_Datasource_Term;
use Fieldmanager_Select;
use InvalidArgumentException;
use Mantle\Support\Pipeline;

use function Mantle\Support\Helpers\collect;

/**
 * Post Loader
 *
 * Loader that takes transformer data and loads it into the system as a post.
 *
 * @todo Add support for terms from settings.
 * @todo Add support for featured image.
 * @todo Add support for bylines.
 */
class Post_Loader extends Loader implements With_Settings {
	/**
	 * Load the data
	 *
	 * @throws InvalidArgumentException When the data from the transformer is not an array.
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

		$loader_settings = $this->processor->settings()['loader'] ?? [];

		if ( $this instanceof With_Presets ) {
			$loader_settings = array_merge( $this->presets(), $loader_settings );
		}

		return collect( $data )
			->map(
				function ( array $postarr ) use ( $loader_settings ) {
					// Ensure some defaults are set in the post array.
					$postarr = array_merge(
						[
							'post_status' => $loader_settings['post_status'] ?? 'draft',
							'post_type'   => $loader_settings['post_type'] ?? 'post',
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
							'meta_key'         => static::META_KEY_REMOTE_ID, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
							'meta_value'       => $postarr['remote_id'] ?? $postarr['guid'], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
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
							$postarr['ID'] = $existing[0];
						} else {
							return null;
						}
					}

					// Ensure the remote ID is set in post meta.
					$postarr['meta_input'][ static::META_KEY_REMOTE_ID ] = $postarr['remote_id'] ?? $postarr['guid'];

					return ( new Pipeline() )
						->send( $postarr )
						->through( $this->processor()->middleware() )
						->then(
							function ( array $postarr ) use ( $loader_settings ) {
								// Attempt to insert or update the post.
								if ( ! empty( $postarr['ID'] ) ) {
									$post_id = wp_update_post( $postarr, true );
								} else {
									$post_id = wp_insert_post( $postarr, true );
								}

								if ( is_wp_error( $post_id ) ) {
									throw new InvalidArgumentException( $post_id->get_error_message() );
								}

								// Process any terms that need to be set on the post.
								if ( ! empty( $loader_settings['terms'] ) ) {
									$this->assign_terms( $loader_settings['terms'], $post_id );
								}

								return get_post( $post_id );
							}
						);
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

		return $this->base_settings();
	}

	/**
	 * Base settings for the post loader.
	 *
	 * @return array
	 */
	protected function base_settings(): array {
		return [
			'post_type'   => new Fieldmanager_Select(
				[
					'label'   => __( 'Post Type', 'feed-consumer' ),
					'options' => collect( get_post_types( [ 'public' => true ], 'objects' ) )
						->pluck( 'label', 'name' )
						->all(),
				],
			),
			'post_status' => new Fieldmanager_Select(
				[
					'label'   => __( 'Post Status', 'feed-consumer' ),
					'options' => get_post_statuses(),
				]
			),
			'terms'       => new Fieldmanager_Autocomplete(
				[
					'label'          => __( 'Terms', 'feed-consumer' ),
					'datasource'     => new Fieldmanager_Datasource_Term(
						[
							'taxonomy' => array_keys( get_taxonomies( [ 'public' => true ] ) ),
						]
					),
					'limit'          => 0,
					'add_more_label' => __( 'Add Term', 'feed-consumer' ),
				]
			),
		];
	}


	/**
	 * Assign terms to a post from the loader's settings.
	 *
	 * @param int[] $term_ids Term IDs to assign.
	 * @param int   $post_id Post ID to assign terms to.
	 * @return void
	 */
	protected function assign_terms( array $term_ids, int $post_id ): void {
		$taxonomy_map = [];

		// Assign terms to the post from different taxonomies.
		foreach ( $term_ids as $term_id ) {
			$term = get_term( $term_id );

			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			$taxonomy_map[ $term->taxonomy ][] = $term_id;
		}

		foreach ( $taxonomy_map as $taxonomy => $term_ids ) {
			wp_set_post_terms( $post_id, $term_ids, $taxonomy, true );
		}
	}
}
