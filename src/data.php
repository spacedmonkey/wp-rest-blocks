<?php
/**
 * Data layer to process to block data.
 *
 * @package WP_REST_Blocks.
 * @phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
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
	$do_cache = apply_filters( 'rest_api_blocks_cache', true );

	if ( $do_cache ) {
		$cache_key = 'rest_api_blocks_' . md5( $content );
		if ( 0 !== $post_id ) {
			$cache_key .= '_' . md5( serialize( get_post_meta( $post_id ) ) );
		}
		$multisite_cache = is_multisite() && apply_filters( 'rest_api_blocks_multisite_cache', true );

		if ( $multisite_cache ) {
			$output = get_site_transient( $cache_key );
		} else {
			$output = get_transient( $cache_key );
		}

		if ( ! empty( $output ) && is_array( $output ) ) {
			/** This filter is documented at the end of this function */
			return apply_filters( 'rest_api_blocks_output', $output, $content, $post_id, true );
		}
	}

	$output = [];
	$blocks = parse_blocks( $content );

	foreach ( $blocks as $block ) {
		$block_data = handle_do_block( $block, $post_id );
		if ( $block_data ) {
			$output[] = $block_data;
		}
	}

	if ( $do_cache ) {
		$cache_expiration = apply_filters( 'rest_api_blocks_expiration', 0 );

		if ( $multisite_cache ) {
			set_site_transient( $cache_key, $output, $cache_expiration );
		} else {
			set_transient( $cache_key, $output, $cache_expiration );
		}
	}

	/**
	 * Filter to allow plugins to change the parsed blocks.
	 *
	 * @param array $output   The parsed blocks.
	 * @param string $content The content that is parsed.
	 * @param int $post_id    The post id. Defaults to 0 if not parsing a post.
	 * @param bool $cached    True if output is cached.
	 */
	return apply_filters( 'rest_api_blocks_output', $output, $content, $post_id, false );
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

	/**
	 * Filter to allow plugins to change the parsed block.
	 *
	 * @param array $block The parsed block.
	 * @param int $post_id The post id. Defaults to 0 if not parsing a post.
	 * @param WP_Block $block_object The block object.
	 */
	return apply_filters( 'rest_api_handle_block', $block, $post_id, $block_object );
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
