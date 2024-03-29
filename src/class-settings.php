<?php
/**
 * Settings class file
 *
 * @package feed-consumer
 */

namespace Feed_Consumer;

use Mantle\Support\Str;
use Feed_Consumer\Contracts\Processor;
use Feed_Consumer\Contracts\With_Setting_Fields;
use Fieldmanager_Group;
use Fieldmanager_Select;
use Mantle\Support\Traits\Singleton;
use Throwable;
use WP_Post;

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
	public const POST_TYPE = 'feed_consumer';

	/**
	 * Meta key for settings.
	 *
	 * @var string
	 */
	public const SETTINGS_META_KEY = 'feed_consumer_settings';

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
	 * Constructor.
	 */
	protected function __construct() {
		add_action( 'init', [ $this, 'on_init' ] );
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_filter( 'post_updated_messages', [ $this, 'set_post_updated_messages' ] );
		add_action( 'fm_post_' . static::POST_TYPE, [ $this, 'register_fields' ] );
		add_action( 'add_meta_boxes_' . static::POST_TYPE, [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post', [ $this, 'on_save_post' ], 99, 2 ); // Uses 'save_post' action to save settings because Fieldmanager does.
	}

	/**
	 * Called on 'init'.
	 */
	public function on_init() {
		if ( ! class_exists( Fieldmanager_Group::class ) ) {
			add_action( 'admin_notices', [ $this, 'missing_fieldmanager_notice' ] );
		}

		// Nudge the user to install AI Logger if it's not installed.
		if ( ! class_exists( \AI_Logger\AI_Logger::class ) ) {
			add_action( 'admin_notices', [ $this, 'missing_ai_logger_notice' ] );
		} elseif ( function_exists( 'ai_logger_post_meta_box' ) ) {
			// Register the meta box for the post type.
			ai_logger_post_meta_box( Runner::LOG_META_KEY, __( 'Feed Consumer Logs', 'feed-consumer' ) );
		}
	}

	/**
	 * Register the post type.
	 */
	public function register_post_type() {
		register_post_type( // phpcs:ignore WordPress.NamingConventions.ValidPostTypeSlug.NotStringLiteral
			static::POST_TYPE,
			[
				'label'              => __( 'Feeds', 'feed-consumer' ),
				'labels'             => [
					'name'                     => __( 'Feeds', 'feed-consumer' ),
					'singular_name'            => __( 'Feed', 'feed-consumer' ),
					'add_new'                  => __( 'Add New Feed', 'feed-consumer' ),
					'add_new_item'             => __( 'Add New Feed', 'feed-consumer' ),
					'edit_item'                => __( 'Edit Feed', 'feed-consumer' ),
					'new_item'                 => __( 'New Feed', 'feed-consumer' ),
					'view_item'                => __( 'View Feed', 'feed-consumer' ),
					'view_items'               => __( 'View Feeds', 'feed-consumer' ),
					'search_items'             => __( 'Search Feeds', 'feed-consumer' ),
					'not_found'                => __( 'No feeds found', 'feed-consumer' ),
					'not_found_in_trash'       => __( 'No feeds found in Trash', 'feed-consumer' ),
					'parent_item_colon'        => __( 'Parent Feed:', 'feed-consumer' ),
					'all_items'                => __( 'All Feeds', 'feed-consumer' ),
					'archives'                 => __( 'Feed Archives', 'feed-consumer' ),
					'attributes'               => __( 'Feed Attributes', 'feed-consumer' ),
					'insert_into_item'         => __( 'Insert into feed', 'feed-consumer' ),
					'uploaded_to_this_item'    => __( 'Uploaded to this feed', 'feed-consumer' ),
					'featured_image'           => __( 'Featured image', 'feed-consumer' ),
					'set_featured_image'       => __( 'Set featured image', 'feed-consumer' ),
					'remove_featured_image'    => __( 'Remove featured image', 'feed-consumer' ),
					'use_featured_image'       => __( 'Use as featured image', 'feed-consumer' ),
					'filter_items_list'        => __( 'Filter feeds list', 'feed-consumer' ),
					'items_list_navigation'    => __( 'Feeds list navigation', 'feed-consumer' ),
					'items_list'               => __( 'Feeds list', 'feed-consumer' ),
					'item_published'           => __( 'Feed published.', 'feed-consumer' ),
					'item_published_privately' => __( 'Feed published privately.', 'feed-consumer' ),
					'item_reverted_to_draft'   => __( 'Feed reverted to draft.', 'feed-consumer' ),
					'item_trashed'             => __( 'Feed trashed.', 'feed-consumer' ),
					'item_scheduled'           => __( 'Feed scheduled.', 'feed-consumer' ),
					'item_updated'             => __( 'Feed updated.', 'feed-consumer' ),
					'menu_name'                => __( 'Feeds', 'feed-consumer' ),
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
	 * Set post type updated messages.
	 *
	 * The messages are as follows:
	 *
	 *   1 => "Post updated. {View Post}"
	 *   2 => "Custom field updated."
	 *   3 => "Custom field deleted."
	 *   4 => "Post updated."
	 *   5 => "Post restored to revision from [date]."
	 *   6 => "Post published. {View post}"
	 *   7 => "Post saved."
	 *   8 => "Post submitted. {Preview post}"
	 *   9 => "Post scheduled for: [date]. {Preview post}"
	 *  10 => "Post draft updated. {Preview post}"
	 *
	 * (Via https://github.com/johnbillion/extended-cpts.)
	 *
	 * @param array $messages An associative array of post updated messages with post type as keys.
	 * @return array Updated array of post updated messages.
	 */
	public function set_post_updated_messages( $messages ) {
		global $post;

		$preview_url    = get_preview_post_link( $post );
		$permalink      = get_permalink( $post );
		$scheduled_date = date_i18n( 'M j, Y @ H:i', strtotime( $post->post_date ) );

		$preview_post_link_html   = '';
		$scheduled_post_link_html = '';
		$view_post_link_html      = '';

		if ( is_post_type_viewable( static::POST_TYPE ) ) {
			// Preview-post link.
			$preview_post_link_html = sprintf(
				' <a target="_blank" href="%1$s">%2$s</a>',
				esc_url( $preview_url ),
				__( 'Preview feed', 'feed-consumer' )
			);

			// Scheduled post preview link.
			$scheduled_post_link_html = sprintf(
				' <a target="_blank" href="%1$s">%2$s</a>',
				esc_url( $permalink ),
				__( 'Preview feed', 'feed-consumer' )
			);

			// View-post link.
			$view_post_link_html = sprintf(
				' <a href="%1$s">%2$s</a>',
				esc_url( $permalink ),
				__( 'View feed', 'feed-consumer' )
			);
		}

		$messages[ static::POST_TYPE ] = [
			1  => __( 'Feed updated.', 'feed-consumer' ) . $view_post_link_html,
			2  => __( 'Custom field updated.', 'feed-consumer' ),
			3  => __( 'Custom field updated.', 'feed-consumer' ),
			4  => __( 'Feed updated.', 'feed-consumer' ),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Feed restored to revision from %s.', 'feed-consumer' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			6  => __( 'Feed published.', 'feed-consumer' ) . $view_post_link_html,
			7  => __( 'Feed saved.', 'feed-consumer' ),
			8  => __( 'Feed submitted.', 'feed-consumer' ) . $preview_post_link_html,
			/* translators: %s: date on which the feed is currently scheduled to be published */
			9  => sprintf( __( 'Feed scheduled for: %s.', 'feed-consumer' ), '<strong>' . $scheduled_date . '</strong>' ) . $scheduled_post_link_html,
			10 => __( 'Feed draft updated.', 'feed-consumer' ) . $preview_post_link_html,
		];

		return $messages;
	}

	/**
	 * Display a notice that Fieldmanager is required.
	 *
	 * @return void
	 */
	public function missing_fieldmanager_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<?php
				printf(
					/* translators: 1: open anchor tag, 2: close anchor tag */
					esc_html__( 'Feed Consumer requires %1$sFieldmanager%2$s to be installed and activated to run properly.', 'feed-consumer' ),
					'<a href="https://github.com/alleyinteractive/wordpress-fieldmanager">',
					'</a>',
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Display a notice to nudge the user to install AI Logger.
	 */
	public function missing_ai_logger_notice() {
		// Only display on the feed post type.
		if ( static::POST_TYPE !== get_current_screen()?->post_type ) {
			return;
		}
		?>
		<div data-dismissible="feed-consumer-ai-logger" class="notice notice-info">
			<p>
				<?php
				printf(
					/* translators: 1: open anchor tag, 2: close anchor tag */
					esc_html__( 'Feed Consumer recommends installing %1$sAlley Logger%2$s to log feed processing.', 'feed-consumer' ),
					'<a href="https://github.com/alleyinteractive/logger">',
					'</a>',
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Register the settings fields for the post type that include processor settings.
	 */
	public function register_fields() {
		// Register any side effects for modifying the Fieldmanager datasources.
		add_filter( 'fm_datasource_term_get_items', [ $this, 'datasource_term_get_items' ], 10, 2 );
		add_filter( 'fm_datasource_term_get_value', [ $this, 'datasource_term_get_value' ], 10, 2 );

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
							/**
							 * Filter the setting groups for a processor.
							 *
							 * Each child is converted to a Fieldmanager Group
							 * for the respective class type (extractor, transformer, loader).
							 *
							 * Plugins can use this filter to add settings to a processor in their own group.
							 *
							 * @param array     $settings_groups The settings groups for the processor.
							 * @param Processor $processor       The processor.
							 */
							$settings_groups = apply_filters(
								'feed_consumer_processor_settings',
								[
									'extractor'   => $processor->get_extractor(),
									'transformer' => $processor->get_transformer(),
									'loader'      => $processor->get_loader(),
								],
								$processor,
							);

							$children = collect( $settings_groups )
									->filter( fn ( $item ) => ! empty( $item ) && $item instanceof With_Setting_Fields )
									->map_with_keys(
										function ( object $object, string $type ) use ( $processor ): array {
											// Pass along the processor to the object.
											if ( method_exists( $object, 'set_processor' ) ) {
												$object->set_processor( $processor );
											}

											$fields = $object->setting_fields();

											// If the settings are empty, return null.
											if ( empty( $fields ) ) {
												return [];
											}

											return [
												$type => new Fieldmanager_Group(
													[
														'label'    => sprintf(
															/* translators: %s: The type of settings (extractor/transformer/loader). */
															__( '%s Settings', 'feed-consumer' ),
															Str::headline( $type ),
														),
														'children' => $fields,
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

		$settings->add_meta_box( __( 'Feed Settings', 'feed-consumer' ), static::POST_TYPE );
	}

	/**
	 * Register meta boxes for information about the current log.
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'feed-consumer-status',
			__( 'Feed Status', 'feed-consumer' ),
			[ $this, 'render_status_meta_box' ],
			static::POST_TYPE,
			'side',
			'low',
		);

		if ( apply_filters( 'feed_consumer_debug_meta_box', true ) ) {
			add_meta_box(
				'feed-consumer-debug',
				__( 'Feed Debug Meta Box', 'feed-consumer' ),
				[ $this, 'render_debug_meta_box' ],
				static::POST_TYPE,
				'normal',
				'low',
			);
		}
	}

	/**
	 * Feed status meta box.
	 *
	 * @param WP_Post $feed Feed post object.
	 */
	public function render_status_meta_box( WP_Post $feed ) {
		if ( 'publish' !== $feed->post_status ) {
			printf( '<strong>%s</strong>', esc_html__( 'Feed is not published.', 'feed-consumer' ) );
			return;
		}

		// Fetch the next run time and display it.
		try {
			$next_run = Runner::schedule_next_run( $feed->ID );
		} catch ( Throwable $e ) {
			printf(
				'<strong>%s</strong> %s',
				esc_html__( 'Feed is not scheduled due to error:', 'feed-consumer' ),
				esc_html( $e->getMessage() )
			);

			return;
		}

		if ( $next_run ) {
			if ( $next_run > current_time( 'timestamp' ) ) { // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
				printf(
					'<strong>%s</strong> <time datetime="%s">%s</time>',
					esc_html__( 'Next run:', 'feed-consumer' ),
					esc_attr( date_i18n( 'c', $next_run ) ),
					esc_html(
						sprintf(
							/* translators: %s: Human readable time difference. */
							__( '%s from now', 'feed-consumer' ),
							human_time_diff( $next_run, current_time( 'timestamp' ) ), // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
						),
					),
				);
			} else {
				printf(
					'<strong>%s</strong> %s',
					esc_html__( 'Next Run:', 'feed-consumer' ),
					esc_html__( 'Now', 'feed-consumer' )
				);
			}
		} else {
			printf( '<strong>%s</strong>', esc_html__( 'Feed is not scheduled to run.', 'feed-consumer' ) );
		}

		$last_run = get_post_meta( $feed->ID, Runner::LAST_RUN_META_KEY, true );

		if ( $last_run ) {
			printf(
				'<p><strong>%s</strong> <time datetime="%s">%s</time></p>',
				esc_html__( 'Last run:', 'feed-consumer' ),
				esc_attr( date_i18n( 'c', $last_run ) ),
				esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last_run ) ),
			);
		}
	}

	/**
	 * Feed Debug Meta Box
	 *
	 * The debug meta box displays the transformed feed data for help with
	 * debugging XML paths.
	 *
	 * @param WP_Post $feed Feed post object.
	 */
	public function render_debug_meta_box( WP_Post $feed ) {
		if ( 'publish' !== $feed->post_status ) {
			printf( '<strong>%s</strong>', esc_html__( 'Feed is not published.', 'feed-consumer' ) );
			return;
		}

		$data = get_post_meta( $feed->ID, '_transformer_debug', true );

		if ( '' === $data ) {
			try {
				$processor = Runner::processor( $feed->ID );

				$extractor = $processor
					->get_extractor()
					->set_processor( $processor );

				$extractor->run();

				$transformer = $processor
					->get_transformer()
					->set_processor( $processor )
					->set_extractor( $extractor );

				$data = $transformer->data();
			} catch ( Throwable $e ) {
				printf(
					'<strong>%s</strong> %s',
					/* translators: exception message */
					esc_html__( 'Feed error:', 'feed-consumer' ),
					esc_html( $e::class . ' - ' . $e->getMessage() )
				);

				$data = false;

				return;
			}

			update_post_meta( $feed->ID, '_transformer_debug', $data );
		}

		if ( ! empty( $data ) ) {
			printf(
				'<p>%s</p><pre style="overflow: scroll; max-height: 500px;">%s</pre>',
				esc_html__( 'Feed Transformer Output', 'feed-consumer' ),
				esc_html( var_export( $data, true ) ) // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
			);
		} else {
			printf( '<strong>%s</strong>', esc_html__( 'No data to display.', 'feed-consumer' ) );
		}
	}

	/**
	 * Include the taxonomy name in a terms datasource response.
	 *
	 * @param array $stack Term stack to modify.
	 * @param array $terms Array of terms in the response.
	 */
	public function datasource_term_get_items( $stack, $terms ) {
		$stack = [];
		foreach ( $terms as $term ) {
			$key           = $term->term_taxonomy_id;
			$taxonomy      = get_taxonomy( $term->taxonomy );
			$stack[ $key ] = sprintf( '%1$s (%2$s: %3$s)', html_entity_decode( $term->name ), __( 'taxonomy', 'nr' ), $taxonomy->label );
		}
		return $stack;
	}

	/**
	 * Modify the term datasource label for terms that display on Fieldmanager fields.
	 *
	 * @param string $value The value to display.
	 * @param mixed  $term  The stored term.
	 * @return string
	 */
	public function datasource_term_get_value( $value, $term ) {
		if ( $term instanceof \WP_Term ) {
			$taxonomy = get_taxonomy( $term->taxonomy );
			$value    = sprintf( '%1$s (%2$s: %3$s)', html_entity_decode( $term->name ), __( 'taxonomy', 'nr' ), $taxonomy->label );
		}

		return $value;
	}

	/**
	 * On save_post, reschedule the feed to run according to the new settings.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function on_save_post( $post_id, $post ) {
		if ( static::POST_TYPE !== $post->post_type ) {
			return;
		}

		if ( wp_next_scheduled( Runner::CRON_HOOK, [ $post_id ] ) ) {
			wp_clear_scheduled_hook( Runner::CRON_HOOK, [ $post_id ] );
		}

		Runner::schedule_next_run( $post_id );

		delete_post_meta( $post_id, '_transformer_debug' );
	}
}
