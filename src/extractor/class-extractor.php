<?php
/**
 * Extractor class file
 *
 * @package feed-consumer
 */

namespace Feed_Consumer\Extractor;

use Feed_Consumer\Contracts\Extractor as Contract;
use Feed_Consumer\Contracts\Processor;
use Mantle\Http_Client\Response;

/**
 * Base Extractor
 */
abstract class Extractor implements Contract {
	/**
	 * Processor instance.
	 *
	 * @var Processor|null
	 */
	protected ?Processor $processor = null;

	/**
	 * Retrieve the processor instance.
	 *
	 * @return Processor
	 */
	public function get_processor(): ?Processor {
		return $this->processor;
	}

	/**
	 * Set the processor instance.
	 *
	 * @param Processor $processor Processor instance to set, optional.
	 * @return static
	 */
	public function set_processor( ?Processor $processor = null ): static {
		$this->processor = $processor;
		return $this;
	}

	/**
	 * Handle an error in the feed response.
	 *
	 * @param Response $response Response object.
	 */
	protected function handle_error( Response $response ): void {
		/**
		 * Fires when an error is encountered in the feed response.
		 *
		 * @param Response $response  Response object.
		 * @param static   $extractor Extractor instance.
		 */
		do_action( 'feed_consumer_extractor_error', $response, $this );
	}
}
