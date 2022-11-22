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

	/**
	 * Getter for the cursor for the extractor.
	 *
	 * @return string|null Cursor if set, null otherwise.
	 */
	public function cursor(): ?string;
}
