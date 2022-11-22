<?php
/**
 * Extractor class file
 *
 * @package feed-consumer
 */

namespace Feed_Consumer\Extractor;

use Feed_Consumer\Contracts\Extractor as Contract;
use Feed_Consumer\Contracts\Processor;

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
	 * Retrieve/set the processor instance.
	 *
	 * @param Processor $processor Processor instance to set, optional.
	 * @return Processor
	 */
	public function processor( ?Processor $processor = null ): Processor {
		if ( $processor ) {
			$this->processor = $processor;
		}

		return $this->processor;
	}
}
