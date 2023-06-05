<?php
namespace Feed_Consumer\Tests;

use Feed_Consumer\Processor\RSS_Processor;
use Feed_Consumer\Runner;
use Feed_Consumer\Settings;

class Runner_Test extends Test_Case {
	protected int $rss_feed_id;

	public function setUp(): void {
		parent::setUp();

		$this->rss_feed_id = static::factory()->post
		->with_meta(
			[
				Settings::SETTINGS_META_KEY => [
					'processor' => Settings::escape_setting_name( RSS_Processor::class ),
					Settings::escape_setting_name( RSS_Processor::class ) => [
						'extractor' => [
							'feed_url' => 'https://alley.com/feed/',
						],
						'loader'    => [
							'post_status' => 'publish',
						],
					],
				],
			]
		)
		->create(
			[
				'post_type' => Settings::POST_TYPE,
			]
		);
	}

	public function test_cron_hook() {
		$this->assertTrue( has_action( Runner::CRON_HOOK ) );
	}

	public function test_processor_getter() {
		$instance = Runner::processor( $this->rss_feed_id );

		$this->assertInstanceOf( RSS_Processor::class, $instance );
		$this->assertEquals(
			[
				'extractor' => [
					'feed_url' => 'https://alley.com/feed/',
				],
				'loader'    => [
					'post_status' => 'publish',
				],
			],
			$instance->get_settings(),
		);
	}

	public function test_schedule_next_run() {
		Runner::schedule_next_run( $this->rss_feed_id );

		$this->assertInCronQueue( Runner::CRON_HOOK, [ $this->rss_feed_id ] );
	}
}
