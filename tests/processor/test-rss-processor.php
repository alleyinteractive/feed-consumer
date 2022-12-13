<?php

namespace Feed_Consumer\Tests\Processor;

use Feed_Consumer\Loader\Post_Loader;
use Feed_Consumer\Processor\RSS_Processor;
use Feed_Consumer\Tests\Test_Case;
use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testing\Mock_Http_Response;

/**
 * @group processor
 */
class RSS_Processor_Test extends Test_Case {
	use Refresh_Database;

	public function test_loads_rss_data() {
		$this->fake_request(
			'https://alley.com/feed/',
			Mock_Http_Response::create()
				->with_header( 'Content-Type', 'application/rss+xml' )
				->with_body( file_get_contents( __DIR__ . '/../fixtures/rss-feed.xml' ) )
		);

		$processor = new RSS_Processor();

		$processor->settings(
			[
				'feed_url' => 'https://alley.com/feed/',
			]
		);

		// todo: finish
	}

	// public function test_handle_rss_feed_error() {}
}
