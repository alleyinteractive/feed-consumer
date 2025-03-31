<?php
/**
 * Graphql_Processor class file
 *
 * @package feed-consumer
 */

namespace Feed_Consumer\Processor;

use Feed_Consumer\Extractor\Graphql_Query_Extractor;
use Feed_Consumer\Loader\Post_Loader;
use Feed_Consumer\Transformer\JSON_Transformer;

/**
 * Graphql Processor
 *
 * Extracts an array of items from an Graphql query with paths for the elements
 * controlled by the feed's settings.
 */
class Graphql_Processor extends Processor {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this
			->set_extractor( new Graphql_Query_Extractor() )
			->set_transformer( new JSON_Transformer() )
			->set_loader( new Post_Loader() );
	}

	/**
	 * Getter for the name of the processor.
	 *
	 * @return string
	 */
	public function name(): string {
		return __( 'GraphQL Processor', 'feed-consumer' );
	}
}
