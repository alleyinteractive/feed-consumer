<?php
/**
 * Loader interface file.
 *
 * @package feed-consumer
 */

namespace Feed_Consumer\Contracts;

/**
 * Loader Interface
 *
 * A loader will take transformer data and load it into the system. This could
 * be a post, term, or anything else.
 *
 * The loader has access to the processor as well as the transformer's data.
 */
interface Loader extends With_Transformer, With_Processor {
	/**
	 * Load the data.
	 *
	 * @return \WP_Post|\WP_Term|mixed
	 */
	public function load(): mixed;
}
