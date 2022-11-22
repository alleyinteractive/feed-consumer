<?php
/**
 * Transformer interface file.
 *
 * @package feed-consumer
 */

namespace Feed_Consumer\Contracts;

/**
 * Transformer Interface
 */
interface Transformer extends With_Extractor, With_Processor {
	/**
	 * Retrieve the transformed data.
	 *
	 * @return array
	 */
	public function data(): array;
}
