<?php
/**
 * Class CacheTest
 *
 * @package WP_REST_Blocks
 * @phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
 */

namespace WP_REST_Blocks\Tests;

use WP_REST_Blocks\Posts;
use WP_REST_Blocks\Widgets;
use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * Class CacheTest
 *
 * @package WP_REST_Blocks\Tests
 * @phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
 */
class CacheTest extends TestCase {
	/**
	 * Static variable for post object.
	 *
	 * @var int $post_id Post id.
	 */
	protected static $post_ids = [];

	/**
	 * @var array
	 */
	protected static $block_types;

	/**
	 *
	 */
	public static function wpSetUpBeforeClass() {
		self::register_block_type(
			'fake/testcache',
			[
				'icon'       => 'text',
				'attributes' => [
					'test_meta' => [
						'source' => 'meta',
						'meta'   => 'test_meta',
						'type'   => 'number',
					],
				],
			]
		);
	}

	/**
	 *
	 */
	public static function wpTearDownAfterClass() {
		foreach ( self::$post_ids as $post_id ) {
			wp_delete_post( $post_id, true );
		}
		foreach ( self::$block_types as $block_type ) {
			unregister_block_type( $block_type );
		}
	}

	/**
	 *
	 */
	public function test_posts_cache() {
		$mixed_post_content = '<!-- wp:fake/testcache {"align":"right"} --><!-- /wp:fake/testcache -->';

		self::$post_ids['cached'] = $this->factory->post->create(
			[
				'post_content' => $mixed_post_content,
			]
		);

		update_post_meta( self::$post_ids['cached'], 'test_meta', 99 );

		$object = $this->get_object( self::$post_ids['cached'] );

		$data = Posts\blocks_get_callback( $object );
		$this->assertArrayHasKey( 'test_meta', $data[0]['attrs'] );
		$this->assertEquals( 99, $data[0]['attrs']['test_meta'] );

		$cached = get_transient( 'rest_api_blocks_' . md5( $object['content']['raw'] ) . '_' . md5( serialize( get_post_meta( self::$post_ids['cached'] ) ) ) );
		$this->assertEquals( $data, $cached );

		// Test cache invalidation after post content update
		$post                = get_post( self::$post_ids['cached'] );
		$post->post_content .= '<!-- wp:core/separator -->';
		wp_update_post( $post );

		$object = $this->get_object( self::$post_ids['cached'] );
		$data   = Posts\blocks_get_callback( $object );
		$this->assertArrayHasKey( 'test_meta', $data[0]['attrs'] );
		$this->assertEquals( 99, $data[0]['attrs']['test_meta'] );

		$cached = get_transient( 'rest_api_blocks_' . md5( $object['content']['raw'] ) . '_' . md5( serialize( get_post_meta( self::$post_ids['cached'] ) ) ) );
		$this->assertEquals( $data, $cached );

		// Test cache invalidation after post meta update
		update_post_meta( self::$post_ids['cached'], 'test_meta', 100 );

		$object = $this->get_object( self::$post_ids['cached'] );
		$data   = Posts\blocks_get_callback( $object );
		$this->assertArrayHasKey( 'test_meta', $data[0]['attrs'] );
		$this->assertEquals( 100, $data[0]['attrs']['test_meta'] );

		$cached = get_transient( 'rest_api_blocks_' . md5( $object['content']['raw'] ) . '_' . md5( serialize( get_post_meta( self::$post_ids['cached'] ) ) ) );
		$this->assertEquals( $data, $cached );
	}

	public function test_widget_cache() {
		register_sidebar(
			[
				'name'          => 'Test Sidebar',
				'id'            => 'test_sidebar',
				'before_widget' => '',
				'after_widget'  => '',
			]
		);

		$widget_id = 7;

		wp_set_sidebars_widgets(
			[
				'wp_inactive_widgets' => [],
				'test_sidebar'        => [
					"block-$widget_id",
				],
				'array_version'       => 3,
			]
		);

		$object = [
			'id'      => "block-$widget_id",
			'id_base' => 'block',
			'content' => '<!-- wp:paragraph -->
<p>sidebar</p>
<!-- /wp:paragraph -->',
		];

		update_option(
			'widget_block',
			[
				$widget_id     => [
					'content' => $object['content'],
				],
				'_multiwidget' => 1,
			]
		);

		$data = Widgets\blocks_widget_get_callback( $object );
		$this->assertEquals( 'sidebar', $data[0]['attrs']['content'] );

		$cached = get_transient( 'rest_api_blocks_' . md5( $object['content'] ) );
		$this->assertEquals( $data, $cached );

		// Test cache invalidation after widget content update
		$object['content'] .= '<!-- wp:paragraph -->
<p>second paragraph</p>
<!-- /wp:paragraph -->';

		update_option(
			'widget_block',
			[
				$widget_id     => [
					'content' => $object['content'],
				],
				'_multiwidget' => 1,
			]
		);

		$data = Widgets\blocks_widget_get_callback( $object );
		$this->assertEquals( 'sidebar', $data[0]['attrs']['content'] );
		$this->assertEquals( 'second paragraph', $data[1]['attrs']['content'] );

		$cached = get_transient( 'rest_api_blocks_' . md5( $object['content'] ) );
		$this->assertEquals( $data, $cached );
	}

	/**
	 * @param $id
	 *
	 * @return array
	 */
	protected function get_object( $id ) {
		$object = [];
		$post   = get_post( $id );
		if ( $post ) {
			$object = [
				'id'      => $id,
				'content' => [ 'raw' => $post->post_content ],
			];
		}

		return $object;
	}

	protected static function register_block_type( $name, $settings ) {
		self::$block_types[] = $name;
		register_block_type( $name, $settings );
	}
}
