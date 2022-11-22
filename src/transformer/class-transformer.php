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

	/**
	 * Retrieve/set the extractor instance.
	 *
	 * @param Extractor $extractor Extractor instance to set, optional.
	 * @return Extractor
	 */
	public function extractor( ?Extractor $extractor = null ): Extractor {
		if ( $extractor ) {
			$this->extractor = $extractor;
		}

		return $this->extractor;
	}
}
