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
		/**
		 * Filters the RSS preset XPaths used to extract content from a RSS feed.
		 *
		 * @param array           $presets     The preset XPaths
		 * @param RSS_Transformer $transformer The RSS transformer
		 */
		return (array) apply_filters(
			'feed_consumer_rss_transformer_presets',
			[
				static::PATH_ITEMS             => '/rss/channel/item',
				static::PATH_GUID              => 'guid',
				static::PATH_TITLE             => 'title',
				static::PATH_PERMALINK         => 'link',
				static::PATH_CONTENT           => 'description',
				static::PATH_BYLINE            => [ 'dc:creator', 'author' ],
				static::PATH_IMAGE             => [ 'media:content/@url', 'media:thumbnail' ],
				static::PATH_IMAGE_DESCRIPTION => 'media:content/media:description',
				static::PATH_IMAGE_CAPTION     => 'media:content/media:text',
				static::PATH_IMAGE_CREDIT      => 'media:content/media:credit',
			],
			$this,
		);
	}
}
