<?php
/**
 * Plugin Name: Feed Consumer
 * Plugin URI: https://github.com/alleyinteractive/feed-consumer
 * Description: Ingest external feeds and other data sources into WordPress
 * Version: 0.1.0
 * Author: Sean Fisher
 * Author URI: https://github.com/alleyinteractive/feed-consumer
 * Requires at least: 5.9
 * Tested up to: 6.2.1
 *
 * Text Domain: feed-consumer
 * Domain Path: /languages/
 *
 * @package feed-consumer
 */

namespace Feed_Consumer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if Composer is installed (remove if Composer is not required for your plugin).
if ( ! file_exists( __DIR__ . '/vendor/wordpress-autoload.php' ) ) {
	\add_action(
		'admin_notices',
		function() {
			?>
			<div class="notice notice-error">
				<p><?php esc_html_e( 'Composer is not installed and feed-consumer cannot load. Try using a `*-built` branch if the plugin is being loaded as a submodule.', 'plugin_domain' ); ?></p>
			</div>
			<?php
		}
	);

	return;
}

// Load Composer dependencies.
require_once __DIR__ . '/vendor/wordpress-autoload.php';

/**
 * Instantiate the plugin.
 */
function main() {
	require_once __DIR__ . '/src/helpers.php';

	Processors::instance();
	Settings::instance();
	Scheduler::instance();
	Runner::register_cron_hook();

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		\WP_CLI::add_command( 'feed-consumer', CLI::class );
	}
}
main();

/**
 * Boot the available integrations.
 */
function boot_integrations() {
	new Integrations\Byline_Manager();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\boot_integrations' );
