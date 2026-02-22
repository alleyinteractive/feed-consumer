<?php
/**
 * Run On Demand class file
 *
 * @package feed-consumer
 */

namespace Feed_Consumer;

use Mantle\Support\Traits\Singleton;
use WP_Post;

/**
 * Run On Demand
 *
 * Adds UI to run a feed on demand from the admin edit screen.
 */
class Run_On_Demand {
	use Singleton;

	/**
	 * AJAX action name.
	 *
	 * @var string
	 */
	public const AJAX_ACTION = 'feed_consumer_run_feed_now';

	/**
	 * Nonce action prefix.
	 *
	 * @var string
	 */
	public const NONCE_ACTION = 'feed_consumer_run_now_';

	/**
	 * Meta box ID.
	 *
	 * @var string
	 */
	public const META_BOX_ID = 'feed-consumer-run-now';

	/**
	 * Constructor.
	 */
	protected function __construct() {
		add_action( 'add_meta_boxes_' . Settings::POST_TYPE, [ $this, 'add_meta_boxes' ] );
		add_action( 'wp_ajax_' . static::AJAX_ACTION, [ $this, 'handle_ajax' ] );
	}

	/**
	 * Add meta boxes.
	 *
	 * @param WP_Post $post The current post object.
	 */
	public function add_meta_boxes( WP_Post $post ): void {
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		add_meta_box(
			static::META_BOX_ID,
			__( 'Run Feed', 'feed-consumer' ),
			[ $this, 'render_meta_box' ],
			Settings::POST_TYPE,
			'side',
			'low',
		);
	}

	/**
	 * Render the Run Feed meta box.
	 *
	 * @param WP_Post $post The current post object.
	 */
	public function render_meta_box( WP_Post $post ): void {
		wp_nonce_field( static::NONCE_ACTION . $post->ID, '_feed_consumer_run_now_nonce' );

		/**
		 * Fires inside the Run Feed meta box before the submit button, allowing
		 * additional fields to be added.
		 *
		 * @param WP_Post $post The current feed post object.
		 */
		do_action( 'feed_consumer_run_now_meta_box_fields', $post );

		printf(
			'<p><button type="button" class="button button-primary" id="%s">%s</button></p>',
			esc_attr( static::META_BOX_ID . '-button' ),
			esc_html__( 'Run Feed Now', 'feed-consumer' ),
		);

		echo '<p class="description" id="' . esc_attr( static::META_BOX_ID . '-status' ) . '"></p>';
		?>
		<script type="text/javascript">
		(function () {
			var button = document.getElementById(<?php echo wp_json_encode( static::META_BOX_ID . '-button' ); ?>);
			var status = document.getElementById(<?php echo wp_json_encode( static::META_BOX_ID . '-status' ); ?>);
			if (!button || !status) {
				return;
			}
			button.addEventListener('click', function () {
				button.disabled = true;
				status.textContent = <?php echo wp_json_encode( __( 'Scheduling feed to run...', 'feed-consumer' ) ); ?>;

				var formData = new FormData();
				formData.append('action', <?php echo wp_json_encode( static::AJAX_ACTION ); ?>);
				formData.append('post_id', <?php echo wp_json_encode( $post->ID ); ?>);
				formData.append('_wpnonce', document.getElementById('_feed_consumer_run_now_nonce').value);

				/**
				 * Allow additional form data to be appended to the AJAX request.
				 *
				 * @type {FormData}
				 */
				var event = new CustomEvent('feed_consumer_run_now_form_data', { detail: formData, bubbles: false, cancelable: false });
				button.dispatchEvent(event);

				fetch(ajaxurl, {
					method: 'POST',
					credentials: 'same-origin',
					body: formData
				})
				.then(function (response) { return response.json(); })
				.then(function (data) {
					if (data.success) {
						status.textContent = <?php echo wp_json_encode( __( 'Feed has been scheduled to run now.', 'feed-consumer' ) ); ?>;
					} else {
						status.textContent = data.data && data.data.message
							? data.data.message
							: <?php echo wp_json_encode( __( 'An error occurred.', 'feed-consumer' ) ); ?>;
						button.disabled = false;
					}
				})
				.catch(function () {
					status.textContent = <?php echo wp_json_encode( __( 'An error occurred.', 'feed-consumer' ) ); ?>;
					button.disabled = false;
				});
			});
		}());
		</script>
		<?php
	}

	/**
	 * Handle the AJAX request to run the feed now.
	 */
	public function handle_ajax(): void {
		$post_id = isset( $_POST['post_id'] ) && is_scalar( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// Verify nonce.
		if (
			! isset( $_POST['_wpnonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), static::NONCE_ACTION . $post_id )
		) {
			wp_send_json_error( [ 'message' => __( 'Invalid nonce.', 'feed-consumer' ) ], 403 );
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'feed-consumer' ) ], 403 );
		}

		if ( $post_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Invalid post ID.', 'feed-consumer' ) ], 400 );
		}

		$feed = get_post( $post_id );

		if ( ! $feed || Settings::POST_TYPE !== $feed->post_type ) {
			wp_send_json_error( [ 'message' => __( 'Invalid feed.', 'feed-consumer' ) ], 400 );
		}

		/**
		 * Fires before the feed is scheduled to run now, allowing additional
		 * processing of fields added via the `feed_consumer_run_now_meta_box_fields`
		 * action.
		 *
		 * @param WP_Post $feed     The feed post object.
		 * @param array   $post_data The POST data from the AJAX request.
		 */
		do_action( 'feed_consumer_run_now_before_schedule', $feed, $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// Remove any existing scheduled runs for this feed and schedule immediately.
		wp_clear_scheduled_hook( Runner::CRON_HOOK, [ $post_id ] );
		wp_schedule_single_event( time(), Runner::CRON_HOOK, [ $post_id ] );

		wp_send_json_success();
	}
}
