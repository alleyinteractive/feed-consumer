<?php
/**
 * Processors class file
 *
 * @package feed-consumer
 */

namespace Feed_Consumer;

use Mantle\Support\Traits\Singleton;

use function Mantle\Support\Helpers\collect;

/**
 * Processors Manager
 */
class Processors {
	use Singleton;

	/**
	 * Processors.
	 *
	 * @var array
	 */
	protected array $processors = [
		\Feed_Consumer\Processor\JSON_Processor::class,
		\Feed_Consumer\Processor\RSS_Processor::class,
		\Feed_Consumer\Processor\XML_Processor::class,
	];

	/**
	 * Constructor.
	 */
	protected function __construct() {
		add_action( 'init', [ $this, 'on_init' ] );
	}

	/**
	 * On init.
	 */
	public function on_init() {
		/**
		 * Filter the supported processor classes.
		 *
		 * @var string[]
		 */
		$this->processors = apply_filters( 'feed_consumer_processors', $this->processors );
	}

	/**
	 * Retrieve or set the processors.
	 *
	 * Returns an array of processors with the processor setting name as the key
	 * and the processor class name as the value.
	 *
	 * @param array<int, string>|null $processors Processors to set, optional.
	 * @return array<string, string>
	 */
	public function processors( ?array $processors = null ): array {
		if ( ! is_null( $processors ) ) {
			$this->processors = $processors;
		}

		return collect( $this->processors )
			->map_with_keys( fn ( $class ) => [ Settings::escape_setting_name( $class ) => $class ] )
			->all();
	}
}
