<?php
/**
 * RSS_Transformer class file
 *
 * @package feed-transformer
 */

namespace Feed_Consumer\Transformer;

use Feed_Consumer\Contracts\With_Presets;

/**
 * RSS Transformer
 */
class RSS_Transformer extends XML_Transformer implements With_Presets {
	/**
	 * XML presets.
	 *
	 * @return array
	 */
	public function presets(): array {
		return [
			static::PATH_ITEMS     => '/rss/channel/item',
			static::PATH_GUID      => 'guid',
			static::PATH_TITLE     => 'title',
			static::PATH_PERMALINK => 'link',
			static::PATH_CONTENT   => 'description',
			static::PATH_BYLINE    => [ 'dc:creator', 'author' ],
		];
	}
}
