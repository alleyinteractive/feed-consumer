<?php

namespace Feed_Consumer\Tests\Processor;

use Feed_Consumer\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('processor')]
class CursorTest extends TestCase {
	public function get_default_cursor() {
		$processor = $this->make_processor();

		$this->assertNull( $processor->get_cursor() );
	}

	public function test_get_cursor() {
		$processor = $this->make_processor();

		$processor->set_cursor( '123' );

		$this->assertEquals( '123', $processor->get_cursor() );
	}

	public function test_set_cursor() {
		$processor = $this->make_processor();

		$this->assertNull( $processor->get_cursor() );

		$processor->set_cursor( '123' );

		$this->assertEquals( '123', $processor->get_cursor() );
	}
}
