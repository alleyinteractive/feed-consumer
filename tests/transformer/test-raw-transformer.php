<?php
namespace Feed_Consumer\Tests\Transformer;

use Feed_Consumer\Tests\Test_Case;
use Feed_Consumer\Transformer\Raw_Transformer;
use Mantle\Testing\Mock_Http_Response;

/**
 * @group transformer
 */
class Raw_Transformer_Test extends Test_Case {
	public function test_string_data() {
		$processor = $this->make_processor();

		$extractor = $this->make_extractor(
			Mock_Http_Response::create()->with_body( 'Example Body' ),
			$processor,
		);

		$transformer = ( new Raw_Transformer() )
			->set_processor( $processor )
			->set_extractor( $extractor );

		$data = $transformer->data();

		$this->assertEquals( [ 'Example Body' ], $data );
	}

	public function test_json_data() {
		$processor = $this->make_processor();

		$extractor = $this->make_extractor(
			Mock_Http_Response::create()->with_json( [ 1, 2, 3 ] ),
			$processor,
		);

		$transformer = ( new Raw_Transformer() )
			->set_processor( $processor )
			->set_extractor( $extractor );

		$data = $transformer->data();

		$this->assertEquals( [ 1, 2, 3 ], $data );
	}
}
