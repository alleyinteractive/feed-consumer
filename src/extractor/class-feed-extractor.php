<?php
/**
 * Feed_Extraction class file
 *
 * @package feed-consumer
 */

namespace Feed_Consumer\Extractor;

use Feed_Consumer\Contracts\Processor;
use Feed_Consumer\Contracts\With_Setting_Fields;
use Mantle\Http_Client\Pending_Request;
use Mantle\Http_Client\Response;
use RuntimeException;

/**
 * Feed Extractor
 *
 * Used to fetch common feeds and extract the data. Supports basic HTTP
 * authentication.
 */
class Feed_Extractor extends Extractor implements With_Setting_Fields {
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
	public function setting_fields(): array {
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

		$settings = $this->processor->get_settings()['extractor'] ?? [];

		// Set the username and password if provided.
		if ( ! empty( $settings[ static::SETTING_USERNAME ] ) && ! empty( $settings[ static::SETTING_PASSWORD ] ) ) {
			$request->with_basic_auth( $settings[ static::SETTING_USERNAME ], $settings[ static::SETTING_PASSWORD ] );
		}

		/**
		 * Fires before the feed is fetched.
		 *
		 * @param \Mantle\Http_Client\Pending_Request $request   Request object.
		 * @param Processor                           $processor Processor instance.
		 * @param array                               $settings  Settings for the processor.
		 */
		do_action( 'feed_consumer_pre_feed_fetch', $request, $this->processor, $settings );

		$this->response = $request->get( $settings[ static::SETTING_FEED_URL ] );

		/**
		 * Fires after the feed is fetched.
		 *
		 * @param \Mantle\Http_Client\Response $response  Response object.
		 * @param Processor                    $processor Processor instance.
		 * @param array                        $settings  Settings for the processor.
		 */
		do_action( 'feed_consumer_feed_fetch', $this->response, $this->processor, $settings );

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
}
