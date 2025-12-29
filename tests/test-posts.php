<?php
/**
 * Test Posts class.
 *
 * @package WP_REST_Blocks
 * @coversDefaultClass \WP_REST_Blocks\Posts
 */

declare(strict_types=1);

namespace WP_REST_Blocks\Tests;

use WP_REST_Blocks\Data;
use WP_REST_Blocks\Posts;
use Yoast\WPTestUtils\WPIntegration\TestCase;
use WP_REST_Request;

/**
 * Posts test case.
 *
 * @coversDefaultClass \WP_REST_Blocks\Posts
 */
class Test_Posts extends TestCase {

	/**
	 * Posts instance.
	 *
	 * @var Posts
	 */
	private $posts;

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
	public function set_up(): void {
		parent::set_up();
		$this->data  = new Data();
		$this->posts = new Posts( $this->data );
	}

	/**
	 * Test constructor dependency injection.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$posts = new Posts( $this->data );
		$this->assertInstanceOf( Posts::class, $posts );
	}

	/**
	 * Test init registers hooks.
	 *
	 * @covers ::init
	 */
	public function test_init_registers_hooks() {
		$this->posts->init();
		$this->assertSame( 10, has_action( 'rest_api_init', [ $this->posts, 'register_rest_fields' ] ) );
	}

	/**
	 * Test get_post_types_with_editor returns array.
	 *
	 * @covers ::get_post_types_with_editor
	 */
	public function test_get_post_types_with_editor() {
		$types = $this->posts->get_post_types_with_editor();
		$this->assertIsArray( $types );
		$this->assertContains( 'post', $types );
		$this->assertContains( 'page', $types );
	}

	/**
	 * Test get_post_types_with_editor includes wp_navigation.
	 *
	 * @covers ::get_post_types_with_editor
	 */
	public function test_get_post_types_with_editor_includes_navigation() {
		$types = $this->posts->get_post_types_with_editor();
		$this->assertContains( 'wp_navigation', $types );
	}

	/**
	 * Test get_post_types_with_editor filters by show_in_rest.
	 *
	 * @covers ::get_post_types_with_editor
	 */
	public function test_get_post_types_with_editor_filters_by_rest() {
		register_post_type(
			'test_no_rest',
			[
				'public'       => true,
				'show_in_rest' => false,
			]
		);

		$types = $this->posts->get_post_types_with_editor();
		$this->assertNotContains( 'test_no_rest', $types );

		unregister_post_type( 'test_no_rest' );
	}

	/**
	 * Test register_rest_fields with no post types.
	 *
	 * @covers ::register_rest_fields
	 * @covers ::get_post_types_with_editor
	 */
	public function test_register_rest_fields_no_post_types() {
		// Mock a scenario where no post types are returned.
		$posts = $this->getMockBuilder( Posts::class )
			->setConstructorArgs( [ $this->data ] )
			->onlyMethods( [ 'get_post_types_with_editor' ] )
			->getMock();

		$posts->expects( $this->once() )
			->method( 'get_post_types_with_editor' )
			->willReturn( [] );

		$posts->register_rest_fields();
		// If it doesn't throw an error, the test passes.
		$this->assertTrue( true );
	}

	/**
	 * Test register_rest_fields registers fields.
	 *
	 * @covers ::register_rest_fields
	 * @covers ::get_post_types_with_editor
	 */
	public function test_register_rest_fields() {
		global $wp_rest_additional_fields;
		$wp_rest_additional_fields = [];

		$this->posts->register_rest_fields();

		$this->assertArrayHasKey( 'post', $wp_rest_additional_fields );
		$this->assertArrayHasKey( 'has_blocks', $wp_rest_additional_fields['post'] );
		$this->assertArrayHasKey( 'block_data', $wp_rest_additional_fields['post'] );
	}

	/**
	 * Test get_has_blocks with content raw.
	 *
	 * @covers ::get_has_blocks
	 */
	public function test_get_has_blocks_with_content_raw() {
		$data_object = [
			'id'      => 1,
			'content' => [
				'raw' => '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->',
			],
		];

		$result = $this->posts->get_has_blocks( $data_object );
		$this->assertTrue( $result );
	}

	/**
	 * Test get_has_blocks with content raw without blocks.
	 *
	 * @covers ::get_has_blocks
	 */
	public function test_get_has_blocks_no_blocks() {
		$data_object = [
			'id'      => 1,
			'content' => [
				'raw' => '<p>Plain HTML without blocks</p>',
			],
		];

		$result = $this->posts->get_has_blocks( $data_object );
		$this->assertFalse( $result );
	}

