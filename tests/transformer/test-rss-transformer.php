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

		$transformer->set_processor( $processor );
		$transformer->set_extractor( $extractor );

		$data = $transformer->data();

		$this->assertCount( 10, $data );

		$item = $data[0];

		$this->assertEquals( 'Brandon Fields', $item['byline'] );
		$this->assertEquals(
			'<!-- wp:paragraph --><p>One of the reasons I love Alley is because they provide opportunities for you to attend incredible conferences like RenderATL.</p><!-- /wp:paragraph -->

<!-- wp:paragraph --><p>The post <a rel="nofollow" href="https://alley.com/news/a-renderatl-welcome-into-the-tech-world/"> A RenderATL Welcome into the Tech World</a> appeared first on <a rel="nofollow" href="https://alley.com">Alley</a>.</p><!-- /wp:paragraph -->',
			$item['post_content'],
		);
		$this->assertEquals( 'https://alley.com/?p=6191', $item['guid'] );
		$this->assertEquals( 'Example image description', $item['image_description'] );
		$this->assertEquals( 'Example Photo Credit', $item['image_credit'] );
		$this->assertEquals( 'https://alley.com/wp-content/uploads/2022/06/IMG_4825.jpeg?w=1024', $item['image'] );
		$this->assertEquals( 'https://alley.com/news/a-renderatl-welcome-into-the-tech-world/', $item['permalink'] );
		$this->assertEquals( 'A RenderATL Welcome into the Tech World', $item['post_title'] );
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

		$transformer->set_processor( $processor );
		$transformer->set_extractor( $extractor );

		$data = $transformer->data();

		$this->assertCount( 0, $data );
	}
}
