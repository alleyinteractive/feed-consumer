<?php
/**
 * Loader interface file.
 *
 * @package feed-consumer
 */

namespace Feed_Consumer\Contracts;

use Feed_Consumer\Processor\Processor;

/**
 * Loader Interface
 */
interface Loader {
	/**
	 * Constructor.
	 *
	 * @param Processor $processor Data processor instance.
	 * @param Extractor $extractor Data extractor instance.
	 * @param Transformer $transformer Data transformer instance.
	 */
	public function __construct( Processor $processor, Extractor $extractor, Transformer $transformer );

	/**
	 * Load the data.
	 *
	 * @return \WP_Post|\WP_Term|mixed
	 */
	public function load(): mixed;
}
