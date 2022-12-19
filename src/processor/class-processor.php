<?php
/**
 * Processor class file
 *
 * @package feed-consumer
 */

namespace Feed_Consumer\Processor;

use Feed_Consumer\Contracts\Processor as Contract;
use Feed_Consumer\Contracts\Extractor;
use Feed_Consumer\Contracts\Transformer;
use Feed_Consumer\Contracts\Loader;
use Feed_Consumer\Contracts\With_Setting_Fields;
use Psr\Log\LoggerInterface;

/**
 * Abstract Processor
 */
abstract class Processor implements Contract, With_Setting_Fields {
	/**
	 * Setting for the interval to poll the feed.
	 *
	 * @var string
	 */
	public const SETTING_INTERVAL = 'interval';

	/**
	 * Extractor instance.
	 *
	 * @var Extractor|null
	 */
	protected ?Extractor $extractor = null;

	/**
	 * Transformer instance.
	 *
	 * @var Transformer|null
	 */
	protected ?Transformer $transformer = null;

	/**
	 * Loader instance.
	 *
	 * @var Loader|null
	 */
	protected ?Loader $loader = null;

	/**
	 * Logger instance.
	 *
	 * @var LoggerInterface|null
	 */
	protected ?LoggerInterface $logger = null;

	/**
	 * Middleware for loading.
	 *
	 * @var callable[]
	 */
	protected array $middleware = [];

	/**
	 * Settings for the processor.
	 *
	 * @var array
	 */
	protected array $settings = [];

	/**
	 * Settings fields for the processor.
	 *
	 * @return array
	 */
	public function setting_fields(): array {
		return [
			static::SETTING_INTERVAL => new \Fieldmanager_Select(
				[
					'label'         => __( 'Polling Interval', 'feed-consumer' ),
					'default_value' => HOUR_IN_SECONDS,
					/**
					 * Filter the polling intervals for the feed extractor.
					 *
					 * @param array $intervals Intervals to use.
					 */
					'options'       => (array) apply_filters(
						'feed_consumer_feed_extractor_intervals',
						[
							HOUR_IN_SECONDS      => __( 'Hourly', 'feed-consumer' ),
							12 * HOUR_IN_SECONDS => __( 'Twice Daily', 'feed-consumer' ),
							DAY_IN_SECONDS       => __( 'Daily', 'feed-consumer' ),
							WEEK_IN_SECONDS      => __( 'Weekly', 'feed-consumer' ),
						]
					),
				],
			),
		];
	}

	/**
	 * Retrieve the stored settings for the processor.
	 *
	 * @param array|null $settings The settings to set, optional.
	 * @return array
	 */
	public function settings( ?array $settings = null ): array {
		if ( $settings ) {
			$this->settings = $settings;
		}

		return $this->settings;
	}

	/**
	 * Retrieve or set the logger for the processor.
	 *
	 * @param LoggerInterface $logger The logger to set, optional.
	 * @return LoggerInterface
	 */
	public function logger( ?LoggerInterface $logger = null ): ?LoggerInterface {
		if ( $logger ) {
			$this->logger = $logger;
		}

		return $this->logger;
	}

	/**
	 * Retrieve or set the extractors for the processor.
	 *
	 * @param Extractor $extractor The extractor to set, optional.
	 * @return Extractor|null
	 */
	public function extractor( ?Extractor $extractor = null ): ?Extractor {
		if ( $extractor ) {
			$this->extractor = $extractor;
		}

		return $this->extractor;
	}


	/**
	 * Retrieve or set the transformer for the processor.
	 *
	 * @param Transformer $transformer The transformer to set, optional.
	 * @return Transformer|null
	 */
	public function transformer( ?Transformer $transformer = null ): ?Transformer {
		if ( $transformer ) {
			$this->transformer = $transformer;
		}

		return $this->transformer;
	}

	/**
	 * Retrieve or set the loaders for the processor.
	 *
	 * @param Loader $loader The loader to set, optional.
	 * @return Loader|null
	 */
	public function loader( ?Loader $loader = null ): ?Loader {
		if ( $loader ) {
			$this->loader = $loader;
		}

		return $this->loader;
	}

	/**
	 * Retrieve or add loader middleware.
	 *
	 * Middleware can be used to modify the content before and/or after it is
	 * loaded to the site.
	 *
	 * @param callable $middleware The middleware to add.
	 */
	public function middleware( ?callable $middleware = null ): array {
		if ( $middleware ) {
			$this->middleware[] = $middleware;
		}

		/**
		 * Filters the middleware stack for the processor.
		 *
		 * @param callable[] $middleware The middleware stack.
		 * @param Processor  $processor  The processor instance.
		 */
		return (array) apply_filters( 'feed_consumer_processor_middleware', $this->middleware, $this );
	}

	/**
	 * Clear the middleware stack.
	 *
	 * @return void
	 */
	public function clear_middleware(): void {
		$this->middleware = [];
	}

	/**
	 * Getter for the interval to run the processor.
	 *
	 * @return int
	 */
	public function frequency(): int {
		return $this->settings['processor'][ static::SETTING_INTERVAL ] ?? HOUR_IN_SECONDS;
	}
}
