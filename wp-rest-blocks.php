<?php
/**
 * Plugin Name:     REST API blocks
 * Plugin URI:      https://github.com/spacedmonkey/wp-rest-blocks
 * Description:     Add gutenberg blocks into the api
 * Author:          Jonathan Harris
 * Author URI:      https://www.spacedmonkey.com/
 * Text Domain:     wp-rest-blocks
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         WP_REST_Blocks
 */

namespace WP_REST_Blocks;

use WP_REST_Blocks\Data;
require_once __DIR__ . '/src/blocks.php';

Data\bootstrap();
