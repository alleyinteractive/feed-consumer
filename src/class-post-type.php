<?php
/**
 * Post_Type class file
 *
 * @package feed-consumer
 */

namespace Feed_Consumer;

use Feed_Consumer\Contracts\Extractor;
use Feed_Consumer\Contracts\Loader;
use Feed_Consumer\Contracts\Processor;
use Feed_Consumer\Contracts\Transformer;
use Feed_Consumer\Contracts\With_Settings;
use Fieldmanager_Field;
use Fieldmanager_Group;
use Fieldmanager_Select;
use Mantle\Support\Traits\Singleton;

use function Mantle\Support\Helpers\collect;

/**
 * Feed Consumer Post Type
 */
class Post_Type {
	use Singleton;

	/**
	 * Post type name.
	 *
	 * @var string
	 */
	public const NAME = 'feed_consumer';

	/**
	 * Constructor.
	 */
	protected function __construct() {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'fm_post_' . static::NAME, [ $this, 'register_fields' ] );
		add_action( 'add_meta_boxes_' . static::NAME, [ $this, 'add_meta_boxes' ] );
	}

	/**
	 * Register the post type.
	 */
	public function register_post_type() {
		register_post_type( // phpcs:ignore WordPress.NamingConventions.ValidPostTypeSlug.NotStringLiteral
			static::NAME,
			[
				'label'              => __( 'Feeds', 'feed-consumer' ),
				'labels'             => [
					'add_new_item'               => __( 'Add New Feed', 'feed-consumer' ),
					'add_or_remove_items'        => __( 'Add or remove feeds', 'feed-consumer' ),
					'all_items'                  => __( 'All Feeds', 'feed-consumer' ),
					'choose_from_most_used'      => __( 'Choose from the most used feeds', 'feed-consumer' ),
					'edit_item'                  => __( 'Edit Feed', 'feed-consumer' ),
					'menu_name'                  => __( 'Feeds', 'feed-consumer' ),
					'name'                       => __( 'Feeds', 'feed-consumer' ),
					'new_item_name'              => __( 'New Feed Name', 'feed-consumer' ),
					'not_found'                  => __( 'No feeds found.', 'feed-consumer' ),
					'parent_item_colon'          => __( 'Parent Feed:', 'feed-consumer' ),
					'parent_item'                => __( 'Parent Feed', 'feed-consumer' ),
					'search_items'               => __( 'Search Feeds', 'feed-consumer' ),
					'separate_items_with_commas' => __( 'Separate feeds with commas', 'feed-consumer' ),
					'singular_name'              => __( 'Feed', 'feed-consumer' ),
					'update_item'                => __( 'Update Feed', 'feed-consumer' ),
				],
				'menu_icon'          => 'dashicons-rss',
				'public'             => true,
				'publicly_queryable' => false,
				'show_in_rest'       => true,
				'supports'           => [
					'title',
				],
				'show_ui'            => true,
			]
		);
	}

	/**
	 * Register the settings fields for the post type that include processor settings.
	 */
	public function register_fields() {
		$processors = collect( Processors::instance()->processors() )
			->map( fn ( $name ) => new $name() );

		$settings = new Fieldmanager_Group(
			[
				'name'          => 'settings',
				'add_to_prefix' => false,
				'children'      => array_merge(
					[
						'processor' => new Fieldmanager_Select(
							[
								'first_empty' => true,
								'label'       => __( 'Processor', 'feed-consumer' ),
								'options'     => $processors->map_with_keys(
									function ( Processor $processor ) {
										return [ $processor::class => $processor->name() ];
									}
								)->all(),
							]
						),
					],
					collect( $processors )
						->map_with_keys(
							function ( Processor $processor ) {
								$children = collect(
									[
										$processor,
										$processor->extractor(),
										$processor->transformer(),
										$processor->loader(),
									]
								)
										->map(
											function ( Processor|Extractor|Transformer|Loader $object ) use ( $processor ) {
												if ( ! ( $object instanceof With_Settings ) ) {
													return null;
												}

												// Pass along the processor to the object.
												if ( method_exists( $object, 'processor' ) ) {
													$object->processor( $processor );
												}

												return collect( $object->settings() )
													->map( [ $this, 'sanitize_field' ] )
													->all();
											}
										)
										->filter()
										->flatten()
										->all();

								return [
									$processor::class => new Fieldmanager_Group(
										[
											'display_if' => [
												'src'   => 'processor',
												'value' => $processor::class,
											],
											'label'      => sprintf(
												/* translators: %s: Processor name. */
												__( '%s Settings', 'feed-consumer' ),
												$processor->name(),
											),
											'children'   => $children,
										]
									),
								];
							}
						)
						->all(),
				),
			]
		);

		$settings->add_meta_box( __( 'Feed Settings', 'feed-consumer' ), static::NAME );
	}

	/**
	 * Sanitize a field for use in a field's settings.
	 *
	 * Because fields are collapsed into a single flat array, we need to ensure
	 * that each field has a name set and doesn't rely on it's array index for field name.
	 *
	 * @param Fieldmanager_Field $field Field to sanitize.
	 * @param mixed              $index Settings index.
	 * @return Fieldmanager_Field
	 */
	public function sanitize_field( Fieldmanager_Field $field, $index ): Fieldmanager_Field {
		// Ensure the field has a name and doesn't rely on the index.
		if ( empty( $field->name ) ) {
			$field->name = $index;
		}

		return $field;
	}

	public function add_meta_boxes() {
		// tktk.
	}
}
