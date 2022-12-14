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
use Fieldmanager_Group;
use Fieldmanager_Select;
use Mantle\Support\Traits\Singleton;

use function Mantle\Support\Helpers\collect;

/**
 * Feed Consumer Post Type and Settings
 */
class Settings {
	use Singleton;

	/**
	 * Post type name.
	 *
	 * @var string
	 */
	public const NAME = 'feed_consumer';

	/**
	 * Meta key for settings.
	 *
	 * @var string
	 */
	public const SETTINGS_META_KEY = 'feed_consumer_settings';

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
		// Instantiate the processors.
		$processors = collect( Processors::instance()->processors() )
			->map( fn ( $name ) => new $name() );

		$settings = new Fieldmanager_Group(
			[
				'name'     => static::SETTINGS_META_KEY,
				'children' => array_merge(
					[
						'processor' => new Fieldmanager_Select(
							[
								'first_empty' => true,
								'label'       => __( 'Processor', 'feed-consumer' ),
								'options'     => $processors->map_with_keys(
									fn ( Processor $processor ) => [
										static::escape_setting_name( $processor::class ) => $processor->name(),
									],
								)->all(),
							]
						),
					],
					// Collect all the settings from each processor and the
					// processor's extractor, transformer, and loader.
					collect( $processors )->map_with_keys(
						function ( Processor $processor ) {
							$children = collect(
								[
									'processor'   => $processor,
									'extractor'   => $processor->extractor(),
									'transformer' => $processor->transformer(),
									'loader'      => $processor->loader(),
								]
							)
									->map_with_keys(
										function ( Processor|Extractor|Transformer|Loader $object, string $type ) use ( $processor ): array {
											if ( ! ( $object instanceof With_Settings ) ) {
												return [];
											}

											// Pass along the processor to the object.
											if ( method_exists( $object, 'processor' ) ) {
												$object->processor( $processor );
											}

											$settings = $object->settings();

											// If the settings are empty, return null.
											if ( empty( $settings ) ) {
												return [];
											}

											return [
												$type => new Fieldmanager_Group(
													[
														'label'    => sprintf(
															/* translators: %s: The type of settings (extractor/transformer/loader). */
															__( '%s Settings', 'feed-consumer' ),
															ucfirst( $type ),
														),
														'children' => $settings,
													]
												),
											];
										}
									)
									->filter()
									->all();

							return [
								$this->escape_setting_name( $processor::class ) => new Fieldmanager_Group(
									[
										'display_if' => [
											'src'   => 'processor',
											'value' => static::escape_setting_name( $processor::class ),
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
	 * Escape a class name for use in a setting.
	 *
	 * @param string $class_name Class name to escape.
	 * @return string
	 */
	public static function escape_setting_name( string $class_name ): string {
		return str_replace( '\\', '_', $class_name );
	}

	/**
	 * Register meta boxes for information about the current log.
	 */
	public function add_meta_boxes() {
		// tktk.
	}
}
