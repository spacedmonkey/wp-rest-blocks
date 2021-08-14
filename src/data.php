<?php
/**
 * Data layer.
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
				if ( isset( $attribute['source'] ) ) {
					$value = null;
					if ( isset( $attribute['selector'] ) ) {
						$dom = pQuery::parseStr( trim( $block_object->inner_html ) );
						if ( 'attribute' === $attribute['source'] ) {
							$value = $dom->query( $attribute['selector'] )->attr( $attribute['attribute'] );
						} elseif ( 'html' === $attribute['source'] ) {
							$value = $dom->query( $attribute['selector'] )->html();
						} elseif ( 'text' === $attribute['source'] ) {
							$value = $dom->query( $attribute['selector'] )->text();
						}
					}
					if ( $post_id && 'meta' === $attribute['source'] && isset( $attribute['meta'] ) ) {
						$value = get_post_meta( $post_id, $attribute['meta'], true );
					}

					if ( null !== $value ) {
						$attr[ $key ] = $value;
					}
				}

				if ( ! isset( $attr[ $key ] ) && isset( $attribute['default'] ) ) {
					$attr[ $key ] = $attribute['default'];
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
		$output = [];
		foreach ( $block['innerBlocks'] as $_block ) {
			$output[] = handle_do_block( $_block, $post_id );
		}
		$block['innerBlocks'] = $output;
	}

	return $block;
}
