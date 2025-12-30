<?php
/**
 * Data layer to process to block data.
 *
 * @package WP_REST_Blocks.
 */

declare(strict_types=1);

namespace WP_REST_Blocks;

use DiDom\Exceptions\InvalidSelectorException;
use WP_Block;
use DiDom\Document;

/**
 * Class Data
 *
 * Handles processing of block data.
 *
 * @package WP_REST_Blocks
 */
class Data {

	/**
	 * Get blocks from html string.
	 *
	 * @param string $content Content to parse.
	 * @param int    $post_id Post int.
	 *
	 * @return array
	 */
	public function get_blocks( string $content, int $post_id = 0 ): array {
		$output = [];
		$blocks = parse_blocks( $content );

		foreach ( $blocks as $block ) {
			$block_data = $this->handle_do_block( $block, $post_id );
			if ( false !== $block_data ) {
				$output[] = $block_data;
			}
		}

		return $output;
	}

	/**
	 * Process a block, getting all extra fields.
	 *
	 * @SuppressWarnings("PHPMD.NPathComplexity")
	 *
	 * @param array $block Block data.
	 * @param int   $post_id Post ID.
	 *
	 * @return array|false
	 */
	public function handle_do_block( array $block, int $post_id = 0 ) {
		if ( ! isset( $block['blockName'] ) || '' === $block['blockName'] ) {
			return false;
		}

		$block_object = new WP_Block( $block );
		$attr         = $block['attrs'] ?? [];
		if ( null !== $block_object->block_type ) {
			$attributes = $block_object->block_type->attributes;
			$supports   = $block_object->block_type->supports;
			if ( null !== $supports && isset( $supports['anchor'] ) && $supports['anchor'] ) {
					$attributes['anchor'] = [
						'type'      => 'string',
						'source'    => 'attribute',
						'attribute' => 'id',
						'selector'  => '*',
						'default'   => '',
					];
			}

			if ( null !== $attributes ) {
				foreach ( $attributes as $key => $attribute ) {
					if ( ! isset( $attr[ $key ] ) ) {
						$attr[ $key ] = $this->get_attribute( $attribute, $block_object->inner_html, $post_id );
					}
				}
			}
		}

		$block['rendered'] = $block_object->render();
		$block['rendered'] = do_shortcode( $block['rendered'] );
		$block['attrs']    = $attr;

		if ( is_array( $block['innerContent'] ) && count( $block['innerContent'] ) > 0 ) {
			$block['innerContent'] = array_values( array_filter( $block['innerContent'], 'is_string' ) );
		}

		if ( isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) && count( $block['innerBlocks'] ) > 0 ) {
			$inner_blocks         = $block['innerBlocks'];
			$block['innerBlocks'] = [];
			foreach ( $inner_blocks as $_block ) {
				$inner_result = $this->handle_do_block( $_block, $post_id );
				if ( false !== $inner_result ) {
					$block['innerBlocks'][] = $inner_result;
				}
			}
		}

		return $block;
	}

	/**
	 * Get attribute.
	 *
	 * @SuppressWarnings("PHPMD.ElseExpression")
	 *
	 * @param array  $attribute Attributes.
	 * @param string $html HTML string.
	 * @param int    $post_id Post Number. Default 0.
	 *
	 * @return mixed
	 */
	public function get_attribute( array $attribute, string $html, int $post_id = 0 ) {
		$value = null;

		if ( isset( $attribute['source'] ) ) {
			// Return early for meta source - no Document needed.
			if ( 'meta' === $attribute['source'] ) {
				$value = $this->extract_value_from_meta( $attribute, $post_id );
			} else {
				// Extract value from HTML using Document.
				$value = $this->extract_value_from_html( $attribute, $html, $post_id );
			}
		}

		// Assign default value if value is null and a default exists.
		if ( null === $value && isset( $attribute['default'] ) ) {
			$value = $attribute['default'];
		}

		$allowed_types = [ 'array', 'object', 'string', 'number', 'integer', 'boolean', 'null' ];
		// If attribute type is set and valid, sanitize value.
		if ( isset( $attribute['type'] ) && in_array( $attribute['type'], $allowed_types, true ) ) {
			$value = rest_sanitize_value_from_schema( $value, $attribute );
		}

		return $value;
	}
	/**
	 * Extract value from post meta.
	 *
	 * @param array $attribute Attribute configuration.
	 * @param int   $post_id   Post ID.
	 * @return mixed|null Meta value or null if not found.
	 */
	private function extract_value_from_meta( array $attribute, int $post_id ) {
		if ( $post_id > 0 && isset( $attribute['meta'] ) ) {
			return get_post_meta( $post_id, $attribute['meta'], true );
		}
		return null;
	}


	/**
	 * Extract value from HTML based on attribute source.
	 *
	 * @param array  $attribute Attributes.
	 * @param string $html HTML string.
	 * @param int    $post_id Post Number. Default 0.
	 *
	 * @return mixed
	 */
	private function extract_value_from_html( array $attribute, string $html, int $post_id = 0 ) {
		$value = null;
		try {
			$dom = new Document( trim( $html ) );

			$node = isset( $attribute['selector'] ) ? $dom->find( $attribute['selector'] ) : [ $dom->first( '*' ) ];
		} catch ( InvalidSelectorException $e ) {
			return null;
		}

		// Get first element from array for non-query sources.
		$single_node = count( $node ) > 0 ? $node[0] : null;

		switch ( $attribute['source'] ) {
			case 'attribute':
				$value = null !== $single_node ? $single_node->getAttribute( $attribute['attribute'] ) : null;
				break;
			case 'html':
			case 'rich-text':
				$value = null !== $single_node ? $single_node->innerHtml() : null;
				break;
			case 'text':
				$value = null !== $single_node ? $single_node->text() : null;
				break;
			case 'query':
				if ( isset( $attribute['query'] ) && count( $node ) > 0 ) {
					$counter = 0;
					foreach ( $node as $v_node ) {
						foreach ( $attribute['query'] as $key => $current_attribute ) {
							$current_value = $this->get_attribute( $current_attribute, $v_node->html(), $post_id );
							if ( null !== $current_value ) {
								$value[ $counter ][ $key ] = $current_value;
							}
						}
						++$counter;
					}
				}
				break;
		}

		return $value;
	}
}
