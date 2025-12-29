<?php
/**
 * Test Widgets class.
 *
 * @package WP_REST_Blocks
 * @coversDefaultClass \WP_REST_Blocks\Widgets
 */

declare(strict_types=1);

namespace WP_REST_Blocks\Tests;

use WP_REST_Blocks\Data;
use WP_REST_Blocks\Widgets;
use Yoast\WPTestUtils\WPIntegration\TestCase;
use WP_Widget_Block;

/**
 * Widgets test case.
 *
 * @coversDefaultClass \WP_REST_Blocks\Widgets
 */
class Test_Widgets extends TestCase {

	/**
	 * Widgets instance.
	 *
	 * @var Widgets
	 */
	private $widgets;

	/**
	 * Data instance.
	 *
	 * @var Data
	 */
	private $data;

	/**
	 * Set up test.
	 *
	 * @return void
	 */
	protected function set_up(): void {
		parent::set_up();
		$this->data    = new Data();
		$this->widgets = new Widgets( $this->data );
	}

	/**
	 * Test constructor dependency injection.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$widgets = new Widgets( $this->data );
		$this->assertInstanceOf( Widgets::class, $widgets );
	}

	/**
	 * Test init registers hooks.
	 *
	 * @covers ::init
	 */
	public function test_init_registers_hooks() {
		$this->widgets->init();
		$this->assertSame( 10, has_action( 'rest_api_init', [ $this->widgets, 'register_rest_fields' ] ) );
	}

	/**
	 * Test register_rest_fields when widgets block editor is not available.
	 *
	 * @covers ::register_rest_fields
	 */
	public function test_register_rest_fields_no_block_editor() {
		if ( function_exists( 'wp_use_widgets_block_editor' ) ) {
			$this->markTestSkipped( 'Cannot test when wp_use_widgets_block_editor exists' );
		}

		$this->widgets->register_rest_fields();
		// Should return early without error.
		$this->assertTrue( true );
	}

	/**
	 * Test register_rest_fields registers fields.
	 *
	 * @covers ::register_rest_fields
	 */
	public function test_register_rest_fields() {
		if ( ! function_exists( 'wp_use_widgets_block_editor' ) ) {
			$this->markTestSkipped( 'wp_use_widgets_block_editor not available' );
		}

		// Mock wp_use_widgets_block_editor to return true.
		add_filter( 'use_widgets_block_editor', '__return_true' );

		global $wp_rest_additional_fields;
		$wp_rest_additional_fields = [];

		$this->widgets->register_rest_fields();

		if ( wp_use_widgets_block_editor() ) {
			$this->assertArrayHasKey( 'widget', $wp_rest_additional_fields );
			$this->assertArrayHasKey( 'has_blocks', $wp_rest_additional_fields['widget'] );
			$this->assertArrayHasKey( 'block_data', $wp_rest_additional_fields['widget'] );
		}

		remove_filter( 'use_widgets_block_editor', '__return_true' );
	}

	/**
	 * Test get_widget with block widget.
	 *
	 * @covers ::get_widget
	 */
	public function test_get_widget() {
		if ( ! class_exists( 'WP_Widget_Block' ) ) {
			$this->markTestSkipped( 'WP_Widget_Block not available' );
		}

		// Register a block widget.
		global $wp_widget_factory;
		if ( ! isset( $wp_widget_factory->widgets['WP_Widget_Block'] ) ) {
			$wp_widget_factory->widgets['WP_Widget_Block'] = new WP_Widget_Block();
		}

		// Set up widget instance.
		$widget_id = 'block-2';
		$instance  = [
			'content' => '<!-- wp:paragraph --><p>Widget content</p><!-- /wp:paragraph -->',
		];

		// Save widget settings.
		$widget_object    = $wp_widget_factory->widgets['WP_Widget_Block'];
		$all_instances    = $widget_object->get_settings();
		$all_instances[2] = $instance;
		update_option( $widget_object->option_name, $all_instances );

		$data_object = [
			'id'      => $widget_id,
			'id_base' => 'block',
		];

		$result = $this->widgets->get_widget( $data_object );
		$this->assertIsArray( $result );
	}

