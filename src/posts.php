<?php
/**
 * Posts.
 *
 * @package WP_REST_Blocks.
 */

namespace WP_REST_Blocks\Posts;

use function WP_REST_Blocks\Data\get_blocks;

/**
 * Bootstrap filters and actions.
 *
 * @return void
 */
function bootstrap() {
	add_action( 'rest_api_init', __NAMESPACE__ . '\\wp_rest_blocks_init' );
}

/**
 * Add rest api fields.
 *
 * @return void
 */
function wp_rest_blocks_init() {
	$post_types = get_post_types(
		[
			'show_in_rest' => true,
		],
		'names'
	);

	if ( ! function_exists( 'use_block_editor_for_post_type' ) ) {
		require_once ABSPATH . 'wp-admin/includes/post.php';
	}

	$types = array_filter( $post_types, 'use_block_editor_for_post_type' );

	if ( ! $types ) {
		return;
	}

	register_rest_field(
		$types,
		'has_blocks',
		[
			'get_callback'    => __NAMESPACE__ . '\\has_blocks_get_callback',
			'update_callback' => null,
			'schema'          => [
				'description' => __( 'Has blocks.', 'wp-rest-blocks' ),
				'type'        => 'boolean',
			],
		]
	);

	register_rest_field(
		$types,
		'blocks',
		[
			'get_callback'    => __NAMESPACE__ . '\\blocks_get_callback',
			'update_callback' => null,
			'schema'          => [
				'description' => __( 'Blocks.', 'wp-rest-blocks' ),
				'type'        => 'object',
			],
		]
	);
}

/**
 * Callback to get if post content has block data.
 *
 * @param array $object Array of data rest api request.
 *
 * @return bool
 */
function has_blocks_get_callback( array $object ) {
	$post = get_post( $object['id'] );
	if ( ! $post ) {
		return false;
	}

	return has_blocks( $post );
}

/**
 * Loop around all blocks and get block data.
 *
 * @param array $object Array of data rest api request.
 *
 * @return array
 */
function blocks_get_callback( array $object ) {
	$post   = get_post( $object['id'] );
	$output = [];
	if ( ! $post ) {
		return $output;
	}

	return get_blocks( $post->post_content, $post->ID );
}
