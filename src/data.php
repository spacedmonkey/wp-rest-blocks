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
					++$counter;
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

		if ( $post_id && 'meta' === $attribute['source'] && isset( $attribute['meta'] ) ) {
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
