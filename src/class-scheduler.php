<?php
/**
 * Scheduler class file
 *
 * @package feed-consumer
 */

namespace Feed_Consumer;

use Mantle\Support\Traits\Singleton;

/**
 * Feed Scheduler
 *
 * Schedule the feeds to be processed.
 */
class Scheduler {
	use Singleton;

	/**
	 * Cron hook of the scheduler.
	 *
	 * @var string
	 */
	public const CRON_HOOK = 'feed_consumer_scheduler';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( static::CRON_HOOK, [ $this, 'run' ] );

		$this->schedule_next_run();
	}

	/**
	 * Schedule all feeds to be run.
	 */
	public function run() {
		$paged = 1;

		while ( true ) {
			$feed_ids = get_posts( // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.get_posts_get_posts
				[
					'fields'           => 'ids',
					'paged'            => $paged++,
					'post_status'      => 'publish',
					'post_type'        => Settings::POST_TYPE,
					'posts_per_page'   => 100,
					'suppress_filters' => false,
				]
			);

			if ( empty( $feed_ids ) ) {
				break;
			}

			foreach ( $feed_ids as $feed_id ) {
				Runner::schedule_next_run( $feed_id );
			}
		}

		// Schedule the next run of the scheduler.
		$this->schedule_next_run();
	}

	/**
	 * Ensure the scheduler is scheduled to run.
	 */
	protected function schedule_next_run() {
		if ( ! wp_next_scheduled( static::CRON_HOOK ) ) {
			wp_schedule_single_event( time() + HOUR_IN_SECONDS, static::CRON_HOOK );
		}
	}
}
