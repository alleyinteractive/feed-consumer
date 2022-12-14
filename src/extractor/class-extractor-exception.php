<?php
/**
 * Extractor_Exception class file
 *
 * @package feed-consumer
 */

namespace Feed_Consumer\Extractor;

use Mantle\Http_Client\Response;
use RuntimeException;

/**
 * Extractor Exception
 */
class Extractor_Exception extends RuntimeException {
	/**
	 * Constructor.
	 *
	 * @param string   $message Exception message.
	 * @param Response $response Response object.
	 */
	public function __construct( string $message, public Response $response ) {
		parent::__construct( $message, $response->status() );
	}
}
