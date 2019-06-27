<?php
/**
 * Class SampleTest
 *
 * @package WP_REST_Blocks
 */

namespace WP_REST_Blocks\Tests;

use WP_REST_Blocks\Data;

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
	 * Static variable for post object.
	 *
	 * @var int $post_with_blocks_id Post id.
	 */
	protected static $post_with_blocks_id;

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

		$mixed_post_content = 'before' .
		                      '<!-- wp:core/fake --><!-- /wp:core/fake -->' .
		                      '<!-- wp:core/fake_atts {"value":"b1"} --><!-- /wp:core/fake_atts -->' .
		                      '<!-- wp:core/fake-child -->
								<p>testing the test</p>
								<!-- /wp:core/fake-child -->' .
		                      'between' .
		                      '<!-- wp:core/self-close-fake /-->' .
		                      '<!-- wp:custom/fake {"value":"b2"} /-->' .
		                      'after';

		self::$post_with_blocks_id = $factory->post->create(
			array(
				'post_content' => $mixed_post_content,
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
		$this->assertTrue( Data\has_blocks_get_callback( $object ) );
	}

	/**
	 *
	 */
	public function test_get_blocks() {
		$object = [ 'content' => [ 'raw' => get_the_content( null, false, self::$post_id ) ] ];
		// Replace this with some actual testing code.
		$this->assertTrue( is_array( Data\blocks_get_callback( $object ) ) );
	}

	/**
	 *
	 */
	public function test_multiple_blocks() {
		$object = [ 'content' => [ 'raw' => get_the_content( null, false, self::$post_with_blocks_id ) ] ];
		// Replace this with some actual testing code.
		$data = Data\blocks_get_callback( $object );
		$this->assertTrue( is_array( $data ) );
		$this->assertSame( 6, count( $data ) );
	}
}
