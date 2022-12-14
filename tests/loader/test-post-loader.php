<?php

namespace Feed_Consumer\Tests\Loader;

use Feed_Consumer\Loader\Loader;
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

	public function test_load_post_with_settings() {
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

		register_post_type( 'test-post-type' );

		$loader->processor()->settings(
			[
				'loader' => [
					'post_type'   => 'test-post-type',
					'post_status' => 'publish',
				],
			]
		);

		$posts = $loader->load();

		$this->assertCount( 2, $posts );

		$this->assertEquals( 'test-post-type', $posts[0]->post_type );
		$this->assertEquals( 'publish', $posts[0]->post_status );
		$this->assertNotEmpty( $posts[0]->ID );

		$this->assertEquals( 'test-post-type', $posts[1]->post_type );
		$this->assertEquals( 'publish', $posts[1]->post_status );
		$this->assertNotEmpty( $posts[1]->ID );
	}

	public function test_do_not_update_existing_post_by_default() {
		$this->expectApplied( 'feed_consumer_update_existing_post' )->once();

		$remote_id = $this->faker->uuid();

		$loader = $this->make_loader(
			[
				[
					'post_content' => $this->faker->paragraph( 3 ),
					'post_title'   => $this->faker->words( 5, true ),
					'remote_id'    => $remote_id,
				],
			]
		);

		// Create the existing local post.
		static::factory()->post->with_meta(
			[
				Loader::META_KEY_REMOTE_ID => $remote_id,
			]
		)->create( [
			'post_title' => 'Original Post',
		] );

		$load = $loader->load();

		$this->assertNull( $load[0] );

		$this->assertPostExists( [
			'post_title' => 'Original Post'
		] );
	}

	public function test_update_existing_post() {
		$this->expectApplied( 'feed_consumer_update_existing_post' )->once();

		add_filter( 'feed_consumer_update_existing_post', fn () => true, 99 );

		$remote_id = $this->faker->uuid();

		$loader = $this->make_loader(
			[
				[
					'post_content' => $this->faker->paragraph( 3 ),
					'post_title'   => $this->faker->words( 5, true ),
					'remote_id'    => $remote_id,
				],
			]
		);

		// Create the existing local post.
		$post_id = static::factory()->post->with_meta(
			[
				Loader::META_KEY_REMOTE_ID => $remote_id,
			]
		)->create( [
			'post_title' => 'Original Post',
		] );

		$load = $loader->load();

		$this->assertEquals( $post_id, $load[0]->ID );

		$this->assertPostDoesNotExists( [
			'post_title' => 'Original Post'
		] );
	}

	public function test_load_posts_with_terms() {
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

		$category_id = static::factory()->category->create();
		$tag_id      = static::factory()->tag->create();

		$loader->processor()->settings(
			[
				'loader' => [
					'terms' => [
						$category_id,
						$tag_id,
					],
				],
			],
		);

		$posts = $loader->load();

		$this->assertCount( 2, $posts );

		$this->assertPostHasTerm( $posts[0], $category_id );
		$this->assertPostHasTerm( $posts[0], $tag_id );

		$this->assertPostHasTerm( $posts[1], $category_id );
		$this->assertPostHasTerm( $posts[1], $tag_id );
	}

	public function test_middleware_on_post() {
		$loader = $this->make_loader(
			[
				[
					'post_content' => $this->faker->paragraph( 3 ),
					'post_title'   => $this->faker->words( 5, true ),
					'remote_id'    => $this->faker->uuid(),
				],
			]
		);

		$_SERVER['__middleware_applied'] = false;

		$loader->processor()->middleware(
			function ( $args, $next ) {
				$_SERVER['__middleware_applied'] = $args;

				$post = $next( $args );

				update_post_meta( $post->ID, 'middleware', 'applied' );

				return $post;
			}
		);

		$posts = $loader->load();

		$this->assertCount( 1, $posts );
		$this->assertTrue( is_array( $_SERVER['__middleware_applied'] ) );
		$this->assertEquals( 'applied', get_post_meta( $posts[0]->ID, 'middleware', true ) );
	}

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
