<?php
/**
 * feed-consumer Test Bootstrap
 */

/**
 * Visit {@see https://mantle.alley.co/testing/test-framework.html} to learn more.
 */
\Mantle\Testing\manager()
	->maybe_rsync_plugin()
	// Load the main file of the plugin.
	->loaded(
		function() {
			if ( file_exists( __DIR__ . '/../../byline-manager/byline-manager.php' ) ) {
				require_once __DIR__ . '/../../byline-manager/byline-manager.php';
			}

			require_once __DIR__ . '/../plugin.php';
		}
	)
	->install();
