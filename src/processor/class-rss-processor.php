<?php
/**
 * RSS_Processor class file
 *
 * @package feed-consumer
 */

namespace Feed_Consumer\Processor;

use Feed_Consumer\Contracts\With_Settings;
use Feed_Consumer\Extractor\Feed_Extractor;
use Feed_Consumer\Transformer\RSS_Transformer;

/**
 * RSS Processor
 *
 * Extracts an array of items from an RSS feed.
 */
class RSS_Processor extends Processor implements With_Settings {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->extractor( new Feed_Extractor() );
		$this->transformer( new RSS_Transformer() );
		// $this->loader( new Post_Loader() );
	}

	/**
	 * Getter for the name of the processor.
	 *
	 * @return string
	 */
	public function name(): string {
		return __( 'RSS Processor', 'feed-consumer' );
	}
}