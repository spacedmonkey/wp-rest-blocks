<?php
/**
 * Posts.
 *
 * @package WP_REST_Blocks.
 */

declare(strict_types=1);

namespace WP_REST_Blocks;

/**
 * Class Posts
 *
 * Handles post-related REST API functionality for blocks.
 *
 * @package WP_REST_Blocks
 */
class Posts {

	/**
	 * Data processor instance.
	 *
	 * @var Data
	 */
	private $data;

	/**
	 * Constructor.
	 *
	 * @param Data $data Data processor instance.
	 */
	public function __construct( Data $data ) {
		$this->data = $data;
	}

	/**
	 * Initialize the class and register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'rest_api_init', [ $this, 'register_rest_fields' ] );
	}

	/**
	 * Get post ID from data object.
	 *
	 * @param array $data_object Array of data rest api request.
	 *
	 * @return int|null
	 */
	private function get_post_id( array $data_object ): ?int {
		return $data_object['wp_id'] ?? $data_object['id'] ?? null;
	}

	/**
	 * Get post types with editor.
	 *
	 * @return array
	 */
	public function get_post_types_with_editor(): array {
		$post_types = get_post_types( [ 'show_in_rest' => true ], 'names' );
		$post_types = array_values( $post_types );

		if ( ! function_exists( 'use_block_editor_for_post_type' ) ) {
			// phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
			require_once ABSPATH . 'wp-admin/includes/post.php';
		}
		$post_types   = array_filter( $post_types, 'use_block_editor_for_post_type' );
		$post_types[] = 'wp_navigation';
		$post_types   = array_filter( $post_types, 'post_type_exists' );

		return $post_types;
	}

	/**
	 * Add rest api fields.
	 *
	 * @return void
	 */
	public function register_rest_fields(): void {
		$types = $this->get_post_types_with_editor();
		if ( 0 === count( $types ) ) {
			return;
		}

		register_rest_field(
			$types,
			'has_blocks',
			[
				'get_callback'    => [ $this, 'has_blocks' ],
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
			$types,
			'block_data',
			[
				'get_callback'    => [ $this, 'get_block_data' ],
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
	 * Callback to get if post content has block data.
	 *
	 * @param array $data_object Array of data rest api request.
	 *
	 * @return bool
	 */
	public function has_blocks( array $data_object ): bool {
		if ( isset( $data_object['content']['raw'] ) ) {
			return has_blocks( $data_object['content']['raw'] );
		}
		$id   = $this->get_post_id( $data_object );
		$post = get_post( $id );
		if ( null === $post ) {
			return false;
		}

		return has_blocks( $post );
	}

	/**
	 * Loop around all blocks and get block data.
	 *
	 * @param array $data_object Array of data rest api request.
	 *
	 * @return array
	 */
	public function get_block_data( array $data_object ): array {
		$id = $this->get_post_id( $data_object );
		if ( isset( $data_object['content']['raw'] ) ) {
			return $this->data->get_blocks( $data_object['content']['raw'], $id );
		}

		$post = get_post( $id );
		if ( null === $post ) {
			return [];
		}

		return $this->data->get_blocks( $post->post_content, $post->ID );
	}
}
