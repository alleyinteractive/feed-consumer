<?php

namespace Feed_Consumer\Tests\Loader;

use Feed_Consumer\Loader\Post_Loader;
use Feed_Consumer\Tests\Test_Case;
use Mantle\Testing\Concerns\Refresh_Database;

/**
 * @group loader
 */
class Post_Loader_Test extends Test_Case {
	use Refresh_Database;

	public function test_load_posts_as_drafts() {
		$loader = $this->make_loader(
			[
				[
					'post_content' => $this->faker->paragraph( 3 ),
					'post_title'   => $this->faker->words( 5, true ),
					'remote_id'    => $this->faker->uuid(),
				],
				[
					'post_content' => $this->faker->paragraph( 3 ),
					'post_title'   => $this->faker->words( 5, true ),
					'remote_id'    => $this->faker->uuid(),
				],
			]
		);

		$posts = $loader->load();

		$this->assertCount( 2, $posts );
		$this->assertEquals( 'post', $posts[0]->post_type );
		$this->assertEquals( 'draft', $posts[0]->post_status );
		$this->assertNotEmpty( $posts[0]->ID );
	}

	// public function test_load_post_with_settings() {}

	// public function test_update_existing_post() {}

	// public function test_load_posts_with_terms() {}

	protected function make_loader( mixed $data, array $settings = [] ): Post_Loader {
		$processor = $this->make_processor( $settings );

		$loader = new Post_Loader();

		$loader->processor( $processor );

		$loader->transformer(
			$this->make_transformer( $data, $processor ),
		);

		return $loader;
	}
}
