<?php
/**
 * Option_Loader class file
 *
 * @package feed-consumer
 */

namespace Feed_Consumer\Loader;

use Feed_Consumer\Contracts\With_Setting_Fields;
use Fieldmanager_TextField;
use InvalidArgumentException;

/**
 * Option Loader
 *
 * Loader that takes transformer data and loads it into an option.
 */
class Option_Loader extends Loader implements With_Setting_Fields {
	/**
	 * Setting name for the option name to load to.
	 *
	 * @var string
	 */
	public const OPTION_NAME = 'option_name';

	/**
	 * Constructor.
	 *
	 * @param string|null $option_name Option name to load into, optional.
	 */
	public function __construct( public ?string $option_name = null ) {
	}

	/**
	 * Load the data to the option.
	 *
	 * @throws InvalidArgumentException If the option name is not set.
	 *
	 * @return \WP_Post
	 */
	public function load(): mixed {
		$data = $this->transformer->data();

		$option_name = $this->option_name ?? ( $this->get_processor()->get_settings()['loader'][ static::OPTION_NAME ] ?? null );

		if ( empty( $option_name ) ) {
			throw new InvalidArgumentException( 'Option name is required.' );
		}

		return update_option( $option_name, $data );
	}

	/**
	 * Settings to register.
	 */
	public function setting_fields(): array {
		if ( ! empty( $this->option_name ) ) {
			return [];
		}


		return [
			static::OPTION_NAME => new Fieldmanager_TextField( __( 'Option Name', 'feed-consumer' ) ),
		];
	}
}
