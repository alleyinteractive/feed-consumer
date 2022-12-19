<?php
/**
 * Transformer class file
 *
 * @package feed-transformer
 */

namespace Feed_Consumer\Transformer;

use Feed_Consumer\Contracts\Extractor;
use Feed_Consumer\Contracts\Processor;
use Feed_Consumer\Contracts\Transformer as Contract;
use Feed_Consumer\Contracts\With_Extractor;
use Feed_Consumer\Contracts\With_Processor;

/**
 * Base Transformer
 */
abstract class Transformer implements With_Extractor, With_Processor, Contract {
	/**
	 * Processor instance.
	 *
	 * @var Processor|null
	 */
	protected ?Processor $processor;

	/**
	 * Extractor instance.
	 *
	 * @var Extractor|null
	 */
	protected ?Extractor $extractor;

	/**
	 * Retrieve the processor instance.
	 *
	 * @return Processor|null
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
	 * @param Extractor $extractor Extractor instance to set.
	 * @return static
	 */
	public function set_extractor( ?Extractor $extractor = null ): static {
		$this->extractor = $extractor;
		return $this;
	}
}
