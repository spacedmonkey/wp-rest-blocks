<?php
/**
 * Class SampleTest
 *
 * @package WP_REST_Blocks
 */

namespace WP_REST_Blocks\Tests;

use WP_REST_Blocks\Data;
use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * Class BlocksTest
 *
 * @package WP_REST_Blocks\Tests
 */
class BlocksTest extends TestCase {
	/**
	 * Static variable for post object.
	 *
	 * @var int $post_id Post id.
	 */
	protected static $post_ids = [];

	/**
	 *
	 * @param $factory
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$post_ids['separator'] = $factory->post->create(
			[
				'post_content' => '<!-- wp:core/separator -->',
			]
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

		self::$post_ids['multi'] = $factory->post->create(
			[
				'post_content' => $mixed_post_content,
			]
		);

		$mixed_post_content = '
		<!-- wp:image {"align":"center"} -->
			<div class="wp-block-image"><figure class="aligncenter"><img src="https://cldup.com/YLYhpou2oq.jpg" alt="Test alt"/><figcaption>Give it a try. Press the "really wide" button on the image toolbar.</figcaption></figure></div>
		<!-- /wp:image -->';

		self::$post_ids['image'] = $factory->post->create(
			[
				'post_content' => $mixed_post_content,
			]
		);
		$mixed_post_content      = '
		<!-- wp:gallery {"ids":[1,2],"columns":2,"linkTo":"none"} -->
		<ul class="wp-block-gallery columns-2 is-cropped">
			<li class="blocks-gallery-item">
				<figure>
					<img src="https://cldup.com/uuUqE_dXzy.jpg" alt="title" />
				</figure>
			</li>
			<li class="blocks-gallery-item">
				<figure>
					<img src="http://google.com/hi.png" alt="title" />
				</figure>
			</li>
		</ul>
		<!-- /wp:core/gallery -->';

		self::$post_ids['gallery'] = $factory->post->create(
			[
				'post_content' => $mixed_post_content,
			]
		);
		$mixed_post_content        = '
		<!-- wp:heading {"level":3,"align":"center","className":"class"} -->
		<h3 style="text-align:center" id="anchor" class="class">Header</h3>
		<!-- /wp:heading -->';

		self::$post_ids['heading'] = $factory->post->create(
			[
				'post_content' => $mixed_post_content,
			]
		);

		$mixed_post_content = '
		<!-- wp:core/button {"align":"center"} -->
		<div class="wp-block-button aligncenter"><a class="wp-block-button__link" title="Gutenberg is cool" href="https://github.com/WordPress/gutenberg">Help build Gutenberg</a></div>
		<!-- /wp:core/button -->';

		self::$post_ids['button'] = $factory->post->create(
			[
				'post_content' => $mixed_post_content,
			]
		);
		$mixed_post_content       = '
		<!-- wp:core/paragraph {"align":"right"} -->
		<p style="text-align:right;">... like this one, which is separate from the above and right aligned.</p>
		<!-- /wp:core/paragraph -->';

		self::$post_ids['paragraph'] = $factory->post->create(
			[
				'post_content' => $mixed_post_content,
			]
		);

		$mixed_post_content = '
		<!-- wp:core/video -->
		<figure class="wp-block-video"><video controls src="https://awesome-fake.video/file.mp4"></video></figure>
		<!-- /wp:core/video -->';

		self::$post_ids['video'] = $factory->post->create(
			[
				'post_content' => $mixed_post_content,
			]
		);

		$mixed_post_content = '
		<!-- wp:core/audio {"align":"right"} -->
		<figure class="wp-block-audio alignright">
		    <audio controls="" src="https://media.simplecast.com/episodes/audio/80564/draft-podcast-51-livePublish2.mp3"></audio>
		</figure>
		<!-- /wp:core/audio -->';

		self::$post_ids['audio'] = $factory->post->create(
			[
				'post_content' => $mixed_post_content,
			]
		);

		self::$post_ids['empty'] = $factory->post->create(
			[
				'post_content' => '',
			]
		);

		$name     = 'fake/test';
		$settings = [
			'icon'       => 'text',
			'attributes' => [
				'test_meta' => [
					'source' => 'meta',
					'meta'   => 'test_meta',
					'type'   => 'number',
				],
			],
		];

		register_block_type( $name, $settings );

		$mixed_post_content = '
		<!-- wp:fake/test {"align":"right"} --><!-- /wp:fake/test -->';

		self::$post_ids['meta_block'] = $factory->post->create(
			[
				'post_content' => $mixed_post_content,
			]
		);

		update_post_meta( self::$post_ids['meta_block'], 'test_meta', 99 );
	}

	/**
	 *
	 */
	public static function wpTearDownAfterClass() {
		foreach ( self::$post_ids as $post_id ) {
			wp_delete_post( $post_id, true );
		}
	}

	/**
	 *
	 */
	public function test_has_block() {
		$object = $this->get_object( self::$post_ids['separator'] );

		$this->assertTrue( Data\has_blocks_get_callback( $object ) );
	}

	/**
	 *
	 */
	public function test_get_blocks() {
		$object = $this->get_object( self::$post_ids['separator'] );

		$this->assertTrue( is_array( Data\blocks_get_callback( $object ) ) );
	}

	/**
	 *
	 */
	public function test_empty_string() {
		$object = $this->get_object( self::$post_ids['empty'] );
		$data   = Data\blocks_get_callback( $object );

		$this->assertTrue( is_array( $data ) );
		$this->assertTrue( empty( $data ) );
		$this->assertFalse( Data\has_blocks_get_callback( $object ) );
	}

