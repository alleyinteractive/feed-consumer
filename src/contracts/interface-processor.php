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
	 * @return array
	 */
	public function get_settings(): array;

	/**
	 * Set the settings for the processor.
	 *
	 * @param array|null $settings The settings to set, optional.
	 * @return static
	 */
	public function set_settings( ?array $settings = null ): static;

	/**
	 * Retrieve the logger for the processor.
	 *
	 * @return LoggerInterface|null
	 */
	public function get_logger(): ?LoggerInterface;

	/**
	 * Set the logger for the processor.
	 *
	 * @param LoggerInterface|null $logger The logger to set.
	 * @return static
	 */
	public function set_logger( ?LoggerInterface $logger = null ): static;

	/**
	 * Retrieve the extractors for the processor.
	 *
	 * @return Extractor|null
	 */
	public function get_extractor(): ?Extractor;

	/**
	 * Set the extractors for the processor.
	 *
	 * @param Extractor|null $extractor The extractor to set.
	 * @return static
	 */
	public function set_extractor( ?Extractor $extractor = null ): static;

	/**
	 * Retrieve the transformer for the processor.
	 *
	 * @return Transformer|null
	 */
	public function get_transformer(): ?Transformer;

	/**
	 * Set the transformer for the processor.
	 *
	 * @param Transformer $transformer|null The transformers to set.
	 * @return static
	 */
	public function set_transformer( ?Transformer $transformer = null ): static;

	/**
	 * Retrieve the loaders for the processor.
	 *
	 * @return Loader|null
	 */
	public function get_loader(): ?Loader;

	/**
	 * Set the loaders for the processor.
	 *
	 * @param Loader $loader|null The loader to set.
	 * @return static
	 */
	public function set_loader( ?Loader $loader = null ): static;

	/**
	 * Retrieve loader middleware.
	 *
	 * Middleware can be used to modify the content before and/or after it is
	 * loaded to the site.
	 *
	 * @return callable[]
	 */
	public function get_middleware(): array;

	/**
	 * Retrieve or add loader middleware.
	 *
	 * Middleware can be used to modify the content before and/or after it is
	 * loaded to the site.
	 *
	 * @param callable $middleware The middleware to add.
	 * @return static
	 */
	public function set_middleware( ?array $middleware = null ): static;

	/**
	 * Push middleware onto the loader stack
	 *
	 * @param callable $middleware The middleware to add.
	 * @return static
	 */
	public function push_middleware( callable $middleware ): static;

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
