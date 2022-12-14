<?php
/**
 * With_Settings interface file
 *
 * @package feed-consumer
 */

namespace Feed_Consumer\Contracts;

/**
 * Define an object that registers settings.
 */
interface With_Settings {
	/**
	 * Settings to register.
	 */
	public function settings(): array;
}
