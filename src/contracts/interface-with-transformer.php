<?php
/**
 * With_Transformer interface file.
 *
 * @package feed-consumer
 */

namespace Feed_Consumer\Contracts;

/**
 * With Transformer Interface
 */
interface With_Transformer {
	/**
	 * Retrieve/set the transformer instance.
	 *
	 * @param Transformer $transformer Transformer instance to set, optional.
	 * @return Transformer
	 */
	public function transformer( ?Transformer $transformer = null ): Transformer;
}
