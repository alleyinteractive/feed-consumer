<?php
/**
 * Runner class file
 *
 * @package feed-consumer
 */

namespace Feed_Consumer;

use Feed_Consumer\Contracts\With_Cursor;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Feed Consumer Runner
 */
class Runner {
	/**
	 * Log meta key.
	 *
	 * @var string
	 */
	public const LOG_META_KEY = 'feed_consumer_log';

	/**
	 * Meta key to store the last run time.
	 *
	 * @var string
	 */
	public const LAST_RUN_META_KEY = 'feed_consumer_last_run';

	/**
	 * Meta key to store the last successful run time.
	 *
	 * @var string
	 */
	public const LAST_SUCCESSFUL_RUN_META_KEY = 'feed_consumer_last_successful_run';

	/**
	 * Lock meta key.
	 *
	 * @var string
	 */
	public const LOCK_META_KEY = 'feed_consumer_lock';

	/**
	 * Default lock duration in seconds (30 minutes).
	 *
	 * @var int
	 */
	public const LOCK_DURATION = 1800;

	/**
	 * Cron hook of the runner.
	 *
	 * @var string
	 */
	public const CRON_HOOK = 'feed_consumer_run';

	/**
	 * Current feed ID being processed.
	 *
	 * @var int|null
	 */
	public static ?int $current_feed_id = null;

	/**
	 * Retrieve an instance of a processor for a feed.
	 *
	 * @param int $feed_id Feed ID.
	 * @return Contracts\Processor
	 *
	 * @throws RuntimeException Thrown if the processor is not set or does not implement the Processor interface.
	 */
	public static function processor( int $feed_id ): Contracts\Processor {
		$settings = get_post_meta( $feed_id, Settings::SETTINGS_META_KEY, true );

		if ( empty( $settings['processor'] ) ) {
			throw new RuntimeException( 'No processor set for feed.' );
		}

		$processors = Processors::instance()->processors();

		if ( empty( $processors[ $settings['processor'] ] ) ) {
			throw new RuntimeException( esc_html( 'Processor not registered: ' . $settings['processor'] ) );
		}

		if ( ! class_exists( $processors[ $settings['processor'] ] ) ) {
			throw new RuntimeException( esc_html( 'Processor class not found: ' . $processors[ $settings['processor'] ] ) );
		}

		$processor = new $processors[ $settings['processor'] ]();

		if ( ! $processor instanceof Contracts\Processor ) {
			throw new RuntimeException( 'Processor must implement Contracts\Processor.' );
		}

		$processor->set_settings( $settings[ Settings::escape_setting_name( $settings['processor'] ) ] ?? [] );

		// Instantiate the processor's cursor if supported.
		if ( $processor instanceof With_Cursor ) {
			$cursor = get_post_meta( $feed_id, With_Cursor::CURSOR_META_KEY, true );

			if ( ! is_null( $cursor ) && '' !== $cursor ) {
				$processor->set_cursor( (string) $cursor );
			}
		}

		return $processor;
	}

	/**
	 * Run a scheduled feed by ID.
	 *
	 * @param int $feed_id Feed ID.
	 * @return void
	 */
	public static function run_scheduled( $feed_id ): void {
		$feed_id = (int) $feed_id;

		( new static(
			$feed_id,
			function_exists( 'ai_logger_to_post' ) ? ai_logger_to_post( $feed_id, static::LOG_META_KEY, Logger::INFO ) : null,
		) )->run();
	}

	/**
	 * Register the cron hook for the runner.
	 */
	public static function register_cron_hook() {
		add_action( static::CRON_HOOK, [ __CLASS__, 'run_scheduled' ] );
	}

