<?php
namespace Feed_Consumer\Tests;

use Feed_Consumer\Processor\RSS_Processor;
use Feed_Consumer\Runner;
use Feed_Consumer\Settings;

class RunnerTest extends TestCase {
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

	public function test_is_not_locked_by_default() {
		$this->assertFalse( Runner::is_locked( $this->rss_feed_id ) );
	}

	public function test_acquire_and_release_lock() {
		Runner::acquire_lock( $this->rss_feed_id );
		$this->assertTrue( Runner::is_locked( $this->rss_feed_id ) );

		Runner::release_lock( $this->rss_feed_id );
		$this->assertFalse( Runner::is_locked( $this->rss_feed_id ) );
	}

	public function test_expired_lock_is_not_locked() {
		// Set a lock that has already expired.
		update_post_meta( $this->rss_feed_id, Runner::LOCK_META_KEY, time() - 1 );

		$this->assertFalse( Runner::is_locked( $this->rss_feed_id ) );
	}

	public function test_lock_duration_filter() {
		add_filter( 'feed_consumer_lock_duration', fn( $duration, $feed_id ) => 60, 10, 2 );

		Runner::acquire_lock( $this->rss_feed_id );

		$lock = (int) get_post_meta( $this->rss_feed_id, Runner::LOCK_META_KEY, true );

		// The lock expiry should be ~60 seconds from now, not the default 1800.
		$this->assertGreaterThan( time(), $lock );
		$this->assertLessThanOrEqual( time() + 60, $lock );

		remove_all_filters( 'feed_consumer_lock_duration' );
	}

	public function test_run_skips_when_locked() {
		Runner::acquire_lock( $this->rss_feed_id );

		$runner = new Runner( $this->rss_feed_id );
		$runner->run();

		// The last run meta should not be set because the run was skipped.
		$this->assertEmpty( get_post_meta( $this->rss_feed_id, Runner::LAST_RUN_META_KEY, true ) );

		Runner::release_lock( $this->rss_feed_id );
	}
}
