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
class Widgets {

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
		add_action( 'rest_api_init', array( $this, 'register_rest_fields' ) );
	}

	/**
	 * Add rest api fields.
	 *
	 * @return void
	 */
	public function register_rest_fields(): void {
		if ( ! function_exists( 'wp_use_widgets_block_editor' ) || ! wp_use_widgets_block_editor() ) {
			return;
		}

		register_rest_field(
			'widget',
			'has_blocks',
			array(
				'get_callback'    => array( $this, 'get_has_blocks' ),
				'update_callback' => null,
				'schema'          => array(
					'description' => __( 'Has blocks.', 'wp-rest-blocks' ),
					'type'        => 'boolean',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
			)
		);

		register_rest_field(
			'widget',
			'block_data',
			array(
				'get_callback'    => array( $this, 'get_block_data' ),
				'update_callback' => null,
				'schema'          => array(
					'description' => __( 'Blocks.', 'wp-rest-blocks' ),
					'type'        => 'object',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
			)
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

		return $all_instances[ $parsed_id['number'] ] ?? array();
	}

	/**
	 * Callback to get if post content has block data.
	 *
	 * @param array $data_object Array of data rest api request.
	 *
	 * @return bool
	 */
	public function get_has_blocks( array $data_object ): bool {
		if ( ! isset( $data_object['id_base'] ) || 'block' !== $data_object['id_base'] ) {
			return false;
		}

		$instance = $this->get_widget( $data_object );
		if ( empty( $instance['content'] ) ) {
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
		if ( ! $this->get_has_blocks( $data_object ) ) {
			return array();
		}

		$instance = $this->get_widget( $data_object );

		return $this->data->get_blocks( $instance['content'] );
	}
}
