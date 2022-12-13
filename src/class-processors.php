<?php
/**
 * Processors class file
 *
 * @package feed-consumer
 */

namespace Feed_Consumer;

use Psr\Log\LoggerInterface;

/**
 * Processors Manager
 */
class Processors {
	/**
	 * Processors.
	 *
	 * @var array
	 */
	protected array $processors = [];

	/**
	 * Logger instance.
	 *
	 * @var LoggerInterface|null
	 */
	protected ?LoggerInterface $logger = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
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
}
