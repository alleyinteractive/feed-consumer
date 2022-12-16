<?php
namespace Feed_Consumer\Tests\Integrations;

use Byline_Manager\Utils;
use Feed_Consumer\Integrations\Byline_Manager;
use Feed_Consumer\Tests\Test_Case;
use Mantle\Testing\Concerns\Refresh_Database;

/**
 * @group integrations
 */
class Byline_Manager_Test extends Test_Case {
	use Refresh_Database;

	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( Utils::class ) ) {
			$this->markTestSkipped( 'Byline Manager is not active.' );
		}
	}

	public function test_integration_registered() {
		$this->assertTrue( has_filter( 'feed_consumer_processor_settings' ) );
	}

	public function test_load_post_with_default_byline() {
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
			],
			[
				'byline_manager' => [
					Byline_Manager::SETTING_DEFAULT_BYLINE => 'Default Byline to Set',
				],
			],
		);

		$posts = $loader->load();

		$this->assertCount( 2, $posts );
		$this->assertEquals( 'post', $posts[0]->post_type );

		// Compare the byline on the post.
		$byline = Utils::get_byline_entries_for_post( $posts[0]->ID );

		$this->assertCount( 1, $byline );
		$this->assertEquals( 'Default Byline to Set', $byline[0]->display_name );
	}

	public function test_load_post_byline_with_single_item() {
		$loader = $this->make_loader(
			[
				[
					'byline'       => 'User from Item',
					'post_content' => $this->faker->paragraph( 3 ),
					'post_title'   => $this->faker->words( 5, true ),
					'remote_id'    => $this->faker->uuid(),
				],
			],
			[
				'byline_manager' => [
					Byline_Manager::SETTING_USE_FEED_AUTHOR => true,
				],
			],
		);

		$posts = $loader->load();

		$this->assertCount( 1, $posts );
		$this->assertEquals( 'post', $posts[0]->post_type );

		// Compare the byline on the post.
		$byline = Utils::get_byline_entries_for_post( $posts[0]->ID );

		$this->assertCount( 1, $byline );
		$this->assertEquals( 'User from Item', $byline[0]->display_name );
	}

	public function test_load_post_byline_with_two_items() {
		$loader = $this->make_loader(
			[
				[
					'byline'       => 'First Last and Second Last',
					'post_content' => $this->faker->paragraph( 3 ),
					'post_title'   => $this->faker->words( 5, true ),
					'remote_id'    => $this->faker->uuid(),
				],
			],
			[
				'byline_manager' => [
					Byline_Manager::SETTING_USE_FEED_AUTHOR => true,
				],
			],
		);

		$posts = $loader->load();

		$this->assertCount( 1, $posts );
		$this->assertEquals( 'post', $posts[0]->post_type );

		// Compare the byline on the post.
		$byline = Utils::get_byline_entries_for_post( $posts[0]->ID );

		$this->assertCount( 2, $byline );
		$this->assertEquals( 'First Last', $byline[0]->display_name );
		$this->assertEquals( 'Second Last', $byline[1]->display_name );
	}

	public function test_load_post_byline_with_more_than_three_items() {
		$loader = $this->make_loader(
			[
				[
					'byline'       => 'First Last, Second Last, and Third Last',
					'post_content' => $this->faker->paragraph( 3 ),
					'post_title'   => $this->faker->words( 5, true ),
					'remote_id'    => $this->faker->uuid(),
				],
			],
			[
				'byline_manager' => [
					Byline_Manager::SETTING_USE_FEED_AUTHOR => true,
				],
			],
		);

		$posts = $loader->load();

		$this->assertCount( 1, $posts );
		$this->assertEquals( 'post', $posts[0]->post_type );

		// Compare the byline on the post.
		$byline = Utils::get_byline_entries_for_post( $posts[0]->ID );

		$this->assertCount( 3, $byline );
		$this->assertEquals( 'First Last', $byline[0]->display_name );
		$this->assertEquals( 'Second Last', $byline[1]->display_name );
		$this->assertEquals( 'Third Last', $byline[2]->display_name );
	}
}
