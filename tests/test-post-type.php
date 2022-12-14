<?php
namespace Feed_Consumer\Tests;

use Feed_Consumer\Settings;

class Post_Type_Test extends Test_Case {
	public function test_post_type_exists() {
		$this->assertTrue( post_type_exists( Settings::POST_TYPE ) );
	}
}
