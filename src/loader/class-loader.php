<?php
namespace Feed_Consumer\Loader;

use Feed_Consumer\Contracts\Loader as Contract;
use Feed_Consumer\Contracts\Processor;
use Feed_Consumer\Contracts\Transformer;

abstract class Loader implements Contract {
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
	 * @param Processor|null $processor
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
	 * @param Transformer|null $transformer
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
