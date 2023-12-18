<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Wp_Rest_Blocks
 */

require_once dirname( __DIR__ ) . '/vendor/yoast/wp-test-utils/src/WPIntegration/bootstrap-functions.php';
$_tests_dir = Yoast\WPTestUtils\WPIntegration\get_path_to_wp_test_dir();

define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills' );

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( __DIR__ ) . '/wp-rest-blocks.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

Yoast\WPTestUtils\WPIntegration\bootstrap_it();
