<?php
/**
 * Class SampleTest
 *
 * @package WP_REST_Blocks
 */

use WP_REST_Blocks;

/**
 * Test block parsing.
 */
class BlocksTest extends WP_UnitTestCase {
	/**
	 * Static variable for post object.
	 *
	 * @var int $post_id Post id.
	 */
	protected static $post_id;

	/**
	 *
	 * @param $factory
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$post_id = $factory->post->create(
			array(
				'post_content' => '<!-- wp:core/separator -->',
			)
		);
	}

	/**
	 *
	 */
	public static function wpTearDownAfterClass() {
		wp_delete_post( self::$post_id, true );
	}

	/**
	 *
	 */
	public function test_has_block() {
		$object = [ 'id' => self::$post_id ];
		// Replace this with some actual testing code.
		$this->assertTrue( WP_REST_Blocks\has_blocks_get_callback( $object ) );
	}

	/**
	 *
	 */
	public function test_get_blocks() {
		$object = [ 'content' => [ 'raw' => get_the_content( null, false, self::$post_id ) ] ];
		// Replace this with some actual testing code.
		$this->assertTrue( is_array( WP_REST_Blocks\blocks_get_callback( $object ) ) );
	}
}
