<?php
/**
 * With_Presets interface file
 *
 * @package feed-consumer
 */

namespace Feed_Consumer\Contracts;

/**
 * Define a transformer with presets.
 *
 * Useful to re-use the logic from a base transformer with presets applied on
 * top of it without needing configuration. For example, the base XML
 * transformer can extract XML from raw paths and transform it into an array.
 * The RSS transformer can then extend the XML transformer and apply the RSS
 * presets on top of it.
 */
interface With_Presets {
	/**
	 * Presets to use.
	 */
	public function presets(): array;
}
