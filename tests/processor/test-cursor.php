<?php

namespace Feed_Consumer\Tests\Processor;

use Feed_Consumer\Loader\Post_Loader;
use Feed_Consumer\Processor\RSS_Processor;
use Feed_Consumer\Runner;
use Feed_Consumer\Settings;
use Feed_Consumer\Tests\Test_Case;
use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testing\Mock_Http_Response;

/**
 * @group processor
 */
class Cursor_Test extends Test_Case {
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
