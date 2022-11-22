<?php
/**
 * Processor class file
 *
 * @package feed-consumer
 */

namespace Feed_Consumer\Processor;

use Feed_Consumer\Contracts\Processor as Contract;
use Feed_Consumer\Contracts\Extractor;
use Feed_Consumer\Contracts\Transformer;
use Feed_Consumer\Contracts\Loader;
use Psr\Log\LoggerInterface;

/**
 * Abstract Processor
 */
abstract class Processor implements Contract {
	/**
	 * Extractor instance.
	 *
	 * @var Extractor|null
	 */
	protected ?Extractor $extractor = null;

	/**
	 * Transformer instance.
	 *
	 * @var Transformer|null
	 */
	protected ?Transformer $transformer = null;

	/**
	 * Loader instance.
	 *
	 * @var Loader|null
	 */
	protected ?Loader $loader = null;

	/**
	 * Logger instance.
	 *
	 * @var LoggerInterface|null
	 */
	protected ?LoggerInterface $logger = null;

	/**
	 * Middleware for loading.
	 *
	 * @var callable[]
	 */
	protected array $middleware = [];

	/**
	 * Settings for the processor.
	 *
	 * @var array
	 */
	protected array $settings = [];

	/**
	 * Retrieve the stored settings for the processor.
	 *
	 * @param array|null $settings The settings to set, optional.
	 * @return array
	 */
	public function settings( ?array $settings = null ): array {
		if ( $settings ) {
			$this->settings = $settings;
		}

		return $this->settings;
	}

	/**
	 * Retrieve or set the logger for the processor.
	 *
	 * @param LoggerInterface $logger The logger to set, optional.
	 * @return LoggerInterface
	 */
	public function logger( ?LoggerInterface $logger = null ): ?LoggerInterface {
		if ( $logger ) {
			$this->logger = $logger;
		}

		return $this->logger;
	}

	/**
	 * Retrieve or set the extractors for the processor.
	 *
	 * @param Extractor $extractor The extractor to set, optional.
	 * @return Extractor|null
	 */
	public function extractor( ?Extractor $extractor = null ): ?Extractor {
		if ( $extractor ) {
			$this->extractor = $extractor;
		}

		return $this->extractor;
	}


	/**
	 * Retrieve or set the transformer for the processor.
	 *
	 * @param Transformer $transformer The transformer to set, optional.
	 * @return Transformer|null
	 */
	public function transformer( ?Transformer $transformer = null ): ?Transformer {
		if ( $transformer ) {
			$this->transformer = $transformer;
		}

		return $this->transformer;
	}

	/**
	 * Retrieve or set the loaders for the processor.
	 *
	 * @param Loader $loader The loader to set, optional.
	 * @return Loader|null
	 */
	public function loader( ?Loader $loader = null ): ?Loader {
		if ( $loader ) {
			$this->loader = $loader;
		}

		return $this->loader;
	}

	/**
	 * Add loader middleware.
	 *
	 * Middleware can be used to modify the content before and/or after it is
	 * loaded to the site.
	 *
	 * @param callable $middleware The middleware to add.
	 */
	public function middleware( callable $middleware ): void {
		$this->middleware[] = $middleware;
	}

	/**
	 * Getter for the interval to run the processor.
	 *
	 * @return int|\DateInterval|null
	 */
	public function frequency(): int|\DateInterval|null {
		return HOUR_IN_SECONDS;
	}
}
