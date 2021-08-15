<?php
/**
 * Add extra fields into rest api to format blocks as json data.
 *
 * @package WP_REST_Blocks.
 */

namespace WP_REST_Blocks\Data;

use WP_Block;
use pQuery;

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

	$post_id = $post->ID;
	$blocks  = parse_blocks( $post->post_content );
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
 * @return array|false
 */
function handle_do_block( array $block, $post_id = 0 ) {
	if ( ! $block['blockName'] ) {
		return false;
	}

	$block_object = new WP_Block( $block );
	$attr         = $block['attrs'];
	if ( $block_object && $block_object->block_type ) {
		$attributes = $block_object->block_type->attributes;
		$supports   = $block_object->block_type->supports;
		if ( $supports && isset( $supports['anchor'] ) && $supports['anchor'] ) {
				$attributes['anchor'] = [
					'type'      => 'string',
					'source'    => 'attribute',
					'attribute' => 'id',
					'selector'  => '*',
					'default'   => '',
				];
		}

		if ( $attributes ) {
			foreach ( $attributes as $key => $attribute ) {
				if ( ! isset( $attr[ $key ] ) ) {
					$attr[ $key ] = get_attribute( $attribute, $block_object->inner_html, $post_id );
				}
			}
		}
	}

	$block['rendered'] = $block_object->render();
	$block['rendered'] = do_shortcode( $block['rendered'] );
	$block['attrs']    = $attr;
	if ( ! empty( $block['innerBlocks'] ) ) {
		$innerBlocks          = $block['innerBlocks'];
		$block['innerBlocks'] = [];
		foreach ( $innerBlocks as $_block ) {
			$block['innerBlocks'][] = handle_do_block( $_block, $post_id );
		}
	}

	return $block;
}

/**
 * Get attribute.
 *
 * @param array  $attribute Attributes.
 * @param string $html HTML string.
 * @param int    $post_id Post Number. Deafult 0.
 *
 * @return mixed
 */
function get_attribute( $attribute, $html, $post_id = 0 ) {
	$value = null;
	if ( isset( $attribute['source'] ) ) {
		if ( isset( $attribute['selector'] ) ) {
			$dom = pQuery::parseStr( trim( $html ) );
			if ( 'attribute' === $attribute['source'] ) {
				$value = $dom->query( $attribute['selector'] )->attr( $attribute['attribute'] );
			} elseif ( 'html' === $attribute['source'] ) {
				$value = $dom->query( $attribute['selector'] )->html();
			} elseif ( 'text' === $attribute['source'] ) {
				$value = $dom->query( $attribute['selector'] )->text();
			} elseif ( 'query' === $attribute['source'] && isset( $attribute['query'] ) ) {
				$nodes   = $dom->query( $attribute['selector'] )->getIterator();
				$counter = 0;
				foreach ( $nodes as $node ) {
					foreach ( $attribute['query'] as $key => $current_attribute ) {
						$current_value = get_attribute( $current_attribute, $node->toString(), $post_id );
						if ( null !== $current_value ) {
							$value[ $counter ][ $key ] = $current_value;
						}
					}
					$counter ++;
				}
			}
		} else {
			$dom  = pQuery::parseStr( trim( $html ) );
			$node = $dom->query();
			if ( 'attribute' === $attribute['source'] ) {
				$current_value = $node->attr( $attribute['attribute'] );
				if ( null !== $current_value ) {
					$value = $current_value;
				}
			} elseif ( 'html' === $attribute['source'] ) {
				$value = $node->html();
			} elseif ( 'text' === $attribute['source'] ) {
				$value = $node->text();
			}
		}

		if ( 'meta' === $attribute['source'] && isset( $attribute['meta'] ) ) {
			$value = get_post_meta( $post_id, $attribute['meta'], true );
		}
	}

	if ( is_null( $value ) && isset( $attribute['default'] ) ) {
		$value = $attribute['default'];
	}

	if ( isset( $attribute['type'] ) && rest_validate_value_from_schema( $value, $attribute ) ) {
		$value = rest_sanitize_value_from_schema( $value, $attribute );
	}

	return $value;
}
