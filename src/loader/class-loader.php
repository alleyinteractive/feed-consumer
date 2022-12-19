<?php
/**
 * Loader class file
 *
 * @package feed-consumer
 */

namespace Feed_Consumer\Loader;

use Feed_Consumer\Contracts\Loader as Contract;
use Feed_Consumer\Contracts\Processor;
use Feed_Consumer\Contracts\Transformer;

/**
 * Abstract Loader
 *
 * Loaders are used to define how the data is loaded into the system. They are
 * passed the processor and transformer instances and return the loaded data, be
 * it a post, term, array of either, or something else entirely.
 */
abstract class Loader implements Contract {
	/**
	 * Meta key for storing the remote ID of an item.
	 *
	 * @var string
	 */
	public const META_KEY_REMOTE_ID = 'feed_consumer_remote_id';

	/**
	 * Processor instance.
	 *
	 * @var Processor
	 */
	protected Processor $processor;

	/**
	 * Transformer instance.
	 *
	 * @var Transformer
	 */
	protected Transformer $transformer;

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
	 * @param Processor $processor Processor instance to set.
	 * @return static
	 */
	public function set_processor( ?Processor $processor = null ): static {
		$this->processor = $processor;
		return $this;
	}

	/**
	 * Retrieve the transformer instance.
	 *
	 * @return Transformer
	 */
	public function get_transformer(): ?Transformer {
		return $this->transformer;
	}

	/**
	 * Set the transformer instance.
	 *
	 * @param Transformer $transformer Transformer instance to set.
	 * @return static
	 */
	public function set_transformer( ?Transformer $transformer = null ): static {
		$this->transformer = $transformer;
		return $this;
	}

	/**
	 * Load the data
	 *
	 * @return \WP_Post|\WP_Term|mixed
	 */
	abstract public function load(): mixed;
}
