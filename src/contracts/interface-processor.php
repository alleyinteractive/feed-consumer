<?php
/**
 * Processor interface file.
 *
 * @package feed-consumer
 */

namespace Feed_Consumer\Contracts;

use Psr\Log\LoggerInterface;

/**
 * Processor Interface
 */
interface Processor {
	/**
	 * Getter for the name of the processor.
	 *
	 * @return string
	 */
	public function name(): string;

	/**
	 * Retrieve the stored settings for the processor.
	 *
	 * @param array|null $settings The settings to set, optional.
	 * @return array
	 */
	public function settings( ?array $settings = null ): array;

	/**
	 * Retrieve or set the logger for the processor.
	 *
	 * @param LoggerInterface $logger The logger to set, optional.
	 * @return LoggerInterface
	 */
	public function logger( ?LoggerInterface $logger = null ): ?LoggerInterface;

	/**
	 * Retrieve or set the extractors for the processor.
	 *
	 * @param Extractor $extractor The extractor to set, optional.
	 * @return Extractor|null
	 */
	public function extractor( ?Extractor $extractor = null ): ?Extractor;

	/**
	 * Retrieve or set the transformer for the processor.
	 *
	 * @param Transformer $transformers The transformers to set, optional.
	 * @return Transformer|null
	 */
	public function transformer( ?Transformer $transformers = null ): ?Transformer;

	/**
	 * Retrieve or set the loaders for the processor.
	 *
	 * @param Loader $loaders The loaders to set, optional.
	 * @return Loader|null
	 */
	public function loader( ?Loader $loaders = null ): ?Loader;

	/**
	 * Retrieve or add loader middleware.
	 *
	 * Middleware can be used to modify the content before and/or after it is
	 * loaded to the site.
	 *
	 * @param callable $middleware The middleware to add.
	 * @return callable[]
	 */
	public function middleware( ?callable $middleware = null ): array;

	/**
	 * Clear the middleware stack.
	 *
	 * @return void
	 */
	public function clear_middleware(): void;

	/**
	 * Getter for the interval to run the processor.
	 *
	 * @return int
	 */
	public function frequency(): int;
}