	/**
	 * Test get_has_blocks with post ID.
	 *
	 * @covers ::get_has_blocks
	 */
	public function test_get_has_blocks_with_post_id() {
		$post_id = $this->factory->post->create(
			[
				'post_content' => '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->',
			]
		);

		$data_object = [
			'id' => $post_id,
		];

		$result = $this->posts->get_has_blocks( $data_object );
		$this->assertTrue( $result );
	}

	/**
	 * Test get_has_blocks with wp_id.
	 *
	 * @covers ::get_has_blocks
	 */
	public function test_get_has_blocks_with_wp_id() {
		$post_id = $this->factory->post->create(
			[
				'post_content' => '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->',
			]
		);

		$data_object = [
			'wp_id' => $post_id,
			'id'    => 999,
		];

		$result = $this->posts->get_has_blocks( $data_object );
		$this->assertTrue( $result );
	}

	/**
	 * Test get_has_blocks with non-existent post.
	 *
	 * @covers ::get_has_blocks
	 */
	public function test_get_has_blocks_nonexistent_post() {
		$data_object = [
			'id' => 999999,
		];

		$result = $this->posts->get_has_blocks( $data_object );
		$this->assertFalse( $result );
	}

	/**
	 * Test get_block_data with content raw.
	 *
	 * @covers ::get_block_data
	 */
	public function test_get_block_data_with_content_raw() {
		$data_object = [
			'id'      => 1,
			'content' => [
				'raw' => '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->',
			],
		];

		$result = $this->posts->get_block_data( $data_object );
		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertSame( 'core/paragraph', $result[0]['blockName'] );
	}

	/**
	 * Test get_block_data with post ID.
	 *
	 * @covers ::get_block_data
	 */
	public function test_get_block_data_with_post_id() {
		$post_id = $this->factory->post->create(
			[
				'post_content' => '<!-- wp:paragraph --><p>Test content</p><!-- /wp:paragraph -->',
			]
		);

		$data_object = [
			'id' => $post_id,
		];

		$result = $this->posts->get_block_data( $data_object );
		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertSame( 'core/paragraph', $result[0]['blockName'] );
	}

	/**
	 * Test get_block_data with wp_id.
	 *
	 * @covers ::get_block_data
	 */
	public function test_get_block_data_with_wp_id() {
		$post_id = $this->factory->post->create(
			[
				'post_content' => '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->',
			]
		);

		$data_object = [
			'wp_id' => $post_id,
			'id'    => 999,
		];

		$result = $this->posts->get_block_data( $data_object );
		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
	}

	/**
	 * Test get_block_data with non-existent post.
	 *
	 * @covers ::get_block_data
	 */
	public function test_get_block_data_nonexistent_post() {
		$data_object = [
			'id' => 999999,
		];

		$result = $this->posts->get_block_data( $data_object );
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test get_block_data with multiple blocks.
	 *
	 * @covers ::get_block_data
	 */
	public function test_get_block_data_multiple_blocks() {
		$post_id = $this->factory->post->create(
			[
				'post_content' => '<!-- wp:paragraph --><p>First</p><!-- /wp:paragraph -->'
					. '<!-- wp:paragraph --><p>Second</p><!-- /wp:paragraph -->',
			]
		);

		$data_object = [
			'id' => $post_id,
		];

		$result = $this->posts->get_block_data( $data_object );
		$this->assertIsArray( $result );
		$this->assertCount( 2, $result );
	}

	/**
	 * Test get_block_data with nested blocks.
	 *
	 * @covers ::get_block_data
	 */
	public function test_get_block_data_nested_blocks() {
		$post_id = $this->factory->post->create(
			[
				'post_content' => '<!-- wp:group --><div class="wp-block-group">'
					. '<!-- wp:paragraph --><p>Nested</p><!-- /wp:paragraph -->'
					. '</div><!-- /wp:group -->',
			]
		);

		$data_object = [
			'id' => $post_id,
		];

		$result = $this->posts->get_block_data( $data_object );
		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertArrayHasKey( 'innerBlocks', $result[0] );
		$this->assertCount( 1, $result[0]['innerBlocks'] );
	}

	/**
	 * Test REST API endpoint integration.
	 *
	 * @covers ::register_rest_fields
	 * @covers ::get_has_blocks
	 * @covers ::get_block_data
	 */
	public function test_rest_api_integration() {
		$this->posts->init();
		do_action( 'rest_api_init' );
        wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );

		$post_id = $this->factory->post->create(
			[
				'post_content' => '<!-- wp:paragraph --><p>REST API Test</p><!-- /wp:paragraph -->',
			]
		);

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts/' . $post_id );
		$request->set_param( 'context', 'edit' );
		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertArrayHasKey( 'has_blocks', $data );
		$this->assertArrayHasKey( 'block_data', $data );
		$this->assertTrue( $data['has_blocks'] );
		$this->assertIsArray( $data['block_data'] );
	}
}
