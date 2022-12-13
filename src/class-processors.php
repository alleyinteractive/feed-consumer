<?php
/**
 * Processors class file
 *
 * @package feed-consumer
 */

namespace Feed_Consumer;

use Mantle\Support\Traits\Singleton;
use Psr\Log\LoggerInterface;

/**
 * Processors Manager
 */
class Processors {
	use Singleton;

	/**
	 * Processors.
	 *
	 * @var array
	 */
	protected array $processors = [
		\Feed_Consumer\Processor\RSS_Processor::class,
	];

	/**
	 * Logger instance.
	 *
	 * @var LoggerInterface|null
	 */
	protected ?LoggerInterface $logger = null;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		add_action( 'init', [ $this, 'on_init' ] );
	}

	/**
	 * On init.
	 */
	public function on_init() {
		/**
		 * Filter the supported processor classes.
		 *
		 * @var string[]
		 */
		$this->processors = apply_filters( 'feed_consumer_processors', $this->processors );

		/**
		 * Filter the logger instance.
		 *
		 * @var LoggerInterface|null
		 */
		$this->logger = apply_filters( 'feed_consumer_logger', function_exists( 'ai_logger' ) ? ai_logger() : null );
	}

	/**
	 * Retrieve or set the processors.
	 *
	 * @param string[] $processors Processors to set, optional.
	 * @return string[]
	 */
	public function processors( ?array $processors = null ): array {
		if ( ! is_null( $processors ) ) {
			$this->processors = $processors;
		}

		return $this->processors;
	}

	/**
	 * Retrieve or set the logger instance.
	 *
	 * @param LoggerInterface|null $logger Logger instance to set, optional.
	 * @return LoggerInterface|null
	 */
	public function logger( ?LoggerInterface $logger = null ): ?LoggerInterface {
		if ( ! is_null( $logger ) ) {
			$this->logger = $logger;
		}

		return $this->logger;
	}
}
