<?php
namespace Feed_Consumer\Tests;

use Feed_Consumer\Contracts\Extractor;
use Feed_Consumer\Contracts\Processor as Processor_Contract;
use Feed_Consumer\Contracts\Transformer;
use Feed_Consumer\Loader\Post_Loader;
use Feed_Consumer\Processor\Processor;
use Mantle\Http_Client\Response;
use Mantle\Testing\Concerns\With_Faker;
use Mantle\Testing\Mock_Http_Response;
use Mantle\Testkit\Test_Case as Testkit;

/**
 * Feed Consumer Base Test Case
 */
abstract class Test_Case extends Testkit {
	use With_Faker;

	public function setUp(): void {
		parent::setUp();

		$this->prevent_stray_requests();
	}

	protected function make_processor( array $settings = [] ): Processor {
		$instance = new class() extends Processor {
			public function name(): string {
				return 'Test Processor';
			}
		};

		$instance->settings( $settings );

		return $instance;
	}

	/**
	 * Create a controllable instance of a extractor.
	 *
	 * @param Mock_Http_Response|Response $response
	 * @param Processor                   $processor
	 * @return Extractor
	 */
	protected function make_extractor( Mock_Http_Response|Response $response, Processor $processor = null ): Extractor {
		if ( $response instanceof Mock_Http_Response ) {
			$response = new Response( $response->to_array() );
		}

		return new class( $processor ?: $this->make_processor(), $response ) extends \Feed_Consumer\Extractor\Extractor {
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
		};
	}

	/**
	 * Create a controllable instance of a transformer.
	 *
	 * @param mixed     $data
	 * @param Processor $processor
	 * @param Extractor $extractor
	 * @return Transformer
	 */
	protected function make_transformer(
		mixed $data,
		Processor $processor = null,
		Extractor $extractor = null
	): Transformer {
		$processor ??= $this->make_processor();

		return new class(
			$data,
			$processor,
			$extractor ?: $this->make_extractor( Mock_Http_Response::create(), $processor )
		) extends \Feed_Consumer\Transformer\Transformer {
			public function __construct( public mixed $data, protected Processor_Contract $processor, protected Extractor $extractor ) {}

			public function data(): array {
				return $this->data;
			}
		};
	}

	protected function make_loader( mixed $data, array $settings = [] ): Post_Loader {
		$processor = $this->make_processor( $settings );

		$loader = new Post_Loader();

		$loader->processor( $processor );

		$loader->transformer(
			$this->make_transformer( $data, $processor ),
		);

		return $loader;
	}
}
