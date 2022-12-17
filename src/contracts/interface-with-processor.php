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
	 * Retrieve the processor instance.
	 *
	 * @return Processor|null
	 */
	public function get_processor(): ?Processor;

	/**
	 * Set the processor instance.
	 *
	 * @param Processor $processor Processor instance to set.
	 * @return static
	 */
	public function set_processor( ?Processor $processor = null ): static;
}
