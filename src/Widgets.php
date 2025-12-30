<?php
/**
 * Widgets.
 *
 * @package WP_REST_Blocks.
 */

declare(strict_types=1);

namespace WP_REST_Blocks;

/**
 * Class Widgets
 *
 * Handles widget-related REST API functionality for blocks.
 *
 * @package WP_REST_Blocks
 */
class Widgets extends REST_Blocks {

	/**
	 * Determines if a specific feature is enabled.
	 *
	 * @return bool
	 */
	public function is_feature_enabled(): bool {
		if ( ! function_exists( 'wp_use_widgets_block_editor' ) ) {
			return false;
		}
		return wp_use_widgets_block_editor();
	}

	/**
	 * Add rest api fields.
	 *
	 * @return void
	 */
	public function register_rest_fields(): void {
		if ( ! $this->is_feature_enabled() ) {
			return;
		}

		register_rest_field(
			'widget',
			'has_blocks',
			[
				'get_callback'    => [ $this, 'has_blocks' ],
				'update_callback' => null,
				'schema'          => $this->get_has_blocks_schema(),
			]
		);

		register_rest_field(
			'widget',
			'block_data',
			[
				'get_callback'    => [ $this, 'get_block_data' ],
				'update_callback' => null,
				'schema'          => $this->get_block_data_schema(),
			]
		);
	}

	/**
	 * Get widget
	 *
	 * @param array $data_object Object data.
	 *
	 * @return array
	 */
	public function get_widget( array $data_object ): array {
		global $wp_widget_factory;

		$widget_object = $wp_widget_factory->get_widget_object( $data_object['id_base'] );
		$parsed_id     = wp_parse_widget_id( $data_object['id'] );
		$all_instances = $widget_object->get_settings();

		return $all_instances[ $parsed_id['number'] ] ?? [];
	}

	/**
	 * Callback to get if post content has block data.
	 *
	 * @param array $data_object Array of data rest api request.
	 *
	 * @return bool
	 */
	public function has_blocks( array $data_object ): bool {
		if ( ! isset( $data_object['id_base'] ) || 'block' !== $data_object['id_base'] ) {
			return false;
		}

		$instance = $this->get_widget( $data_object );
		if ( ! isset( $instance['content'] ) || '' === $instance['content'] ) {
			return false;
		}

		return has_blocks( $instance['content'] );
	}

	/**
	 * Loop around all blocks and get block data.
	 *
	 * @param array $data_object Array of data rest api request.
	 *
	 * @return array
	 */
	public function get_block_data( array $data_object ): array {
		if ( ! $this->has_blocks( $data_object ) ) {
			return [];
		}

		$instance = $this->get_widget( $data_object );

		return $this->data->get_blocks( $instance['content'] );
	}
}
