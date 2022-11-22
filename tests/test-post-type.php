<?php
namespace Feed_Consumer\Tests;

use Feed_Consumer\Post_Type;

class Post_Type_Test extends Test_Case {
	public function test_post_type_exists() {
		$this->assertTrue( post_type_exists( Post_Type::NAME ) );
	}
}
