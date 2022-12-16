<?php
/**
 * Post_Loader class file
 *
 * @package feed-consumer
 */

namespace Feed_Consumer\Loader;

use Feed_Consumer\Contracts\With_Presets;
use Feed_Consumer\Contracts\With_Setting_Fields;
use Fieldmanager_Autocomplete;
use Fieldmanager_Checkbox;
use Fieldmanager_Datasource_Term;
use Fieldmanager_Select;
use InvalidArgumentException;
use Mantle\Support\Pipeline;

use function Feed_Consumer\create_or_get_attachment_from_url;
use function Mantle\Support\Helpers\collect;

/**
 * Post Loader
 *
 * Loader that takes transformer data and loads it into the system as a post.
 */
class Post_Loader extends Loader implements With_Setting_Fields {
	/**
	 * Key for storing the post's byline.
	 *
	 * @var string
	 */
	public const BYLINE = 'byline';

	/**
	 * Key for storing the post's content.
	 *
	 * @var string
	 */
	public const CONTENT = 'post_content';

	/**
	 * Key for storing the post's guid.
	 *
	 * @var string
	 */
	public const GUID = 'guid';

	/**
	 * Key for storing the post's image URL.
	 *
	 * @var string
	 */
	public const IMAGE = 'image';

	/**
	 * Key for storing the post image's alt text.
	 *
	 * @var string
	 */
	public const IMAGE_ALT = 'image_alt';

	/**
	 * Key for storing the post image's caption.
	 *
	 * @var string
	 */
	public const IMAGE_CAPTION = 'image_caption';

	/**
	 * Key for storing the post image's credit.
	 *
	 * @var string
	 */
	public const IMAGE_CREDIT = 'image_credit';

	/**
	 * Key for storing the post image's description.
	 *
	 * @var string
	 */
	public const IMAGE_DESCRIPTION = 'image_description';

	/**
	 * Key for storing the post's permalink.
	 *
	 * @var string
	 */
	public const PERMALINK = 'permalink';

	/**
	 * Key for storing the post's title.
	 *
	 * @var string
	 */
	public const TITLE = 'post_title';

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
								/**
								 * Filter to prevent the post from being loaded.
								 *
								 * @param bool        $prevent  Whether to prevent the post from being loaded, default false.
								 * @param array       $postarr  The post array.
								 * @param Post_Loader $loader   The post loader.
								 */
								if ( true === apply_filters( 'feed_consumer_prevent_post_load', false, $postarr, $this ) ) {
									return null;
								}

								// Attempt to insert or update the post.
								if ( ! empty( $postarr['ID'] ) ) {
									$post_id = wp_update_post( $postarr, true );
								} else {
									$post_id = wp_insert_post( $postarr, true );
								}

								if ( is_wp_error( $post_id ) ) {
									throw new InvalidArgumentException( $post_id->get_error_message() );
								}

								// Assign the post's featured image if set.
								if ( ! empty( $loader_settings['ingest_images'] ) && ! empty( $postarr[ static::IMAGE ] ) ) {
									$this->assign_featured_image( $postarr, $post_id );
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
	public function setting_fields(): array {
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
			'post_type'     => new Fieldmanager_Select(
				[
					'label'   => __( 'Post Type', 'feed-consumer' ),
					'options' => collect( get_post_types( [ 'public' => true ], 'objects' ) )
						->pluck( 'label', 'name' )
						->all(),
				],
			),
			'post_status'   => new Fieldmanager_Select(
				[
					'label'   => __( 'Post Status', 'feed-consumer' ),
					'options' => get_post_statuses(),
				]
			),
			'ingest_images' => new Fieldmanager_Checkbox(
				[
					'label' => __( 'Ingest Featured Images from the Feed', 'feed-consumer' ),
				]
			),
			'terms'         => new Fieldmanager_Autocomplete(
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

	/**
	 * Assign a featured image to a post.
	 *
	 * @param array $postarr Post array.
	 * @param int   $post_id Post ID.
	 */
	protected function assign_featured_image( array $postarr, int $post_id ): void {
		/**
		 * Filter the meta key used to store the image credit.
		 *
		 * @param string $credit_meta_key Meta key to store the image credit.
		 */
		$credit_meta_key = apply_filters( 'feed_consumer_image_credit_meta_key', 'credit' );

		$attachment_id = create_or_get_attachment_from_url(
			$postarr[ static::IMAGE ],
			[
				'alt'            => $postarr[ static::IMAGE_ALT ] ?? '',
				'caption'        => $postarr[ static::IMAGE_CAPTION ] ?? '',
				'description'    => $postarr[ static::IMAGE_DESCRIPTION ] ?? '',
				'parent_post_id' => $post_id,
				'meta'           => [
					$credit_meta_key => $postarr[ static::IMAGE_CREDIT ] ?? '',
				],
			]
		);

		if ( ! $attachment_id ) {
			return;
		}

		set_post_thumbnail( $post_id, $attachment_id );
	}
}
