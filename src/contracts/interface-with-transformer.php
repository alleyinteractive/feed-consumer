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
	 * Retrieve the transformer instance.
	 *
	 * @return Transformer
	 */
	public function get_transformer(): ?Transformer;

	/**
	 * Set the transformer instance.
	 *
	 * @param Transformer $transformer Transformer instance to set, optional.
	 * @return static
	 */
	public function set_transformer( ?Transformer $transformer = null ): static;
}
