<?php
/**
 * Class SampleTest
 *
 * @package WP_REST_Blocks
 */

namespace WP_REST_Blocks\Tests;

use WP_REST_Blocks\Data;
use WP_UnitTestCase;

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
	 * Static variable for post object.
	 *
	 * @var int $post_with_blocks_id Post id.
	 */
	protected static $post_with_image_id;

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

		$mixed_post_content = '
		<!-- wp:image {"align":"center"} -->
			<div class="wp-block-image"><figure class="aligncenter"><img src="https://cldup.com/YLYhpou2oq.jpg" alt="Test alt"/><figcaption>Give it a try. Press the "really wide" button on the image toolbar.</figcaption></figure></div>
		<!-- /wp:image -->';

		self::$post_with_image_id = $factory->post->create(
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
		$object = [ 'content' => [ 'raw' => get_post( self::$post_id )->post_content ] ];
		// Replace this with some actual testing code.
		$this->assertTrue( is_array( Data\blocks_get_callback( $object ) ) );
	}

	/**
	 *
	 */
	public function test_multiple_blocks() {
		$object = [ 'content' => [ 'raw' => get_post( self::$post_with_blocks_id )->post_content ] ];
		// Replace this with some actual testing code.
		$data = Data\blocks_get_callback( $object );
		$this->assertTrue( is_array( $data ) );
		$this->assertEquals( 5, count( $data ) );
		$this->assertEquals( 'core/fake', $data[0]['blockName'] );
		$this->assertEquals( 'core/fake_atts', $data[1]['blockName'] );
	}

	/**
	 *
	 */
	public function test_multiple_blocks_attrs() {
		$object = [ 'content' => [ 'raw' => get_post( self::$post_with_blocks_id )->post_content ] ];
		// Replace this with some actual testing code.
		$data = Data\blocks_get_callback( $object );
		$this->assertEquals( 'core/fake_atts', $data[1]['blockName'] );
		$this->assertEquals( 'b1', $data[1]['attrs']['value'] );
		$this->assertArrayHasKey( 'value', $data[1]['attrs'] );
	}

	/**
	 *
	 */
	public function test_image_blocks_attrs() {
		$object = [ 'content' => [ 'raw' => get_post( self::$post_with_image_id )->post_content ] ];
		$data   = Data\blocks_get_callback( $object );
		$this->assertEquals( 'core/image', $data[0]['blockName'] );
		$this->assertArrayHasKey( 'url', $data[0]['attrs'] );
		$this->assertArrayHasKey( 'alt', $data[0]['attrs'] );
		$this->assertArrayHasKey( 'caption', $data[0]['attrs'] );
		$this->assertEquals( 'https://cldup.com/YLYhpou2oq.jpg', $data[0]['attrs']['src'] );
		$this->assertEquals( 'Test alt', $data[0]['attrs']['alt'] );
		$this->assertEquals( 'Give it a try. Press the "really wide" button on the image toolbar.', $data[0]['attrs']['caption'] );
	}
}
