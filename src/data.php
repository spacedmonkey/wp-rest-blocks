<?php
/**
 * Data layer to process to block data.
 *
 * @package WP_REST_Blocks.
 */

namespace WP_REST_Blocks\Data;

use WP_Block;
use DiDom\Document;

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
	$dom   = new Document( parse_str( trim( $html ), $dom) );
	$dom   = $dom->format();
	$nodes  = isset( $attribute['selector'] ) ? $dom->find( $attribute['selector'] ) : $dom->find("*");

	if (!empty($nodes)) {
		$node = $nodes[0];  // Assuming we need the first found element
		switch ($attribute['source']) {
				case 'attribute':
						$value = $node->attr($attribute['attribute']);
						break;
				case 'html':
				case 'rich-text':
						$value = $node->html();
						break;
				case 'text':
						$value = $node->text();
						break;
				case 'query':
						if (isset($attribute['query'])) {
								$value = [];  // Initialize as an array if it's a query type
								foreach ($nodes as $node) {
										$sub_value = [];
										foreach ($attribute['query'] as $key => $current_attribute) {
												$sub_value[$key] = get_attribute($current_attribute, $node->html(), $post_id);
										}
										$value[] = $sub_value;
								}
						}
						break;
				case 'meta':
						if ($post_id && isset($attribute['meta'])) {
								$value = get_post_meta($post_id, $attribute['meta'], true);
						}
						break;
		}
}

// Assign default value if value is null and a default exists.
if (is_null($value) && isset($attribute['default'])) {
		$value = $attribute['default'];
}

return $value;
}
