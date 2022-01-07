<?php
/**
 * Plugin Name:       REST API Blocks
 * Plugin URI:        https://github.com/spacedmonkey/wp-rest-blocks
 * Description:       Add gutenberg blocks data into post / page / widget REST API endpoints.
 * Author:            Jonathan Harris
 * Author URI:        https://www.spacedmonkey.com/
 * Text Domain:       wp-rest-blocks
 * Domain Path:       /languages
 * Version:           0.5.0
 * Requires at least: 5.5
 * Requires PHP:      7.0
 *
 * @package         WP_REST_Blocks
 */

namespace WP_REST_Blocks;

use WP_REST_Blocks\Posts;
use WP_REST_Blocks\Widgets;

if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

if ( ! class_exists( '\pQuery' ) ) {
	/**
	 * Displays an admin notice about why the plugin is unable to load.
	 *
	 * @return void
	 */
	function admin_notice() {
		$message = sprintf(
			/* translators: %s: build commands. */
			__( ' Please run %s to finish installation.', 'wp-rest-blocks' ),
			'<code>composer install</code>'
		);
		?>
		<div class="notice notice-error">
			<p><strong><?php esc_html_e( 'REST API Blocks plugin could not be initialized.', 'wp-rest-blocks' ); ?></strong></p>
			<p><?php echo wp_kses( $message, [ 'code' => [] ] ); ?></p>
		</div>
		<?php
	}
	add_action( 'admin_notices', __NAMESPACE__ . '\admin_notice' );

	return;
}

require_once __DIR__ . '/src/data.php';
require_once __DIR__ . '/src/posts.php';
require_once __DIR__ . '/src/widgets.php';

Posts\bootstrap();
Widgets\bootstrap();
