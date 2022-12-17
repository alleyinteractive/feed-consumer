<?php
/**
 * Feed_Extraction class file
 *
 * @package feed-consumer
 */

namespace Feed_Consumer\Extractor;

use Feed_Consumer\Contracts\Processor;
use Feed_Consumer\Contracts\With_Settings;
use Mantle\Http_Client\Pending_Request;
use Mantle\Http_Client\Response;
use RuntimeException;

/**
 * Feed Extractor
 *
 * Used to fetch common feeds and extract the data. Supports basic HTTP
 * authentication.
 */
class Feed_Extractor extends Extractor implements With_Settings {
	/**
	 * Setting for the feed URL.
	 *
	 * @var string
	 */
	public const SETTING_FEED_URL = 'feed_url';

	/**
	 * Setting for the username.
	 *
	 * @var string
	 */
	public const SETTING_USERNAME = 'feed_username';

	/**
	 * Setting for the password.
	 *
	 * @var string
	 */
	public const SETTING_PASSWORD = 'feed_password';

	/**
	 * Response for the feed.
	 *
	 * @var \Mantle\Http_Client\Response
	 */
	protected $response;

	/**
	 * Settings to register.
	 */
	public function settings(): array {
		return [
			static::SETTING_FEED_URL => new \Fieldmanager_TextField( __( 'Feed URL', 'feed-consumer' ) ),
			static::SETTING_USERNAME => new \Fieldmanager_TextField( __( 'Username (optional)', 'feed-consumer' ) ),
			static::SETTING_PASSWORD => new \Fieldmanager_TextField( __( 'Password (optional)', 'feed-consumer' ) ),
		];
	}

	/**
	 * Extract the data.
	 *
	 * @throws RuntimeException Thrown if the feed URL is not set.
	 * @throws Extractor_Exception Thrown if the feed request fails.
	 *
	 * @return static
	 */
	public function run(): static {
		$request = new Pending_Request();

		if ( ! $this->processor ) {
			throw new RuntimeException( 'Processor not set.' );
		}

		$settings = $this->processor->settings();

		// Set the username and password if provided.
		if ( ! empty( $settings[ static::SETTING_USERNAME ] ) && ! empty( $settings[ static::SETTING_PASSWORD ] ) ) {
			$request->with_basic_auth( $settings[ static::SETTING_USERNAME ], $settings[ static::SETTING_PASSWORD ] );
		}

		$this->response = $request->get( $settings[ static::SETTING_FEED_URL ] );

		if ( ! $this->response->ok() ) {
			$this->handle_error( $this->response );

			throw new Extractor_Exception( 'Failed to extract feed: ' . $settings[ static::SETTING_FEED_URL ], $this->response );
		}

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
	 * Getter for the cursor for the extractor.
	 *
	 * @return string|null Cursor if set, null otherwise.
	 */
	public function cursor(): ?string {
		return null;
	}
}
