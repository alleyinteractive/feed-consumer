<?php
/**
 * Post_Type class file
 *
 * @package feed-consumer
 */

namespace Feed_Consumer;

/**
 * Feed Consumer Post Type
 */
class Post_Type {
	/**
	 * Post type name.
	 *
	 * @var string
	 */
	public const NAME = 'feed_consumer';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_post_type' ] );
	}

	/**
	 * Register the post type.
	 */
	public function register_post_type() {
		register_post_type(
			static::NAME,
			[
				'label'              => __( 'Feeds', 'feed-consumer' ),
				'labels'             => [
					'add_new_item'               => __( 'Add New Feed', 'feed-consumer' ),
					'add_or_remove_items'        => __( 'Add or remove feeds', 'feed-consumer' ),
					'all_items'                  => __( 'All Feeds', 'feed-consumer' ),
					'choose_from_most_used'      => __( 'Choose from the most used feeds', 'feed-consumer' ),
					'edit_item'                  => __( 'Edit Feed', 'feed-consumer' ),
					'menu_name'                  => __( 'Feeds', 'feed-consumer' ),
					'name'                       => __( 'Feeds', 'feed-consumer' ),
					'new_item_name'              => __( 'New Feed Name', 'feed-consumer' ),
					'not_found'                  => __( 'No feeds found.', 'feed-consumer' ),
					'parent_item_colon'          => __( 'Parent Feed:', 'feed-consumer' ),
					'parent_item'                => __( 'Parent Feed', 'feed-consumer' ),
					'search_items'               => __( 'Search Feeds', 'feed-consumer' ),
					'separate_items_with_commas' => __( 'Separate feeds with commas', 'feed-consumer' ),
					'singular_name'              => __( 'Feed', 'feed-consumer' ),
					'update_item'                => __( 'Update Feed', 'feed-consumer' ),
				],
				'menu_icon'          => 'dashicons-rss',
				'public'             => true,
				'publicly_queryable' => false,
				'show_in_rest'       => true,
				'supports'           => [
					'title',
				],
				'show_ui'            => true,
			]
		);
	}
}
