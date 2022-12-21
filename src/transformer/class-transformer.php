<?php
/**
 * Transformer class file
 *
 * @package feed-transformer
 */

namespace Feed_Consumer\Transformer;

use Feed_Consumer\Contracts\Extractor;
use Feed_Consumer\Contracts\Processor;
use Feed_Consumer\Contracts\Transformer as Contract;
use Feed_Consumer\Contracts\With_Extractor;
use Feed_Consumer\Contracts\With_Processor;

/**
 * Base Transformer
 */
abstract class Transformer implements With_Extractor, With_Processor, Contract {
	/**
	 * XPath key to the items.
	 *
	 * @var string
	 */
	public const PATH_ITEMS = 'path_items';

	/**
	 * XPath key to the item guid.
	 *
	 * @var string
	 */
	public const PATH_GUID = 'path_guid';

	/**
	 * XPath key to the item title.
	 *
	 * @var string
	 */
	public const PATH_TITLE = 'path_title';

	/**
	 * XPath key to the item link.
	 *
	 * @var string
	 */
	public const PATH_PERMALINK = 'path_permalink';

	/**
	 * XPath key to the item content.
	 *
	 * @var string
	 */
	public const PATH_CONTENT = 'path_content';

	/**
	 * XPath key to the item excerpt.
	 *
	 * @var string
	 */
	public const PATH_EXCERPT = 'path_excerpt';

	/**
	 * XPath key to the item byline.
	 *
	 * @var string
	 */
	public const PATH_BYLINE = 'path_byline';

	/**
	 * XPath key to the item image URL.
	 *
	 * @var string
	 */
	public const PATH_IMAGE = 'path_image';

	/**
	 * XPath key to the item image description.
	 *
	 * @var string
	 */
	public const PATH_IMAGE_DESCRIPTION = 'path_image_description';

	/**
	 * XPath key to the item image caption.
	 *
	 * @var string
	 */
	public const PATH_IMAGE_CAPTION = 'path_image_caption';

	/**
	 * XPath key to the item image credit.
	 *
	 * @var string
	 */
	public const PATH_IMAGE_CREDIT = 'path_image_credit';

	/**
	 * Settings key to not convert to Gutenberg blocks.
	 *
	 * @var string
	 */
	public const DONT_CONVERT_TO_BLOCKS = 'dont_convert_to_blocks';

	/**
	 * Processor instance.
	 *
	 * @var Processor|null
	 */
	protected ?Processor $processor;

	/**
	 * Extractor instance.
	 *
	 * @var Extractor|null
	 */
	protected ?Extractor $extractor;

	/**
	 * Retrieve the processor instance.
	 *
	 * @return Processor|null
	 */
	public function get_processor(): ?Processor {
		return $this->processor;
	}

	/**
	 * Set the processor instance.
	 *
	 * @param Processor $processor Processor instance to set.
	 * @return static
	 */
	public function set_processor( ?Processor $processor = null ): static {
		$this->processor = $processor;
		return $this;
	}

	/**
	 * Retrieve the extractor instance.
	 *
	 * @return Extractor|null
	 */
	public function get_extractor(): ?Extractor {
		return $this->extractor;
	}

	/**
	 * Set the extractor instance.
	 *
	 * @param Extractor $extractor Extractor instance to set.
	 * @return static
	 */
	public function set_extractor( ?Extractor $extractor = null ): static {
		$this->extractor = $extractor;
		return $this;
	}
}
