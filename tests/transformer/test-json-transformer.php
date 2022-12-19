<?php
namespace Feed_Consumer\Tests\Transformer;

use Feed_Consumer\Tests\Test_Case;
use Feed_Consumer\Transformer\JSON_Transformer;
use Mantle\Testing\Mock_Http_Response;

/**
 * @group transformer
 */
class JSON_Transformer_Test extends Test_Case {
	public function test_json_transformation() {
		$processor = $this->make_processor(
			[
				'transformer' => [
					JSON_Transformer::PATH_ITEMS => '',
					JSON_Transformer::PATH_GUID => 'guid.rendered',
					JSON_Transformer::PATH_TITLE => 'title.rendered',
					JSON_Transformer::PATH_PERMALINK => 'link',
					JSON_Transformer::PATH_CONTENT => 'content.rendered',
					JSON_Transformer::PATH_EXCERPT => 'excerpt.rendered',
				],
			],
		);

		$extractor = $this->make_extractor(
			Mock_Http_Response::create()
				->with_json( file_get_contents( __DIR__ . '/../fixtures/json-feed.json' ) ),
			$processor,
		);

		$transformer = new JSON_Transformer( $processor, $extractor );

		$transformer->set_processor( $processor );
		$transformer->set_extractor( $extractor );

		$data = $transformer->data();

		$this->assertCount( 10, $data );

		$item = $data[0];

		$this->assertNotEmpty( $item['post_content'] );
		$this->assertStringStartsWith( '<!-- wp:html --><figure class="wp-block-video wp-blo', $item['post_content'] );
		$this->assertEquals( 'https://alley.com/?p=6191', $item['guid'] );
		$this->assertEquals( 'https://alley.com/news/a-renderatl-welcome-into-the-tech-world/', $item['permalink'] );
		$this->assertEquals( 'A RenderATL Welcome into the Tech World', $item['post_title'] );
	}

	public function test_json_transformation_error() {
		$processor = $this->make_processor();

		$extractor = $this->make_extractor(
			Mock_Http_Response::create()
				->with_json( substr( file_get_contents( __DIR__ . '/../fixtures/json-feed.json' ), 0, 100 ) ),
			$processor,
		);

		$transformer = new JSON_Transformer( $processor, $extractor );

		$transformer->set_processor( $processor );
		$transformer->set_extractor( $extractor );

		$data = $transformer->data();

		$this->assertCount( 0, $data );
	}
}
