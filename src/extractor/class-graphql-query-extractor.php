<?php
/**
 * Graphql_Query_Extractor class file
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
 * Graphql Query Extractor
 *
 * Used to query a Graphql Endpoint and extract the data. Supports basic HTTP
 * authentication.
 */
class Graphql_Query_Extractor extends Extractor implements With_Setting_Fields {
	/**
	 * Setting for the feed URL.
	 *
	 * @var string
	 */
	public const SETTING_ENDPOINT = 'feed_url';

	/**
	 * Setting for the username.
	 *
	 * @var string
	 */
	public const SETTING_CLIENT_ID = 'feed_username';

	/**
	 * Setting for the password.
	 *
	 * @var string
	 */
	public const SETTING_CLIENT_SECRET = 'feed_password';

	/**
	 * Setting for the query.
	 *
	 * @var string
	 */
	public const SETTING_QUERY = 'feed_query';

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
			static::SETTING_ENDPOINT      => new \Fieldmanager_TextField( __( 'Query Endpoint', 'feed-consumer' ) ),
			static::SETTING_CLIENT_ID     => new \Fieldmanager_TextField( __( 'Client ID (optional)', 'feed-consumer' ) ),
			static::SETTING_CLIENT_SECRET => new \Fieldmanager_TextField( __( 'Client Secret (optional)', 'feed-consumer' ) ),
			static::SETTING_QUERY         => new \Fieldmanager_TextArea( __( 'GraphQL Query', 'feed-consumer' ) ),
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
		if ( ! empty( $settings[ static::SETTING_CLIENT_ID ] ) && ! empty( $settings[ static::SETTING_CLIENT_SECRET ] ) ) {
			$request->with_basic_auth( $settings[ static::SETTING_CLIENT_ID ], $settings[ static::SETTING_CLIENT_SECRET ] );
		}

		/**
		 * Fires before the feed is fetched.
		 *
		 * @param \Mantle\Http_Client\Pending_Request $request   Request object.
		 * @param Processor                           $processor Processor instance.
		 * @param array                               $settings  Settings for the processor.
		 */
		do_action( 'feed_consumer_pre_feed_fetch', $request, $this->processor, $settings );

		$this->response = $request->get( $settings[ static::SETTING_ENDPOINT ] );

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

			throw new Extractor_Exception(
				esc_html( 'Failed to extract feed: ' . $settings[ static::SETTING_ENDPOINT ] ),
				$this->response, // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
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