	/**
	 * Schedule the next run of a feed.
	 *
	 * @param int $feed_id Feed ID.
	 * @return int|null Timestamp of the next run.
	 */
	public static function schedule_next_run( int $feed_id ): ?int {
		$next_run = wp_next_scheduled( static::CRON_HOOK, [ $feed_id ] );

		if ( $next_run ) {
			return $next_run;
		}

		// Determine if the next run should be scheduled.
		if ( Settings::POST_TYPE !== get_post_type( $feed_id ) || 'publish' !== get_post_status( $feed_id ) ) {
			return null;
		}

		try {
			// Fetch the frequency of the processor to calculate the next timestamp.
			$timestamp = time() + static::processor( $feed_id )->frequency();

			if ( ! wp_schedule_single_event( $timestamp, static::CRON_HOOK, [ $feed_id ] ) ) {
				return null;
			}

			return $timestamp;
		} catch ( Throwable ) {
			return null;
		}
	}

	/**
	 * Check if a feed is currently locked.
	 *
	 * @param int $feed_id Feed ID.
	 * @return bool
	 */
	public static function is_locked( int $feed_id ): bool {
		$lock = get_post_meta( $feed_id, static::LOCK_META_KEY, true );

		if ( empty( $lock ) ) {
			return false;
		}

		// The lock value is an expiration timestamp; if it has passed, the lock is expired.
		return time() < (int) $lock;
	}

	/**
	 * Acquire a lock for a feed.
	 *
	 * @param int $feed_id Feed ID.
	 * @return void
	 */
	public static function acquire_lock( int $feed_id ): void {
		/**
		 * Filters the lock duration in seconds.
		 *
		 * @param int $duration Lock duration in seconds. Default 1800 (30 minutes).
		 * @param int $feed_id  Feed ID.
		 */
		$duration = (int) apply_filters( 'feed_consumer_lock_duration', static::LOCK_DURATION, $feed_id );

		update_post_meta( $feed_id, static::LOCK_META_KEY, time() + $duration );
	}

	/**
	 * Release the lock for a feed.
	 *
	 * @param int $feed_id Feed ID.
	 * @return void
	 */
	public static function release_lock( int $feed_id ): void {
		delete_post_meta( $feed_id, static::LOCK_META_KEY );
	}

	/**
	 * Constructor.
	 *
	 * @param integer              $feed_id Feed post ID.
	 * @param LoggerInterface|null $logger Logger instance.
	 */
	public function __construct( protected int $feed_id, protected ?LoggerInterface $logger = null ) {
	}

	/**
	 * Whether this runner instance acquired the lock.
	 *
	 * @var bool
	 */
	protected bool $lock_acquired = false;

