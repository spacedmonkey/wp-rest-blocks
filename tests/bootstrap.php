<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Wp_Rest_Blocks
 */

require_once dirname( dirname( __FILE__ ) ) .  '/vendor/yoast/wp-test-utils/src/WPIntegration/bootstrap-functions.php';
$_tests_dir = Yoast\WPTestUtils\WPIntegration\get_path_to_wp_test_dir();

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';



/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/wp-rest-blocks.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

Yoast\WPTestUtils\WPIntegration\bootstrap_it();

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
