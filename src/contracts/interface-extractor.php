<?php
/**
 * Extractor interface file.
 *
 * @package feed-consumer
 */

namespace Feed_Consumer\Contracts;

use Feed_Consumer\Processor\Processor;
use Mantle\Http_Client\Response;

/**
 * Extractor Interface
 */
interface Extractor {
	/**
	 * Constructor.
	 *
	 * @param Processor $processor Data processor instance.
	 */
	public function __construct( Processor $processor );

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
	 * Retrieve a collection of items from the response.
	 *
	 * Provides the extractor a way to collect the individual items of an
	 * extraction into a large collection instead of a singular document.
	 *
	 * @return array
	 */
	// public function get_collection(): array;

	/**
	 * Getter for the cursor for the extractor.
	 *
	 * @return string|null Cursor if set, null otherwise.
	 */
	public function cursor(): ?string;
}
