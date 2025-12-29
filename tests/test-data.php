<?php
/**
 * Test Data class.
 *
 * @package WP_REST_Blocks
 * @coversDefaultClass \WP_REST_Blocks\Data
 */

declare(strict_types=1);

namespace WP_REST_Blocks\Tests;

use WP_REST_Blocks\Data;
use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * Data test case.
 *
 * @coversDefaultClass \WP_REST_Blocks\Data
 */
class Test_Data extends TestCase {

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
		$this->data = new Data();
	}

	/**
	 * Test get_blocks with empty content.
	 *
	 * @covers ::get_blocks
	 */
	public function test_get_blocks_empty_content() {
		$result = $this->data->get_blocks( '' );
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test get_blocks with simple paragraph block.
	 *
	 * @covers ::get_blocks
	 * @covers ::handle_do_block
	 */
	public function test_get_blocks_simple_paragraph() {
		$content = '<!-- wp:paragraph --><p>Test paragraph</p><!-- /wp:paragraph -->';
		$result  = $this->data->get_blocks( $content );

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertSame( 'core/paragraph', $result[0]['blockName'] );
		$this->assertArrayHasKey( 'rendered', $result[0] );
		$this->assertStringContainsString( 'Test paragraph', $result[0]['rendered'] );
	}

	/**
	 * Test get_blocks with multiple blocks.
	 *
	 * @covers ::get_blocks
	 * @covers ::handle_do_block
	 */
	public function test_get_blocks_multiple_blocks() {
		$content = '<!-- wp:paragraph --><p>First</p><!-- /wp:paragraph -->'
				. '<!-- wp:paragraph --><p>Second</p><!-- /wp:paragraph -->';
		$result  = $this->data->get_blocks( $content );

		$this->assertIsArray( $result );
		$this->assertCount( 2, $result );
		$this->assertSame( 'core/paragraph', $result[0]['blockName'] );
		$this->assertSame( 'core/paragraph', $result[1]['blockName'] );
	}

	/**
	 * Test get_blocks with nested blocks.
	 *
	 * @covers ::get_blocks
	 * @covers ::handle_do_block
	 */
	public function test_get_blocks_nested_blocks() {
		$content = '<!-- wp:group --><div class="wp-block-group">'
				. '<!-- wp:paragraph --><p>Nested paragraph</p><!-- /wp:paragraph -->'
				. '</div><!-- /wp:group -->';
		$result  = $this->data->get_blocks( $content );

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertSame( 'core/group', $result[0]['blockName'] );
		$this->assertArrayHasKey( 'innerBlocks', $result[0] );
		$this->assertCount( 1, $result[0]['innerBlocks'] );
		$this->assertSame( 'core/paragraph', $result[0]['innerBlocks'][0]['blockName'] );
	}

	/**
	 * Test get_blocks with post ID.
	 *
	 * @covers ::get_blocks
	 * @covers ::handle_do_block
	 */
	public function test_get_blocks_with_post_id() {
		$post_id = $this->factory->post->create();
		$content = '<!-- wp:paragraph --><p>Test with post ID</p><!-- /wp:paragraph -->';
		$result  = $this->data->get_blocks( $content, $post_id );

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertSame( 'core/paragraph', $result[0]['blockName'] );
	}

	/**
	 * Test handle_do_block with empty blockName.
	 *
	 * @covers ::handle_do_block
	 */
	public function test_handle_do_block_empty_block_name() {
		$block = array(
			'blockName' => '',
			'attrs'     => array(),
		);

		$result = $this->data->handle_do_block( $block );
		$this->assertFalse( $result );
	}

	/**
	 * Test handle_do_block with valid block.
	 *
	 * @covers ::handle_do_block
	 */
	public function test_handle_do_block_valid_block() {
		$block = array(
			'blockName'   => 'core/paragraph',
			'attrs'       => array(),
			'innerBlocks' => array(),
			'innerHTML'   => '<p>Test</p>',
			'innerContent' => array( '<p>Test</p>' ),
		);

		$result = $this->data->handle_do_block( $block );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'rendered', $result );
		$this->assertArrayHasKey( 'attrs', $result );
	}

	/**
	 * Test handle_do_block with attrs.
	 *
	 * @covers ::handle_do_block
	 */
	public function test_handle_do_block_with_attrs() {
		$block = array(
			'blockName'   => 'core/paragraph',
			'attrs'       => array( 'align' => 'center' ),
			'innerBlocks' => array(),
			'innerHTML'   => '<p>Test</p>',
			'innerContent' => array( '<p>Test</p>' ),
		);

		$result = $this->data->handle_do_block( $block );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'attrs', $result );
		$this->assertSame( 'center', $result['attrs']['align'] );
	}

	/**
	 * Test handle_do_block with inner blocks that return false.
	 *
	 * @covers ::handle_do_block
	 */
	public function test_handle_do_block_with_invalid_inner_blocks() {
		$block = array(
			'blockName'   => 'core/group',
			'attrs'       => array(),
			'innerBlocks' => array(
				array(
					'blockName' => '',
					'attrs'     => array(),
				),
			),
			'innerHTML'   => '<div></div>',
			'innerContent' => array( '<div></div>' ),
		);

		$result = $this->data->handle_do_block( $block );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'innerBlocks', $result );
		$this->assertEmpty( $result['innerBlocks'] );
	}

	/**
	 * Test get_attribute with text source.
	 *
	 * @covers ::get_attribute
	 */
	public function test_get_attribute_text_source() {
		$attribute = array(
			'source' => 'text',
			'selector' => 'p',
		);
		$html      = '<p>Test text</p>';

		$result = $this->data->get_attribute( $attribute, $html );
		$this->assertSame( 'Test text', $result );
	}

	/**
	 * Test get_attribute with html source.
	 *
	 * @covers ::get_attribute
	 */
	public function test_get_attribute_html_source() {
		$attribute = array(
			'source' => 'html',
			'selector' => 'div',
		);
		$html      = '<div><strong>Bold text</strong></div>';

		$result = $this->data->get_attribute( $attribute, $html );
		$this->assertStringContainsString( '<strong>Bold text</strong>', $result );
	}

	/**
	 * Test get_attribute with attribute source.
	 *
	 * @covers ::get_attribute
	 */
	public function test_get_attribute_attribute_source() {
		$attribute = array(
			'source'    => 'attribute',
			'selector'  => 'a',
			'attribute' => 'href',
		);
		$html      = '<a href="https://example.com">Link</a>';

		$result = $this->data->get_attribute( $attribute, $html );
		$this->assertSame( 'https://example.com', $result );
	}

	/**
	 * Test get_attribute with query source.
	 *
	 * @covers ::get_attribute
	 */
	public function test_get_attribute_query_source() {
		$attribute = array(
			'source'   => 'query',
			'selector' => 'li',
			'query'    => array(
				'content' => array(
					'source' => 'text',
				),
			),
		);
		$html      = '<ul><li>Item 1</li><li>Item 2</li></ul>';

		$result = $this->data->get_attribute( $attribute, $html );
		$this->assertIsArray( $result );
		$this->assertCount( 2, $result );
		$this->assertSame( 'Item 1', $result[0]['content'] );
		$this->assertSame( 'Item 2', $result[1]['content'] );
	}

	/**
	 * Test get_attribute with meta source.
	 *
	 * @covers ::get_attribute
	 */
	public function test_get_attribute_meta_source() {
		$post_id = $this->factory->post->create();
		update_post_meta( $post_id, 'test_meta', 'meta_value' );

		$attribute = array(
			'source' => 'meta',
			'meta'   => 'test_meta',
		);
		$html      = '<p>Test</p>';

		$result = $this->data->get_attribute( $attribute, $html, $post_id );
		$this->assertSame( 'meta_value', $result );
	}

	/**
	 * Test get_attribute with meta source but no post ID.
	 *
	 * @covers ::get_attribute
	 */
	public function test_get_attribute_meta_source_no_post_id() {
		$attribute = array(
			'source' => 'meta',
			'meta'   => 'test_meta',
		);
		$html      = '<p>Test</p>';

		$result = $this->data->get_attribute( $attribute, $html, 0 );
		$this->assertNull( $result );
	}

	/**
	 * Test get_attribute with default value.
	 *
	 * @covers ::get_attribute
	 */
	public function test_get_attribute_default_value() {
		$attribute = array(
			'source'  => 'text',
			'selector' => 'span',
			'default' => 'default_value',
		);
		$html      = '<p>No span here</p>';

		$result = $this->data->get_attribute( $attribute, $html );
		$this->assertSame( 'default_value', $result );
	}

	/**
	 * Test get_attribute with type validation.
	 *
	 * @covers ::get_attribute
	 */
	public function test_get_attribute_type_validation() {
		$attribute = array(
			'source'   => 'text',
			'selector' => 'p',
			'type'     => 'string',
		);
		$html      = '<p>Test string</p>';

		$result = $this->data->get_attribute( $attribute, $html );
		$this->assertIsString( $result );
		$this->assertSame( 'Test string', $result );
	}

	/**
	 * Test get_attribute with rich-text source.
	 *
	 * @covers ::get_attribute
	 */
	public function test_get_attribute_rich_text_source() {
		$attribute = array(
			'source'   => 'rich-text',
			'selector' => 'div',
		);
		$html      = '<div><em>Italic</em> text</div>';

		$result = $this->data->get_attribute( $attribute, $html );
		$this->assertStringContainsString( '<em>Italic</em>', $result );
	}

	/**
	 * Test get_attribute without selector.
	 *
	 * @covers ::get_attribute
	 */
	public function test_get_attribute_no_selector() {
		$attribute = array(
			'source' => 'text',
		);
		$html      = 'Plain text';

		$result = $this->data->get_attribute( $attribute, $html );
		$this->assertSame( 'Plain text', $result );
	}

	/**
	 * Test get_attribute with empty query result.
	 *
	 * @covers ::get_attribute
	 */
	public function test_get_attribute_empty_query_result() {
		$attribute = array(
			'source'   => 'query',
			'selector' => 'li',
			'query'    => array(
				'content' => array(
					'source' => 'text',
				),
			),
		);
		$html      = '<div>No list items</div>';

		$result = $this->data->get_attribute( $attribute, $html );
		$this->assertNull( $result );
	}

	/**
	 * Test get_blocks with blocks containing shortcodes.
	 *
	 * @covers ::get_blocks
	 * @covers ::handle_do_block
	 */
	public function test_get_blocks_with_shortcodes() {
		add_shortcode( 'test_shortcode', function() {
			return 'Shortcode output';
		} );

		$content = '<!-- wp:paragraph --><p>[test_shortcode]</p><!-- /wp:paragraph -->';
		$result  = $this->data->get_blocks( $content );

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertStringContainsString( 'Shortcode output', $result[0]['rendered'] );

		remove_shortcode( 'test_shortcode' );
	}
}

