<?php

namespace Feed_Consumer\Tests\Loader;

use Feed_Consumer\Loader\Loader;
use Feed_Consumer\Loader\Option_Loader;
use Feed_Consumer\Loader\Post_Loader;
use Feed_Consumer\Tests\Test_Case;
use InvalidArgumentException;
use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testing\Mock_Http_Response;

/**
 * @group loader
 */
class Option_Loader_Test extends Test_Case {
	use Refresh_Database;

	public function test_load_from_constructor() {
		$loader = $this->make_option_loader(
			[
				'content' => 'example content',
			],
			[],
			'example_option',
		);

		$loader->load();

		$this->assertNotEmpty( get_option( 'example_option' ) );
		$this->assertEquals( [ 'content' => 'example content' ], get_option( 'example_option' ) );
	}

	public function test_load_from_settings() {
		$loader = $this->make_option_loader(
			[
				'content' => 'example content',
			],
			[
				'loader' => [
					Option_Loader::OPTION_NAME => 'setting_option',
				],
			],
		);

		$loader->load();

		$this->assertNotEmpty( get_option( 'setting_option' ) );
		$this->assertEquals( [ 'content' => 'example content' ], get_option( 'setting_option' ) );
	}

	public function test_load_invalid_argument() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Option name is required.' );

		$loader = $this->make_option_loader(
			[
				'content' => 'example content',
			],
		);

		$loader->load();
	}

	protected function make_option_loader( mixed $data, array $settings = [], string $option_name = null ): Option_Loader {
		$processor = $this->make_processor( $settings );

		$loader = new Option_Loader( $option_name );

		$loader->set_processor( $processor );

		$loader->set_transformer(
			$this->make_transformer( $data, $processor ),
		);

		return $loader;
	}
}
