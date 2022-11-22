<?php
namespace Feed_Consumer\Tests\Extractor;

use Feed_Consumer\Extractor\Feed_Extractor;
use Feed_Consumer\Tests\Test_Case;
use Mantle\Testing\Mock_Http_Response;

class Feed_Extractor_Test extends Test_Case {
	public function test_extract_feed() {
		$this->fake_request(
			'https://alley.com/feed/',
			Mock_Http_Response::create()
				->with_header( 'Content-Type', 'application/rss+xml' )
				->with_body( file_get_contents( __DIR__ . '/../fixtures/rss-feed.xml' ) )
		);

		$processor = $this->make_processor(
			[
				'feed_url' => 'https://alley.com/feed/',
			]
		);

		$extractor = ( new Feed_Extractor( $processor ) )->run();
		$data      = $extractor->data();

		$this->assertTrue( $data->is_xml() );
		$this->assertStringContainsString( '<channel>', $data->body() );
		$this->assertEmpty( $extractor->cursor() );
	}

	public function test_extract_feed_error() {
		$this->fake_request(
			'https://alley.com/feed/',
			Mock_Http_Response::create()->with_status( 404 )
		);

		$processor = $this->make_processor(
			[
				'feed_url' => 'https://alley.com/feed/',
			]
		);

		$extractor = ( new Feed_Extractor( $processor ) )->run();
		$data      = $extractor->data();

		$this->assertEmpty( $data->body() );
		$this->assertEmpty( $extractor->cursor() );
	}

	public function test_extract_basic_auth() {
		$_SERVER['__did_auth'] = false;

		$this->fake_request(
			'https://alley.com/feed/',
			function ( string $url, array $args ) {
				if ( ! empty( $args['headers']['Authorization'] ) ) {
					$_SERVER['__did_auth'] = 'Basic ' . base64_encode( 'username:password' ) === $args['headers']['Authorization'];
				}

				return Mock_Http_Response::create()
					->with_header( 'Content-Type', 'application/rss+xml' )
					->with_body( file_get_contents( __DIR__ . '/../fixtures/rss-feed.xml' ) );
			}
		);

		$processor = $this->make_processor(
			[
				'feed_url'      => 'https://alley.com/feed/',
				'feed_username' => 'username',
				'feed_password' => 'password',
			]
		);

		$data = ( new Feed_Extractor( $processor ) )->run()->data();

		$this->assertTrue( $data->is_xml() );
		$this->assertTrue( $_SERVER['__did_auth'] );
	}
}
