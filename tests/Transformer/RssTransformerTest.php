<?php
namespace Feed_Consumer\Tests\Transformer;

use Feed_Consumer\Tests\TestCase;
use Feed_Consumer\Transformer\RSS_Transformer;
use Mantle\Testing\Mock_Http_Response;
use PHPUnit\Framework\Attributes\Group;

#[Group('transformer')]
class RssTransformerTest extends TestCase {
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
		$this->assertStringStartsWith(
			'<!-- wp:paragraph --><p>One of the reasons I love Alley is because they provide opportunities for you to attend incredible conferences like <a href="https://www.renderatl.com/about-us">RenderATL</a>, my first technology conference in Atlanta, GA. Render was a four-day conference featuring 50+ expert speakers in tech covering software engineering practices, web3, engineering leadership, accessibility practices, and more. Its attendees and speakers were an intersectional of race, gender and age. It exceeded my expectations, the session speakers were unmatched and the food embodied southern hospitality. The only problem I had was that I could not attend every session—I’m excited to share my favorite speakers so you can follow them and make sure not to miss this next year! </p><!-- /wp:paragraph -->',
			(string) $item['post_content'],
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

	public function test_rss_transformer_with_cursor() {
		$processor = $this->make_processor();

		// Setting the cursor relative to the 3rd item in the feed (it should only include 1-2).
		$processor->set_cursor( 'Fri, 08 Apr 2022 19:18:04 +0000' );

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

		$this->assertCount( 2, $data );
	}
}
