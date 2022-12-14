<?php
/**
 * Extractor interface file.
 *
 * @package feed-consumer
 */

namespace Feed_Consumer\Contracts;

use Mantle\Http_Client\Response;

/**
 * Extractor Interface
 *
 * An extractor is a means to extract data from a source and return the raw
 * data. It shouldn't be used to parse the response, which will be done in a
 * transformer.
 */
interface Extractor extends With_Processor {
	/**
	 * Extract the data.
	 *
	 * @return static
	 */
	public function run(): static;

	/**
	 * Getter for the data from the extractor.
	 *
	 * @return Response
	 */
	public function data(): Response;
}
