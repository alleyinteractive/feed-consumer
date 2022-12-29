<?php
/**
 * With_Cursor interface file.
 *
 * @package feed-consumer
 */

namespace Feed_Consumer\Contracts;

/**
 * With Cursor Interface
 */
interface With_Cursor {
	/**
	 * Cursor meta key.
	 *
	 * @var string
	 */
	public const CURSOR_META_KEY = 'feed_consumer_cursor';

	/**
	 * Retrieve the cursor.
	 *
	 * @return string|null
	 */
	public function get_cursor(): ?string;

	/**
	 * Set the cursor.
	 *
	 * @param string $cursor Cursor to set.
	 * @return static
	 */
	public function set_cursor( string $cursor ): static;
}
