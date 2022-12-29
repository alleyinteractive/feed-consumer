<?php
/**
 * Cursor class file
 *
 * @package feed-consumer
 */

namespace Feed_Consumer\Processor;

/**
 * Processor Cursor Storage
 */
trait Cursor {
	/**
	 * Cursor storage.
	 *
	 * @var string|null
	 */
	protected ?string $cursor = null;

	/**
	 * Retrieve the cursor.
	 *
	 * @return string|null
	 */
	public function get_cursor(): ?string {
		return $this->cursor;
	}

	/**
	 * Set the cursor.
	 *
	 * @param string $cursor Cursor to set.
	 * @return static
	 */
	public function set_cursor( string $cursor ): static {
		$this->cursor = $cursor;

		return $this;
	}
}
