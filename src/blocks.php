<?php
/**
 * Add extra fields into rest api to format blocks as json data.
 *
 * @package WP_REST_Blocks.
 */

namespace WP_REST_Blocks\Data;

use WP_Block;
use pQuery;
use Symfony\Component\CssSelector\CssSelectorConverter;

/**
 * Bootstrap filters and actions.
 */
function bootstrap() {
	add_action( 'rest_api_init', __NAMESPACE__ . '\\wp_rest_blocks_init' );
}

/**
 * Add rest api fields.
 */
function wp_rest_blocks_init() {
	$types = get_post_types(
		array(
			'show_in_rest' => true,
		),
		'names'
	);

	register_rest_field(
		$types,
		'has_blocks',
		array(
			'get_callback'    => __NAMESPACE__ . '\\has_blocks_get_callback',
			'update_callback' => null,
			'schema'          => array(
				'description' => __( 'Has blocks.', 'wp-rest-blocks' ),
				'type'        => 'boolean',
			),
		)
	);

	register_rest_field(
		$types,
		'blocks',
		array(
			'get_callback'    => __NAMESPACE__ . '\\blocks_get_callback',
			'update_callback' => null,
			'schema'          => array(
				'description' => __( 'Blocks.', 'wp-rest-blocks' ),
				'type'        => 'object',
			),
		)
	);
}


/**
 * Callback to get if post content has block data.
 *
 * @param array $object Array of data rest api request.
 *
 * @return bool
 */
function has_blocks_get_callback( $object ) {
	return has_blocks( $object['id'] );
}

/**
 * Loop around all blocks and get block data.
 *
 * @param array $object Array of data rest api request.
 *
 * @return array
 */
function blocks_get_callback( $object ) {
	$blocks  = parse_blocks( $object['content']['raw'] );
	$post_id = $object['id'];
	$output  = array();
	foreach ( $blocks as $block ) {
		$block_data = handle_do_block( $block, $post_id );
		if ( $block_data ) {
			$output[] = $block_data;
		}
	}

	return $output;
}

/**
 * Process a block, getting all extra fields.
 *
 * @param array $block Block data.
 * @param int   $post_id Post ID.
 *
 * @return array
 */
function handle_do_block( $block, $post_id = 0 ) {
	if ( ! $block['blockName'] ) {
		return false;
	}

	$block_object = new WP_Block( $block );
	$attr         = array();
	if ( $block_object && $block_object->block_type ) {
		$attributes = $block_object->block_type->attributes;
		$attr       = $block['attrs'];
		if ( $attributes ) {
			$dom = pQuery::parseStr( $block_object->inner_html );
			foreach ( $attributes as $key => $attribute ) {
				if ( isset( $attribute['source'] ) ) {
					$value = null;
					if ( 'attribute' === $attribute['source'] && isset( $attribute['selector'] ) ) {
						$value = $dom->query( $attribute['selector'] )->attr( $attribute['attribute'] );
					} elseif ( 'html' === $attribute['source'] && isset( $attribute['selector'] ) ) {
						$value = $dom->query( $attribute['selector'] )->html();
					} elseif ( 'text' === $attribute['source'] && isset( $attribute['selector'] ) ) {
						$value = $dom->query( $attribute['selector'] )->text();
					} elseif ( 'meta' === $attribute['source'] && isset( $attribute['meta'] ) ) {
						$value = get_post_meta( $post_id, $attribute['meta'], true );
					}

					if ( null !== $value ) {
						$attr[ $key ] = $value;
					} elseif ( isset( $attribute['default'] ) ) {
						$attr[ $key ] = $attribute['default'];
					}
				}

				if ( isset( $attr[ $key ] ) && rest_validate_value_from_schema( $attr[ $key ], $attribute ) ) {
					$attr[ $key ] = rest_sanitize_value_from_schema( $attr[ $key ], $attribute );
				}
			}
		}
	}

	$block['rendered'] = $block_object->render();
	$block['rendered'] = do_shortcode( $block['rendered'] );
	$block['attrs']    = $attr;
	if ( ! empty( $block['innerBlocks'] ) ) {
		$output = array();
		foreach ( $block['innerBlocks'] as $_block ) {
			$output[] = handle_do_block( $_block, $post_id );
		}
		$block['innerBlocks'] = $output;
	}

	return $block;
}
