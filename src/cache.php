<?php
/**
 * Cache block data for posts.
 *
 * @package WP_REST_Blocks.
 */

namespace WP_REST_Blocks\Cache;

use function WP_REST_Blocks\Data\get_blocks;
use function WP_REST_Blocks\Posts\get_post_types_with_editor;

/**
 * Meta cache key.
 */
const POST_META_KEY = '_wp_rest_block_cache';

/**
 * Bootstrap filters and actions.
 *
 * @return void
 */
function bootstrap() {
	add_filter( 'pre_handle_blocks_rest_blocks', __NAMESPACE__ . '\\load_from_cache', 10, 3 );
	add_action( 'save_post', __NAMESPACE__ . '\\populate_cache', 10, 2 );
}

/**
 * Populate cache using action.
 *
 * @param int      $post_id Post id.
 * @param \WP_Post $post Post objedt.
 *
 * @return void
 */
function populate_cache( $post_id, $post ) {
	$post_types = get_post_types_with_editor();
	if ( ! in_array( get_post_type( $post ), $post_types, true ) ) {
		return;
	}
	if ( ! has_blocks( $post ) ) {
		return;
	}

	$blocks = get_blocks( $post->post_content, $post->ID );
	update_post_meta( $post_id, POST_META_KEY, wp_json_encode( $blocks ) );
}

/**
 * Load from cache.
 *
 * @param array|null $data Current value.
 * @param string     $content Content.
 * @param int        $post_id Post id.
 *
 * @return mixed
 */
function load_from_cache( $data, $content, $post_id ) {
	if ( ! $post_id ) {
		return $data;
	}

	$cache = get_post_meta( $post_id, POST_META_KEY, true );

	if ( ! $cache ) {
		return $data;
	}

	$blocks = json_decode( $cache );
	if ( ! $blocks ) {
		return $data;
	}

	return $blocks;
}
