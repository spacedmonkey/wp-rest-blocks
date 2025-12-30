<?php
/**
 * Data layer to process to block data.
 *
 * @package WP_REST_Blocks.
 */

declare(strict_types=1);

namespace WP_REST_Blocks;

/**
 * Abstract class for managing REST API blocks.
 */
abstract class REST_Blocks {
	/**
	 * Data processor instance.
	 *
	 * @var Data
	 */
	protected Data $data;

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
	 * Register REST API fields for posts and widgets.
	 *
	 * @return void
	 */
	/**
	 * Add rest api fields.
	 *
	 * @return void
	 */
	public function register_rest_fields(): void {
		if ( ! $this->is_feature_enabled() ) {
			return;
		}
		$types = $this->get_types();

		register_rest_field(
			$types,
			'has_blocks',
			[
				'get_callback'    => [ $this, 'has_blocks' ],
				'update_callback' => null,
				'schema'          => $this->get_has_blocks_schema(),
			]
		);

		register_rest_field(
			$types,
			'block_data',
			[
				'get_callback'    => [ $this, 'get_block_data' ],
				'update_callback' => null,
				'schema'          => $this->get_block_data_schema(),
			]
		);
	}

	/**
	 * Retrieve a list of supported types.
	 *
	 * @return array
	 */
	abstract public function get_types(): array;

	/**
	 * Retrieves block data based on the provided data object.
	 *
	 * @param array $data_object The data object containing information to extract block data.
	 *
	 * @return array The extracted block data as an array.
	 */
	abstract public function get_block_data( array $data_object ): array;

	/**
	 * Determine if the given data object contains blocks.
	 *
	 * @param array $data_object The input data object to analyze.
	 * @return bool True if blocks are found, false otherwise.
	 */
	abstract public function has_blocks( array $data_object ): bool;

	/**
	 * Determines if a specific feature is enabled.
	 *
	 * @return bool
	 */
	abstract public function is_feature_enabled(): bool;

	/**
	 * Get REST API schema for block data field.
	 *
	 * @return array
	 */
	public function get_block_data_schema(): array {
		return [
			'description' => __( 'Blocks.', 'wp-rest-blocks' ),
			'type'        => 'array',
			'context'     => [ 'embed', 'view', 'edit' ],
			'readonly'    => true,
			'items'       => [
				'type'       => 'object',
				'properties' => [
					'blockName'    => [
						'type'        => 'string',
						'description' => __( 'Block name.', 'wp-rest-blocks' ),
					],
					'attrs'        => [
						'type'                 => 'object',
						'description'          => __( 'Block attributes.', 'wp-rest-blocks' ),
						'additionalProperties' => true,
					],
					'innerBlocks'  => [
						'type'        => 'array',
						'items'       => [
							'type'                 => 'object',
							'additionalProperties' => true,
						],
						'description' => __( 'Inner blocks.', 'wp-rest-blocks' ),
					],
					'innerHTML'    => [
						'type'        => 'string',
						'description' => __( 'Inner HTML.', 'wp-rest-blocks' ),
					],
					'innerContent' => [
						'type'        => 'array',
						'items'       => [
							'type' => 'string',
						],
						'description' => __( 'Inner content.', 'wp-rest-blocks' ),
					],
					'rendered'     => [
						'type'        => 'string',
						'description' => __( 'Rendered block output.', 'wp-rest-blocks' ),
					],
				],
			],
		];
	}

	/**
	 * Get REST API schema for has blocks field.
	 *
	 * @return array
	 */
	public function get_has_blocks_schema(): array {
		return [
			'description' => __( 'Has blocks.', 'wp-rest-blocks' ),
			'type'        => 'boolean',
			'context'     => [ 'embed', 'view', 'edit' ],
			'readonly'    => true,
		];
	}
}
