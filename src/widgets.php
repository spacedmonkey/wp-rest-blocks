<?php
/**
 * Widgets.
 *
 * @package WP_REST_Blocks.
 */

namespace WP_REST_Blocks\Widgets;

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
	if ( ! function_exists( 'wp_use_widgets_block_editor' ) || ! wp_use_widgets_block_editor() ) {
		return;
	}

	register_rest_field(
		'widget',
		'has_blocks',
		[
			'get_callback'    => __NAMESPACE__ . '\\has_blocks_widget_get_callback',
			'update_callback' => null,
			'schema'          => [
				'description' => __( 'Has blocks.', 'wp-rest-blocks' ),
				'type'        => 'boolean',
				'context'     => [ 'embed', 'view', 'edit' ],
				'readonly'    => true,
			],
		]
	);

	register_rest_field(
		'widget',
		'blocks',
		[
			'get_callback'    => __NAMESPACE__ . '\\blocks_widget_get_callback',
			'update_callback' => null,
			'schema'          => [
				'description' => __( 'Blocks.', 'wp-rest-blocks' ),
				'type'        => 'object',
				'context'     => [ 'embed', 'view', 'edit' ],
				'readonly'    => true,
			],
		]
	);
}

/**
 * Get widget
 *
 * @param array $object Object data.
 *
 * @return mixed
 */
function get_widget( array $object ) {
	global $wp_widget_factory;

	$widget_object = $wp_widget_factory->get_widget_object( $object['id_base'] );
	$parsed_id     = wp_parse_widget_id( $object['id'] );
	$all_instances = $widget_object->get_settings();

	return $all_instances[ $parsed_id['number'] ];
}

/**
 * Callback to get if post content has block data.
 *
 * @param array $object Array of data rest api request.
 *
 * @return bool
 */
function has_blocks_widget_get_callback( array $object ) {
	if ( ! isset( $object['id_base'] ) || 'block' !== $object['id_base'] ) {
		return false;
	}

	$instance = get_widget( $object );
	if ( ! isset( $instance['content'] ) || ! $instance['content'] ) {
		return false;
	}

	return has_blocks( $instance['content'] );
}

/**
 * Loop around all blocks and get block data.
 *
 * @param array $object Array of data rest api request.
 *
 * @return array
 */
function blocks_widget_get_callback( array $object ) {
	if ( ! has_blocks_widget_get_callback( $object ) ) {
		return [];
	}

	$instance = get_widget( $object );

	return get_blocks( $instance['content'] );
}
