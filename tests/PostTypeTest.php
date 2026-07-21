<?php
namespace Feed_Consumer\Tests;

use Feed_Consumer\Settings;

class PostTypeTest extends TestCase {
	public function test_post_type_exists() {
		$this->assertTrue( post_type_exists( Settings::POST_TYPE ) );
	}

	public function test_post_type_is_not_publicly_visible() {
		$post_type = get_post_type_object( Settings::POST_TYPE );

		$this->assertFalse( $post_type->public );
		$this->assertFalse( $post_type->publicly_queryable );
		$this->assertFalse( $post_type->show_in_rest );
		$this->assertTrue( $post_type->exclude_from_search );
	}
}
