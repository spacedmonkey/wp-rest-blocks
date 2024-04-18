<?php
/**
 * Data layer to process to block data.
 *
 * @package WP_REST_Blocks.
 */

namespace WP_REST_Blocks\Data;

use WP_Block;
use pQuery;

/**
 * Get blocks from html string.
 *
 * @param string $content Content to parse.
 * @param int    $post_id Post int.
 *
 * @return array
 */
function get_blocks( $content, $post_id = 0 ) {
	$output = [];
	$blocks = parse_blocks( $content );

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
		$inner_blocks         = $block['innerBlocks'];
		$block['innerBlocks'] = [];
		foreach ( $inner_blocks as $_block ) {
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
	$dom   = pQuery::parseStr( trim( $html ) );
	$node  = isset( $attribute['selector'] ) ? $dom->query( $attribute['selector'] ) : $dom->query();

	if ( isset( $attribute['source'] ) ) {
		switch ( $attribute['source'] ) {
			case 'attribute':
				$value = $node->attr( $attribute['attribute'] );
				break;
			case 'html':
			case 'rich-text':
				$value = $node->html();
				break;
			case 'text':
				$value = $node->text();
				break;
			case 'query':
				if ( isset( $attribute['query'] ) ) {
					$counter = 0;
					$nodes   = $node->getIterator();
					foreach ( $nodes as $v_node ) {
						foreach ( $attribute['query'] as $key => $current_attribute ) {
							$current_value = get_attribute( $current_attribute, $v_node->toString(), $post_id );
							if ( null !== $current_value ) {
								$value[ $counter ][ $key ] = $current_value;
							}
						}
						++$counter;
					}
				}
				break;
			case 'meta':
				if ( $post_id && isset( $attribute['meta'] ) ) {
					$value = get_post_meta( $post_id, $attribute['meta'], true );
				}
				break;
		}
	}

	// Assign default value if value is null and a default exists.
	if ( is_null( $value ) && isset( $attribute['default'] ) ) {
		$value = $attribute['default'];
	}

	$allowed_types = [ 'array', 'object', 'string', 'number', 'integer', 'boolean', 'null' ];
	// If attribute type is set and valid, sanitize value.
	if ( isset( $attribute['type'] ) && in_array( $attribute['type'], $allowed_types, true ) && rest_validate_value_from_schema( $value, $attribute ) ) {
		$value = rest_sanitize_value_from_schema( $value, $attribute );
	}

	return $value;
}
