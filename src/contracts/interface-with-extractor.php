<?php
/**
 * With_Extractor interface file.
 *
 * @package feed-consumer
 */

namespace Feed_Consumer\Contracts;

/**
 * With Extractor Interface
 */
interface With_Extractor {
	/**
	 * Retrieve the extractor instance.
	 *
	 * @return Extractor
	 */
	public function get_extractor(): ?Extractor;

	/**
	 * Set the extractor instance.
	 *
	 * @param Extractor $extractor Extractor instance to set.
	 * @return static
	 */
	public function set_extractor( ?Extractor $extractor = null ): static;
}