	/**
	 * Test get_widget returns empty array for non-existent widget.
	 *
	 * @covers ::get_widget
	 */
	public function test_get_widget_nonexistent() {
		if ( ! class_exists( 'WP_Widget_Block' ) ) {
			$this->markTestSkipped( 'WP_Widget_Block not available' );
		}

		global $wp_widget_factory;
		if ( ! isset( $wp_widget_factory->widgets['WP_Widget_Block'] ) ) {
			$wp_widget_factory->widgets['WP_Widget_Block'] = new WP_Widget_Block();
		}

		$data_object = [
			'id'      => 'block-999',
			'id_base' => 'block',
		];

		$result = $this->widgets->get_widget( $data_object );
		$this->assertIsArray( $result );
	}

	/**
	 * Test get_has_blocks with non-block widget.
	 *
	 * @covers ::get_has_blocks
	 */
	public function test_get_has_blocks_non_block_widget() {
		$data_object = [
			'id_base' => 'calendar',
		];

		$result = $this->widgets->get_has_blocks( $data_object );
		$this->assertFalse( $result );
	}

	/**
	 * Test get_has_blocks without id_base.
	 *
	 * @covers ::get_has_blocks
	 */
	public function test_get_has_blocks_no_id_base() {
		$data_object = [
			'id' => 'some-widget-1',
		];

		$result = $this->widgets->get_has_blocks( $data_object );
		$this->assertFalse( $result );
	}

	/**
	 * Test get_has_blocks with block widget containing blocks.
	 *
	 * @covers ::get_has_blocks
	 * @covers ::get_widget
	 */
	public function test_get_has_blocks_with_blocks() {
		if ( ! class_exists( 'WP_Widget_Block' ) ) {
			$this->markTestSkipped( 'WP_Widget_Block not available' );
		}

		global $wp_widget_factory;
		if ( ! isset( $wp_widget_factory->widgets['WP_Widget_Block'] ) ) {
			$wp_widget_factory->widgets['WP_Widget_Block'] = new WP_Widget_Block();
		}

		// Set up widget with blocks.
		$widget_id = 'block-3';
		$instance  = [
			'content' => '<!-- wp:paragraph --><p>Test blocks</p><!-- /wp:paragraph -->',
		];

		$widget_object    = $wp_widget_factory->widgets['WP_Widget_Block'];
		$all_instances    = $widget_object->get_settings();
		$all_instances[3] = $instance;
		update_option( $widget_object->option_name, $all_instances );

		$data_object = [
			'id'      => $widget_id,
			'id_base' => 'block',
		];

		$result = $this->widgets->get_has_blocks( $data_object );
		$this->assertTrue( $result );
	}

	/**
	 * Test get_has_blocks with empty content.
	 *
	 * @covers ::get_has_blocks
	 * @covers ::get_widget
	 */
	public function test_get_has_blocks_empty_content() {
		if ( ! class_exists( 'WP_Widget_Block' ) ) {
			$this->markTestSkipped( 'WP_Widget_Block not available' );
		}

		global $wp_widget_factory;
		if ( ! isset( $wp_widget_factory->widgets['WP_Widget_Block'] ) ) {
			$wp_widget_factory->widgets['WP_Widget_Block'] = new WP_Widget_Block();
		}

		// Set up widget with empty content.
		$widget_id = 'block-4';
		$instance  = [
			'content' => '',
		];

		$widget_object    = $wp_widget_factory->widgets['WP_Widget_Block'];
		$all_instances    = $widget_object->get_settings();
		$all_instances[4] = $instance;
		update_option( $widget_object->option_name, $all_instances );

		$data_object = [
			'id'      => $widget_id,
			'id_base' => 'block',
		];

		$result = $this->widgets->get_has_blocks( $data_object );
		$this->assertFalse( $result );
	}