	/**
	 *
	 */
	public function test_multiple_blocks() {
		$object = $this->get_object( self::$post_ids['multi'] );

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
		$object = $this->get_object( self::$post_ids['multi'] );

		$data = Data\blocks_get_callback( $object );
		$this->assertEquals( 'core/fake_atts', $data[1]['blockName'] );
		$this->assertEquals( 'b1', $data[1]['attrs']['value'] );
		$this->assertArrayHasKey( 'value', $data[1]['attrs'] );
	}

	/**
	 *
	 */
	public function test_image_blocks_attrs() {
		$object = $this->get_object( self::$post_ids['image'] );
		$data   = Data\blocks_get_callback( $object );
		$this->assertEquals( 'core/image', $data[0]['blockName'] );
		$this->assertArrayHasKey( 'url', $data[0]['attrs'] );
		$this->assertArrayHasKey( 'alt', $data[0]['attrs'] );
		$this->assertArrayHasKey( 'caption', $data[0]['attrs'] );
		$this->assertEquals( 'https://cldup.com/YLYhpou2oq.jpg', $data[0]['attrs']['url'] );
		$this->assertEquals( 'Test alt', $data[0]['attrs']['alt'] );
		$this->assertEquals( 'Give it a try. Press the "really wide" button on the image toolbar.', $data[0]['attrs']['caption'] );
	}

	/**
	 *
	 */
	public function test_gallery() {
		$object = $this->get_object( self::$post_ids['gallery'] );
		$data   = Data\blocks_get_callback( $object );
		$this->assertEquals( 'core/gallery', $data[0]['blockName'] );
		$this->assertTrue( is_array( $data[0]['attrs']['ids'] ) );
		$this->assertTrue( $data[0]['attrs']['imageCrop'] );
		$this->assertEquals( 'none', $data[0]['attrs']['linkTo'] );
		$this->assertEquals( 2, $data[0]['attrs']['columns'] );
	}

	/**
	 *
	 */
	public function test_heading_blocks_attrs() {
		$object = $this->get_object( self::$post_ids['heading'] );
		$data   = Data\blocks_get_callback( $object );

		$this->assertEquals( 'core/heading', $data[0]['blockName'] );
		$this->assertArrayHasKey( 'level', $data[0]['attrs'] );
		$this->assertArrayHasKey( 'className', $data[0]['attrs'] );
		$this->assertArrayHasKey( 'align', $data[0]['attrs'] );
		$this->assertArrayHasKey( 'anchor', $data[0]['attrs'] );
		$this->assertEquals( 3, $data[0]['attrs']['level'] );
		$this->assertEquals( 'class', $data[0]['attrs']['className'] );
		$this->assertEquals( 'center', $data[0]['attrs']['align'] );
		$this->assertEquals( 'anchor', $data[0]['attrs']['anchor'] );
	}


	/**
	 *
	 */
	public function test_button_blocks_attrs() {
		$object = $this->get_object( self::$post_ids['button'] );
		$data   = Data\blocks_get_callback( $object );
		$this->assertEquals( 'core/button', $data[0]['blockName'] );
		$this->assertArrayHasKey( 'text', $data[0]['attrs'] );
		$this->assertArrayHasKey( 'url', $data[0]['attrs'] );
		$this->assertArrayHasKey( 'align', $data[0]['attrs'] );
		$this->assertArrayHasKey( 'title', $data[0]['attrs'] );

		$this->assertEquals( 'Help build Gutenberg', $data[0]['attrs']['text'] );
		$this->assertEquals( 'https://github.com/WordPress/gutenberg', $data[0]['attrs']['url'] );
		$this->assertEquals( 'center', $data[0]['attrs']['align'] );
		$this->assertEquals( 'Gutenberg is cool', $data[0]['attrs']['title'] );
	}

	/**
	 *
	 */
	public function test_audio_blocks_attrs() {
		$object = $this->get_object( self::$post_ids['audio'] );
		$data   = Data\blocks_get_callback( $object );
		$this->assertEquals( 'core/audio', $data[0]['blockName'] );
		$this->assertArrayHasKey( 'src', $data[0]['attrs'] );

		$this->assertEquals( 'https://media.simplecast.com/episodes/audio/80564/draft-podcast-51-livePublish2.mp3', $data[0]['attrs']['src'] );
	}

	/**
	 *
	 */
	public function test_video_blocks_attrs() {
		$object = $this->get_object( self::$post_ids['video'] );
		$data   = Data\blocks_get_callback( $object );
		$this->assertEquals( 'core/video', $data[0]['blockName'] );
		$this->assertArrayHasKey( 'src', $data[0]['attrs'] );

		$this->assertEquals( 'https://awesome-fake.video/file.mp4', $data[0]['attrs']['src'] );
	}

	/**
	 *
	 */
	public function test_meta_blocks_attrs() {
		$object = $this->get_object( self::$post_ids['meta_block'] );
		$data   = Data\blocks_get_callback( $object );
		$this->assertArrayHasKey( 'test_meta', $data[0]['attrs'] );
		$this->assertEquals( 99, $data[0]['attrs']['test_meta'] );
	}

	/**
	 *
	 */
	public function test_paragrap_blocks_attrs() {
		$object = $this->get_object( self::$post_ids['paragraph'] );
		$data   = Data\blocks_get_callback( $object );
		$this->assertEquals( 'core/paragraph', $data[0]['blockName'] );
		$this->assertArrayHasKey( 'content', $data[0]['attrs'] );

		$this->assertEquals( '... like this one, which is separate from the above and right aligned.', $data[0]['attrs']['content'] );
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
}
