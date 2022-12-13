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
	 * Retrieve/set the processor instance.
	 *
	 * @param Processor|null $processor Processor instance to set, optional.
	 * @return Processor
	 */
	public function processor( ?Processor $processor = null ): Processor {
		if ( $processor ) {
			$this->processor = $processor;
		}

		return $this->processor;
	}

	/**
	 * Retrieve/set the transformer instance.
	 *
	 * @param Transformer|null $transformer Transformer instance to set, optional.
	 * @return Transformer
	 */
	public function transformer( ?Transformer $transformer = null ): Transformer {
		if ( $transformer ) {
			$this->transformer = $transformer;
		}

		return $this->transformer;
	}

	/**
	 * Load the data
	 *
	 * @return \WP_Post|\WP_Term|mixed
	 */
	abstract public function load(): mixed;
}