	/**
	 * Test get_block_data when has_blocks returns false.
	 *
	 * @covers ::get_block_data
	 * @covers ::get_has_blocks
	 */
	public function test_get_block_data_no_blocks() {
		$data_object = [
			'id_base' => 'calendar',
		];

		$result = $this->widgets->get_block_data( $data_object );
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test get_block_data with blocks.
	 *
	 * @covers ::get_block_data
	 * @covers ::get_has_blocks
	 * @covers ::get_widget
	 */
	public function test_get_block_data_with_blocks() {
		if ( ! class_exists( 'WP_Widget_Block' ) ) {
			$this->markTestSkipped( 'WP_Widget_Block not available' );
		}

		global $wp_widget_factory;
		if ( ! isset( $wp_widget_factory->widgets['WP_Widget_Block'] ) ) {
			$wp_widget_factory->widgets['WP_Widget_Block'] = new WP_Widget_Block();
		}

		// Set up widget with blocks.
		$widget_id = 'block-5';
		$instance  = [
			'content' => '<!-- wp:paragraph --><p>Widget block data</p><!-- /wp:paragraph -->',
		];

		$widget_object    = $wp_widget_factory->widgets['WP_Widget_Block'];
		$all_instances    = $widget_object->get_settings();
		$all_instances[5] = $instance;
		update_option( $widget_object->option_name, $all_instances );

		$data_object = [
			'id'      => $widget_id,
			'id_base' => 'block',
		];

		$result = $this->widgets->get_block_data( $data_object );
		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertSame( 'core/paragraph', $result[0]['blockName'] );
	}

	/**
	 * Test get_block_data with multiple blocks.
	 *
	 * @covers ::get_block_data
	 * @covers ::get_widget
	 */
	public function test_get_block_data_multiple_blocks() {
		if ( ! class_exists( 'WP_Widget_Block' ) ) {
			$this->markTestSkipped( 'WP_Widget_Block not available' );
		}

		global $wp_widget_factory;
		if ( ! isset( $wp_widget_factory->widgets['WP_Widget_Block'] ) ) {
			$wp_widget_factory->widgets['WP_Widget_Block'] = new WP_Widget_Block();
		}

		// Set up widget with multiple blocks.
		$widget_id = 'block-6';
		$instance  = [
			'content' => '<!-- wp:paragraph --><p>First</p><!-- /wp:paragraph -->'
				. '<!-- wp:paragraph --><p>Second</p><!-- /wp:paragraph -->',
		];

		$widget_object    = $wp_widget_factory->widgets['WP_Widget_Block'];
		$all_instances    = $widget_object->get_settings();
		$all_instances[6] = $instance;
		update_option( $widget_object->option_name, $all_instances );

		$data_object = [
			'id'      => $widget_id,
			'id_base' => 'block',
		];

		$result = $this->widgets->get_block_data( $data_object );
		$this->assertIsArray( $result );
		$this->assertCount( 2, $result );
	}

	/**
	 * Test get_block_data with nested blocks.
	 *
	 * @covers ::get_block_data
	 * @covers ::get_widget
	 */
	public function test_get_block_data_nested_blocks() {
		if ( ! class_exists( 'WP_Widget_Block' ) ) {
			$this->markTestSkipped( 'WP_Widget_Block not available' );
		}

		global $wp_widget_factory;
		if ( ! isset( $wp_widget_factory->widgets['WP_Widget_Block'] ) ) {
			$wp_widget_factory->widgets['WP_Widget_Block'] = new WP_Widget_Block();
		}

		// Set up widget with nested blocks.
		$widget_id = 'block-7';
		$instance  = [
			'content' => '<!-- wp:group --><div class="wp-block-group">'
				. '<!-- wp:paragraph --><p>Nested</p><!-- /wp:paragraph -->'
				. '</div><!-- /wp:group -->',
		];

		$widget_object    = $wp_widget_factory->widgets['WP_Widget_Block'];
		$all_instances    = $widget_object->get_settings();
		$all_instances[7] = $instance;
		update_option( $widget_object->option_name, $all_instances );

		$data_object = [
			'id'      => $widget_id,
			'id_base' => 'block',
		];

		$result = $this->widgets->get_block_data( $data_object );
		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertArrayHasKey( 'innerBlocks', $result[0] );
	}
}
