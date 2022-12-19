<?php
/**
 * Byline_Manager integration class
 *
 * @package feed-consumer
 */

namespace Feed_Consumer\Integrations;

use Byline_Manager\Utils;
use Closure;
use Feed_Consumer\Contracts\Processor;
use Feed_Consumer\Contracts\With_Setting_Fields;
use Fieldmanager_Checkbox;
use Fieldmanager_TextField;
use WP_Post;

use function Mantle\Support\Helpers\collect;

/**
 * Byline Manager Integration
 */
class Byline_Manager implements With_Setting_Fields {
	/**
	 * Setting to control the default byline for a post.
	 *
	 * @var string
	 */
	public const SETTING_DEFAULT_BYLINE = 'default_byline';

	/**
	 * Setting to control whether the feed author should be used.
	 *
	 * @var string
	 */
	public const SETTING_USE_FEED_AUTHOR = 'use_feed_author';

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! class_exists( Utils::class ) ) {
			return;
		}

		add_filter( 'feed_consumer_processor_settings', [ $this, 'register_settings' ] );
		add_filter( 'feed_consumer_processor_middleware', [ $this, 'register_middleware' ], 10, 2 );
	}

	/**
	 * Register the settings onto the post loader.
	 *
	 * @param array $settings_groups Settings groups to modify.
	 * @return array
	 */
	public function register_settings( array $settings_groups ): array {
		$settings_groups['byline_manager'] = $this;

		return $settings_groups;
	}

	/**
	 * Settings fields for the integration.
	 *
	 * @return array
	 */
	public function setting_fields(): array {
		return [
			static::SETTING_DEFAULT_BYLINE  => new Fieldmanager_TextField(
				[
					'label'       => __( 'Default Byline', 'feed-consumer' ),
					'description' => __( 'The default byline to use for posts.', 'feed-consumer' ),
				]
			),
			static::SETTING_USE_FEED_AUTHOR => new Fieldmanager_Checkbox(
				__( 'Use Feed Author', 'feed-consumer' ),
				[
					'description' => __( 'Use the feed author as the byline for the post. If not checked, only the default byline will be used.', 'feed-consumer' ),
				]
			),
		];
	}

	/**
	 * Register middleware for supported processors.
	 *
	 * This will inject the byline when the processor is run and the post is
	 * saved.
	 *
	 * @param callable[] $middleware Middleware stack.
	 * @param Processor  $processor  Processor instance.
	 */
	public function register_middleware( array $middleware, Processor $processor ) {
		$settings = $processor->settings()['byline_manager'] ?? [];

		// Bail if the processor doesn't have any configured settings.
		if ( empty( array_filter( $settings ) ) ) {
			return $middleware;
		}

		$middleware[] = function ( array $item, Closure $next ) use ( $settings ): WP_Post {
			// Use the default byline from settings if configured.
			if ( empty( $settings[ static::SETTING_USE_FEED_AUTHOR ] ) && ! empty( $settings[ static::SETTING_DEFAULT_BYLINE ] ) ) {
				$byline = [
					'byline_entries' => [
						[
							'type' => 'text',
							'atts' => [
								'text' => $settings[ static::SETTING_DEFAULT_BYLINE ],
							],
						],
					],
				];
			} elseif ( ! empty( $settings[ static::SETTING_USE_FEED_AUTHOR ] ) && ! empty( $item['byline'] ) ) {
				// Use the item's byline if configured to, splitting the byline up.
				$parts = str_replace(
					[
						', and ',
						' and ',
					],
					', ',
					$item['byline'],
				);


				$byline = [
					'byline_entries' => collect( explode( ',', $parts ) )
						->filter()
						->map(
							fn ( $part ) => [
								'type' => 'text',
								'atts' => [
									'text' => trim( $part ),
								],
							],
						)
						->all(),
				];
			}

			$post = $next( $item );

			Utils::set_post_byline( $post->ID, $byline );

			return $post;
		};

		return $middleware;
	}
}
