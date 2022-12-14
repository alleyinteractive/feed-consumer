<?php
/**
 * Transformer interface file.
 *
 * @package feed-consumer
 */

namespace Feed_Consumer\Contracts;

/**
 * Transformer Interface
 *
 * A transformer takes data from an extractor and transforms it into a
 * consumable format for a loader. Often times this data will be post data that
 * a loader will use to transform to a post.
 *
 * The transformer has access to the processor as well as the extractor's data.
 */
interface Transformer extends With_Extractor, With_Processor {
	/**
	 * Retrieve the transformed data.
	 *
	 * @return array
	 */
	public function data(): array;
}
