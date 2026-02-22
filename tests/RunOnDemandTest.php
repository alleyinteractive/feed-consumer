<?php
namespace Feed_Consumer\Tests;

use Feed_Consumer\Run_On_Demand;
use Feed_Consumer\Runner;
use Feed_Consumer\Settings;

class RunOnDemandTest extends TestCase {
	protected int $feed_id;

	public function setUp(): void {
		parent::setUp();

		$this->feed_id = static::factory()->post->create(
			[
				'post_type'   => Settings::POST_TYPE,
				'post_status' => 'publish',
			]
		);
	}

	public function test_ajax_action_is_registered(): void {
		$this->assertTrue( has_action( 'wp_ajax_' . Run_On_Demand::AJAX_ACTION ) );
	}

	public function test_meta_box_is_registered_for_published_feed(): void {
		$post = get_post( $this->feed_id );
		$this->assertNotNull( $post );

		Run_On_Demand::instance()->add_meta_boxes( $post );

		global $wp_meta_boxes;
		$this->assertArrayHasKey( Run_On_Demand::META_BOX_ID, $wp_meta_boxes[ Settings::POST_TYPE ]['side']['low'] );
	}

	public function test_meta_box_not_registered_for_draft(): void {
		$draft_id = static::factory()->post->create(
			[
				'post_type'   => Settings::POST_TYPE,
				'post_status' => 'draft',
			]
		);

		$post = get_post( $draft_id );
		$this->assertNotNull( $post );

		// Reset meta boxes.
		global $wp_meta_boxes;
		unset( $wp_meta_boxes[ Settings::POST_TYPE ]['side']['low'][ Run_On_Demand::META_BOX_ID ] );

		Run_On_Demand::instance()->add_meta_boxes( $post );

		$this->assertArrayNotHasKey( Run_On_Demand::META_BOX_ID, $wp_meta_boxes[ Settings::POST_TYPE ]['side']['low'] ?? [] );
	}
}
