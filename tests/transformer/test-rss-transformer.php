<?php
namespace Feed_Consumer\Tests\Transformer;

use Feed_Consumer\Tests\Test_Case;
use Feed_Consumer\Transformer\RSS_Transformer;
use Mantle\Testing\Mock_Http_Response;

/**
 * @group transformer
 */
class RSS_Transformer_Test extends Test_Case {
	public function test_rss_transformation() {
		$processor = $this->make_processor();

		$extractor = $this->make_extractor(
			Mock_Http_Response::create()
				->with_header( 'Content-Type', 'application/rss+xml' )
				->with_body( file_get_contents( __DIR__ . '/../fixtures/rss-feed.xml' ) ),
			$processor,
		);

		$transformer = new RSS_Transformer( $processor, $extractor );

		$transformer->processor( $processor );
		$transformer->extractor( $extractor );

		$data = $transformer->data();

		$this->assertCount( 10, $data );

		$item = $data[0];

		$this->assertEquals( 'Brandon Fields', $item['byline'] );
		$this->assertEquals( '<p>One of the reasons I love Alley is because they provide opportunities for you to attend incredible conferences like RenderATL.</p><p>The post <a rel="nofollow" href="https://alley.com/news/a-renderatl-welcome-into-the-tech-world/"> A RenderATL Welcome into the Tech World</a> appeared first on <a rel="nofollow" href="https://alley.com">Alley</a>.</p>', $item['content'] );
		$this->assertEquals( 'https://alley.com/?p=6191', $item['guid'] );
		$this->assertEquals( null, $item['image_caption'] );
		$this->assertEquals( null, $item['image_credit'] );
		$this->assertEquals( null, $item['image'] );
		$this->assertEquals( 'https://alley.com/news/a-renderatl-welcome-into-the-tech-world/', $item['permalink'] );
		$this->assertEquals( 'A RenderATL Welcome into the Tech World', $item['title'] );
	}

	public function test_rss_transformation_error() {
		$processor = $this->make_processor();

		$extractor = $this->make_extractor(
			Mock_Http_Response::create()
				->with_header( 'Content-Type', 'application/rss+xml' )
				->with_body( substr( file_get_contents( __DIR__ . '/../fixtures/rss-feed.xml' ), 0, 100 ) ),
			$processor,
		);

		$transformer = new RSS_Transformer( $processor, $extractor );

		$transformer->processor( $processor );
		$transformer->extractor( $extractor );

		$data = $transformer->data();

		$this->assertCount( 0, $data );
	}
}
