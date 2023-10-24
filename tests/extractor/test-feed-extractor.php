<?php
namespace Feed_Consumer\Tests\Extractor;

use Feed_Consumer\Extractor\Extractor_Exception;
use Feed_Consumer\Extractor\Feed_Extractor;
use Feed_Consumer\Tests\Test_Case;
use Mantle\Testing\Mock_Http_Response;

use function Mantle\Support\Helpers\tap;

/**
 * @group extractor
 */
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
				'extractor' => [
					'feed_url' => 'https://alley.com/feed/',
				],
			]
		);

		$extractor = tap(
			new Feed_Extractor(),
			fn ( Feed_Extractor $extractor ) => $extractor->set_processor( $processor ),
		)->run();

		$data = $extractor->data();

		$this->assertTrue( $data->is_xml() );
		$this->assertStringContainsString( '<channel>', $data->body() );
	}

	public function test_extract_feed_error() {
		$this->expectApplied( 'feed_consumer_extractor_error' )->once();

		$this->expectException( Extractor_Exception::class );
		$this->expectExceptionMessage( 'Failed to extract feed: https://alley.com/feed/' );

		$this->fake_request(
			'https://alley.com/feed/',
			Mock_Http_Response::create()->with_status( 404 )
		);

		$processor = $this->make_processor(
			[
				'extractor' => [
					'feed_url' => 'https://alley.com/feed/',
				],
			]
		);

		tap(
			new Feed_Extractor(),
			fn ( Feed_Extractor $extractor ) => $extractor->set_processor( $processor ),
		)->run();
	}

	public function test_extract_basic_auth() {
		$_SERVER['__did_auth'] = false;

		$this->fake_request(
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
				'extractor' => [
					'feed_url'      => 'https://alley.com/feed/',
					'feed_username' => 'username',
					'feed_password' => 'password',
				],
			]
		);

		$extractor = tap(
			new Feed_Extractor(),
			fn ( Feed_Extractor $extractor ) => $extractor->set_processor( $processor ),
		)->run();

		$data = $extractor->data();

		$this->assertTrue( $data->is_xml() );
		$this->assertTrue( $_SERVER['__did_auth'] );
	}
}
