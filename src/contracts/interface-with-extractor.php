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
	 * Retrieve/set the extractor instance.
	 *
	 * @param Extractor $extractor Extractor instance to set, optional.
	 * @return Extractor
	 */
	public function extractor( ?Extractor $extractor = null ): Extractor;
}
