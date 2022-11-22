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
interface Transformer {
	/**
	 * Constructor.
	 *
	 * @param Processor $processor Data processor instance.
	 * @param Extractor $extractor Data extractor instance.
	 */
	public function __construct( Processor $processor, Extractor $extractor );

	/**
	 * Retrieve the transformed data.
	 *
	 * @return array
	 */
	public function data(): array;
}
