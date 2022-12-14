<?php

namespace Feed_Consumer\Tests\Processor;

use Feed_Consumer\Loader\Post_Loader;
use Feed_Consumer\Processor\RSS_Processor;
use Feed_Consumer\Runner;
use Feed_Consumer\Settings;
use Feed_Consumer\Tests\Test_Case;
use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testing\Mock_Http_Response;

/**
 * @group processor
 */
class RSS_Processor_Test extends Test_Case {
	use Refresh_Database;

	public function test_load_rss_feed() {
		$this->expectApplied( 'feed_consumer_run_complete' )->once();

		$this->fake_request(
			'https://alley.com/feed/',
			Mock_Http_Response::create()
				->with_header( 'Content-Type', 'application/rss+xml' )
				->with_body( file_get_contents( __DIR__ . '/../fixtures/rss-feed.xml' ) )
		);

		$this->assertPostDoesNotExists(
			[
				'post_title'  => 'A RenderATL Welcome into the Tech World',
				'post_status' => 'publish',
			],
		);

		// Create the RSS feed.
		$feed_id = static::factory()->post
			->with_meta(
				[
					Settings::SETTINGS_META_KEY => [
						'processor' => Settings::escape_setting_name( RSS_Processor::class ),
						Settings::escape_setting_name( RSS_Processor::class ) => [
							'feed_url' => 'https://alley.com/feed/',
							'loader'   => [
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

		Runner::run_scheduled( $feed_id );

		$this->assertPostExists(
			[
				'post_title'  => 'A RenderATL Welcome into the Tech World',
				'post_status' => 'publish',
			],
		);

		$this->assertNotEmpty( get_post_meta( $feed_id, Runner::LAST_RUN_META_KEY, true ) );

		$this->assertInCronQueue( Runner::CRON_HOOK, [ $feed_id ] );
	}

	public function test_handle_rss_feed_error() {
		$this->expectApplied( 'feed_consumer_run_complete' )->never();

		$this->fake_request(
			'https://alley.com/feed/',
			Mock_Http_Response::create()->with_status( 404 )
		);

		// Create the RSS feed.
		$feed_id = static::factory()->post
			->with_meta(
				[
					Settings::SETTINGS_META_KEY => [
						'processor' => Settings::escape_setting_name( RSS_Processor::class ),
						Settings::escape_setting_name( RSS_Processor::class ) => [
							'feed_url' => 'https://alley.com/feed/',
						],
					],
				]
			)
			->create(
				[
					'post_type' => Settings::POST_TYPE,
				]
			);

		Runner::run_scheduled( $feed_id );

		$this->assertInCronQueue( Runner::CRON_HOOK, [ $feed_id ] );
	}
}
