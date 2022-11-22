<?php
/**
 * With_Processor interface file.
 *
 * @package feed-consumer
 */

namespace Feed_Consumer\Contracts;

/**
 * With Processor Interface
 */
interface With_Processor {
	/**
	 * Retrieve/set the processor instance.
	 *
	 * @param Processor $processor Processor instance to set, optional.
	 * @return Processor
	 */
	public function processor( ?Processor $processor = null ): Processor;
}
