<?php
/**
 * Raw_Transformer class file
 *
 * @package feed-transformer
 */

namespace Feed_Consumer\Transformer;

/**
 * Raw Transformer
 *
 * Performs no transformation on the data. Converts JSON to an array.
 */
class Raw_Transformer extends Transformer {
	/**
	 * Retrieve the transformed data.
	 *
	 * @return array
	 */
	public function data(): array {
		$response = $this->extractor->data();

		if ( $response->is_json() ) {
			return $response->json();
		}

		return [
			$response->body(),
		];
	}
}