	/**
	 * Run a feed with the configured settings.
	 */
	public function run(): void {
		$feed = get_post( $this->feed_id );

		if ( empty( $feed ) ) {
			$this->logger?->error( 'Feed not found' );
			return;
		}

		// Ensure the feed is published.
		if ( 'publish' !== $feed->post_status ) {
			$this->logger?->error( 'Feed not published' );
			return;
		}

		// Instantiate the processor instance.
		try {
			$processor = static::processor( $this->feed_id );

			static::$current_feed_id = $this->feed_id;
		} catch ( Throwable $e ) {
			$this->logger?->error( 'Invalid processor', [ 'exception' => $e ] );

			static::$current_feed_id = null;

			return;
		}

		// Check if the feed is locked to prevent overlapping runs.
		if ( static::is_locked( $this->feed_id ) ) {
			$this->logger?->info( 'Feed is locked, skipping run to prevent overlapping execution.' );

			static::$current_feed_id = null;

			return;
		}

		// Acquire the lock for this feed run.
		static::acquire_lock( $this->feed_id );
		$this->lock_acquired = true;

		// Track to New Relic if configured.
		if ( extension_loaded( 'newrelic' ) ) {
			if ( function_exists( 'newrelic_name_transaction' ) ) {
				newrelic_name_transaction( 'feed-consumer' );
			}

			if ( function_exists( 'newrelic_add_custom_parameter' ) ) {
				newrelic_add_custom_parameter( 'feed_id', $this->feed_id );
			}
		}

		// Clear the previous meta log.
		delete_post_meta( $this->feed_id, static::LOG_META_KEY );

		$this->logger?->info( 'Run started.' );

		try {
			$extractor = $processor
				->get_extractor()
				->set_processor( $processor )
				->run();
		} catch ( Throwable $e ) {
			$this->logger?->error( 'Error running feed extractor', [ 'exception' => $e ] );
			$this->after_run( false );
			return;
		}

		// Pass the data to the transformer.
		try {
			$transformer = $processor->get_transformer();

			$transformed_data = $transformer
				->set_processor( $processor )
				->set_extractor( $extractor )
				->data();
		} catch ( Throwable $e ) {
			$this->logger?->error( 'Error running feed transformer', [ 'exception' => $e ] );
			$this->after_run( false );
			return;
		}

		/**
		 * Filters the transformed data before it is passed to the loader.
		 *
		 * @param array                                $transformed_data Transformed data from the transformer.
		 * @param int                                  $feed_id          Feed ID.
		 * @param \Feed_Consumer\Contracts\Transformer $transformer      Transformer instance.
		 * @param \Feed_Consumer\Contracts\Extractor   $extractor        Extractor instance.
		 */
		$transformed_data = apply_filters( 'feed_consumer_transformed_data', $transformed_data, $this->feed_id, $transformer, $extractor );

		// Pass the data to the loader.
		try {
			$loaded_data = $processor
				->get_loader()
				->set_processor( $processor )
				->set_transformer( $transformer )
				->load();
		} catch ( Throwable $e ) {
			$this->logger?->error( 'Error running feed loader', [ 'exception' => $e ] );
			$this->after_run( false );
			return;
		}

		$loaded_data = array_filter( $loaded_data );

		$this->logger?->info(
			sprintf(
				'Run complete. %d items processed, %d items loaded, %d items skipped.',
				count( $transformed_data ),
				count( $loaded_data ),
				count( $transformed_data ) - count( $loaded_data ),
			)
		);

		/**
		 * Fires after a feed has been processed.
		 *
		 * @param int    $feed_id The feed ID.
		 * @param array  $loaded_data The data that was loaded.
		 * @param string $processor The processor class.
		 */
		do_action( 'feed_consumer_run_complete', $this->feed_id, $loaded_data, $processor::class );

		// Store the cursor of the processor.
		if ( $processor instanceof With_Cursor ) {
			$cursor = $processor->get_cursor();

			if ( is_null( $cursor ) ) {
				delete_post_meta( $this->feed_id, With_Cursor::CURSOR_META_KEY );
			} else {
				update_post_meta( $this->feed_id, With_Cursor::CURSOR_META_KEY, $cursor );
			}
		}

		$this->after_run( true );
	}

	/**
	 * Actions to perform after the feed has run.
	 *
	 * @param bool $successful Whether the run was successful.
	 */
	protected function after_run( bool $successful ): void {
		// Release the lock only if this runner instance acquired it.
		if ( $this->lock_acquired ) {
			static::release_lock( $this->feed_id );
			$this->lock_acquired = false;
		}

		// Update the last run time of the feed.
		$timestamp = time();
		update_post_meta( $this->feed_id, static::LAST_RUN_META_KEY, $timestamp );

		// Update the last successful run time of the feed.
		if ( $successful ) {
			update_post_meta( $this->feed_id, static::LAST_SUCCESSFUL_RUN_META_KEY, $timestamp );
		}

		/**
		 * Fires when feed run completed.
		 *
		 * @since 1.1.2
		 *
		 * @param int  $feed_id The feed id.
		 * @param bool $successful Whether or not the run was successful.
		 * @param int  $timestamp The current timestamp that will be stored in meta keys.
		 */
		do_action( 'feed_consumer_feed_termination', $this->feed_id, $successful, $timestamp );

		// Schedule the next run of the feed.
		static::schedule_next_run( $this->feed_id );

		static::$current_feed_id = null;
	}
}
