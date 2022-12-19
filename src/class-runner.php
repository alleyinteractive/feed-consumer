<?php
/**
 * Runner class file
 *
 * @package feed-consumer
 */

namespace Feed_Consumer;

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
			throw new RuntimeException( 'Processor not registered: ' . $settings['processor'] );
		}

		if ( ! class_exists( $processors[ $settings['processor'] ] ) ) {
			throw new RuntimeException( 'Processor class not found: ' . $processors[ $settings['processor'] ] );
		}

		$processor = new $processors[ $settings['processor'] ]();

		if ( ! $processor instanceof Contracts\Processor ) {
			throw new RuntimeException( 'Processor must implement Contracts\Processor.' );
		}

		$processor->set_settings( $settings[ Settings::escape_setting_name( $settings['processor'] ) ] ?? [] );

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
	 * Constructor.
	 *
	 * @param integer              $feed_id Feed post ID.
	 * @param LoggerInterface|null $logger Logger instance.
	 */
	public function __construct( protected int $feed_id, protected ?LoggerInterface $logger = null ) {
	}

	/**
	 * Run a feed with the configured settings.
	 */
	public function run() {
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

			// Schedule the next run of the feed to try again.
			static::schedule_next_run( $this->feed_id );

			static::$current_feed_id = null;

			return;
		}

		if ( empty( $extractor ) ) {
			$this->logger?->info( 'No extracted data found' );

			static::$current_feed_id = null;

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

			// Schedule the next run of the feed to try again.
			static::schedule_next_run( $this->feed_id );

			static::$current_feed_id = null;

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

		if ( empty( $transformed_data ) ) {
			$this->logger?->info( 'No transformed data found' );

			static::$current_feed_id = null;

			return;
		}

		// Pass the data to the loader.
		try {
			$loaded_data = $processor
				->get_loader()
				->set_processor( $processor )
				->set_transformer( $transformer )
				->load();
		} catch ( Throwable $e ) {
			$this->logger?->error( 'Error running feed loader', [ 'exception' => $e ] );

			// Schedule the next run of the feed to try again.
			static::schedule_next_run( $this->feed_id );

			static::$current_feed_id = null;

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

		// Update the last run time of the feed.
		update_post_meta( $this->feed_id, static::LAST_RUN_META_KEY, current_time( 'timestamp' ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested

		// Schedule the next run of the feed.
		static::schedule_next_run( $this->feed_id );

		static::$current_feed_id = null;
	}
}
