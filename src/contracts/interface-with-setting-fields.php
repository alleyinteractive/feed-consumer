<?php
/**
 * With_Setting_Fields interface file
 *
 * @package feed-consumer
 */

namespace Feed_Consumer\Contracts;

/**
 * Define an object that registers setting fields.
 */
interface With_Setting_Fields {
	/**
	 * Settings to register.
	 */
	public function setting_fields(): array;
}
