<?php
namespace Feed_Consumer\Tests;

use Feed_Consumer\Settings;

class PostTypeTest extends TestCase {
	public function test_post_type_exists() {
		$this->assertTrue( post_type_exists( Settings::POST_TYPE ) );
	}
}
