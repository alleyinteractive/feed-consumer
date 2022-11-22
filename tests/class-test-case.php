<?php
namespace Feed_Consumer\Tests;

use Feed_Consumer\Contracts\Extractor;
use Feed_Consumer\Contracts\Transformer;
use Feed_Consumer\Post_Type;
use Feed_Consumer\Processor\Processor;
use Mantle\Http_Client\Response;
use Mantle\Testing\Mock_Http_Response;
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
		$instance = new class extends Processor {
			public function name(): string {
				return 'Test Processor';
			}
		};

		$instance->settings( $settings );

		return $instance;
	}

	protected function make_extractor( Mock_Http_Response|Response $response, Processor $processor = null ): Extractor {
		if ( $response instanceof Mock_Http_Response ) {
			$response = new Response( $response->to_array() );
		}

		return new class ( $processor ?: $this->make_processor(), $response ) extends \Feed_Consumer\Extractor\Extractor {
			protected $response;

			/**
			 * Constructor.
			 *
			 * @param Processor $processor Data processor instance.
			 */
			public function __construct( Processor $processor, mixed $response ) {
				$this->processor = $processor;
				$this->response  = $response;
			}

			/**
			 * Extract the data.
			 *
			 * @return static
			 */
			public function run(): static {
				return $this;
			}

			/**
			 * Getter for the data from the extractor.
			 *
			 * @return Response
			 */
			public function data(): Response {
				return $this->response;
			}

			/**
			 * Cursor for the extractor.
			 *
			 * @return string|null Cursor if set, null otherwise.
			 */
			public function cursor(): ?string {
				return null;
			}
		};
	}
}
