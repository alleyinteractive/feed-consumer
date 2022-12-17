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

	// /**
	//  * Retrieve the stored settings for the processor.
	//  *
	//  * @param array|null $settings The settings to set, optional.
	//  * @return array
	//  */
	// public function settings( ?array $settings = null ): array {
	// 	if ( $settings ) {
	// 		$this->settings = $settings;
	// 	}

	// 	return $this->settings;
	// }

	/**
	 * Retrieve the stored settings for the processor.
	 *
	 * @return array
	 */
	public function get_settings(): array {
		return $this->settings;
	}

	/**
	 * Set the settings for the processor.
	 *
	 * @param array|null $settings The settings to set.
	 */
	public function set_settings( ?array $settings = null ): static {
		$this->settings = $settings;

		return $this;
	}

	/**
	 * Retrieve the extractor instance.
	 *
	 * @return Extractor|null
	 */
	public function get_extractor(): ?Extractor {
		return $this->extractor;
	}

	/**
	 * Set the extractor instance.
	 *
	 * @param Extractor|null $extractor The extractor instance.
	 */
	public function set_extractor( ?Extractor $extractor = null ): static {
		$this->extractor = $extractor;

		return $this;
	}

	/**
	 * Retrieve the transformer instance.
	 *
	 * @return Transformer|null
	 */
	public function get_transformer(): ?Transformer {
		return $this->transformer;
	}

	/**
	 * Set the transformer instance.
	 *
	 * @param Transformer|null $transformer The transformer instance.
	 */
	public function set_transformer( ?Transformer $transformer = null ): static {
		$this->transformer = $transformer;

		return $this;
	}

	/**
	 * Retrieve the loader instance.
	 *
	 * @return Loader|null
	 */
	public function get_loader(): ?Loader {
		return $this->loader;
	}

	/**
	 * Set the loader instance.
	 *
	 * @param Loader|null $loader The loader instance.
	 */
	public function set_loader( ?Loader $loader = null ): static {
		$this->loader = $loader;

		return $this;
	}

	/**
	 * Retrieve the logger instance.
	 *
	 * @return LoggerInterface|null
	 */
	public function get_logger(): ?LoggerInterface {
		return $this->logger;
	}

	/**
	 * Set the logger instance.
	 *
	 * @param LoggerInterface|null $logger The logger instance.
	 */
	public function set_logger( ?LoggerInterface $logger = null ): static {
		$this->logger = $logger;

		return $this;
	}

	/**
	 * Retrieve the middleware stack.
	 *
	 * @return callable[]
	 */
	public function get_middleware(): array {
		/**
		 * Filters the middleware stack for the processor.
		 *
		 * @param callable[] $middleware The middleware stack.
		 * @param Processor  $processor  The processor instance.
		 */
		return (array) apply_filters( 'feed_consumer_processor_middleware', $this->middleware, $this );
	}

	/**
	 * Set the middleware stack.
	 *
	 * @param callable[] $middleware The middleware stack.
	 */
	public function set_middleware( ?array $middleware = null ): static {
		$this->middleware = $middleware;

		return $this;
	}

	/**
	 * Push middleware onto the stack.
	 *
	 * @param callable $middleware The middleware to push.
	 * @return static
	 */
	public function push_middleware( callable $middleware ): static {
		$this->middleware[] = $middleware;

		return $this;
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
