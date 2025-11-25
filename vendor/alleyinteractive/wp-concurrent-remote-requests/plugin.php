<?php
/**
 * Plugin Name: wp-concurrent-remote-requests
 * Plugin URI: https://github.com/alleyinteractive/wp-concurrent-remote-requests
 * Description: Feature plugin for concurrent HTTP remote requests.
 * Version: 1.1.1
 * Author: Sean Fisher
 * Author URI: https://github.com/alleyinteractive/wp-concurrent-remote-requests
 * Requires at least: 6.5
 * Tested up to: 6.8
 *
 * @package wp-concurrent-remote-requests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if Composer is installed.
if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	\add_action(
		'admin_notices',
		function () {
			?>
			<div class="notice notice-error">
				<p><?php esc_html_e( 'Composer is not installed and the wp-concurrent-remote-requests cannot load. Try using a `*-built` branch if the plugin is being loaded as a submodule.', 'plugin_domain' ); ?></p>
			</div>
			<?php
		}
	);

	return;
}

// Load Composer dependencies.
require_once __DIR__ . '/vendor/autoload.php';
