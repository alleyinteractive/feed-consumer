<?php
/**
 * CLI class file
 *
 * @package feed-consumer
 */

namespace Feed_Consumer;

/**
 * WP-CLI Command for Feed Consumer
 */
class CLI {
	/**
	 * Trigger a single feed to run.
	 *
	 * ## OPTIONS
	 *
	 * <feed_id>
	 * : Feed ID to run.
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function run( array $args, array $assoc_args ) {
		[ $feed_id ] = $args;

		$feed = get_post( $feed_id );

		if ( empty( $feed ) ) {
			\WP_CLI::error( 'Feed not found.' );
		}

		if ( 'publish' !== $feed->post_status ) {
			\WP_CLI::error( 'Feed is not published.' );
		}

		$runner = new Runner( $feed->ID, function_exists( 'ai_logger' ) ? ai_logger() : null );

		$runner->run();

		return 0;
	}
}
