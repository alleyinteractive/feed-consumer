<?php
namespace Feed_Consumer\Tests;

use Feed_Consumer\Post_Type;
use Feed_Consumer\Processor\Processor;
use Mantle\Testkit\Test_Case as TestkitTest_Case;

/**
 * Feed Consumer Base Test Case
 */
abstract class Test_Case extends TestkitTest_Case {
	public function setUp(): void {
		parent::setUp();

		$this->prevent_stray_requests();
	}

	public function make_processor( array $settings = [] ): Processor {
		$processor_id = static::factory()->post
			->with_meta(
				[
					'settings' => $settings,
				]
			)
			->create(
				[
					'post_type'   => Post_Type::NAME,
					'post_status' => 'publish',
				]
			);

		return new class( $processor_id ) extends Processor {
			public function name(): string {
				return 'Test Processor';
			}
		};
	}
}
