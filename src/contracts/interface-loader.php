<?php
/**
 * Loader interface file.
 *
 * @package feed-consumer
 */

namespace Feed_Consumer\Contracts;

/**
 * Loader Interface
 */
interface Loader extends With_Transformer, With_Processor {
	/**
	 * Load the data.
	 *
	 * @return \WP_Post|\WP_Term|mixed
	 */
	public function load(): mixed;
}
