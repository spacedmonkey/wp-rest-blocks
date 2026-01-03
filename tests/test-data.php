<?php
/**
 * Test Data class.
 *
 * @package WP_REST_Blocks
 * @coversDefaultClass \WP_REST_Blocks\Data
 */

declare(strict_types = 1);

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
	 */
	private Data $data;

	/**
	 * Set up test.
	 *
	 * @return void
	 */
	public function set_up(): void {
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
		$block = [
			'blockName' => '',
			'attrs'     => [],
		];

		$result = $this->data->handle_do_block( $block );
		$this->assertFalse( $result );
	}

	/**
	 * Test handle_do_block with valid block.
	 *
	 * @covers ::handle_do_block
	 */
	public function test_handle_do_block_valid_block() {
		$block = [
			'blockName'    => 'core/paragraph',
			'attrs'        => [],
			'innerBlocks'  => [],
			'innerHTML'    => '<p>Test</p>',
			'innerContent' => [ '<p>Test</p>' ],
		];

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
		$block = [
			'blockName'    => 'core/paragraph',
			'attrs'        => [ 'align' => 'center' ],
			'innerBlocks'  => [],
			'innerHTML'    => '<p>Test</p>',
			'innerContent' => [ '<p>Test</p>' ],
		];

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
		$block = [
			'blockName'    => 'core/group',
			'attrs'        => [],
			'innerBlocks'  => [
				[
					'blockName' => '',
					'attrs'     => [],
				],
			],
			'innerHTML'    => '<div></div>',
			'innerContent' => [ '<div></div>' ],
		];

		$result = $this->data->handle_do_block( $block );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'innerBlocks', $result );
		$this->assertEmpty( $result['innerBlocks'] );
	}


	/**
	 * Test get_attribute with meta source.
	 *
	 * @covers ::get_attribute
	 */
	public function test_get_attribute_meta_source() {
		$post_id = $this->factory->post->create();
		update_post_meta( $post_id, 'test_meta', 'meta_value' );

		$attribute = [
			'source' => 'meta',
			'meta'   => 'test_meta',
		];
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
		$attribute = [
			'source' => 'meta',
			'meta'   => 'test_meta',
		];
		$html      = '<p>Test</p>';

		$result = $this->data->get_attribute( $attribute, $html, 0 );
		$this->assertNull( $result );
	}

	/**
	 * Data provider for text source tests.
	 *
	 * @return array
	 */
	public function data_provider_text_source() {
		return [
			'simple paragraph'               => [
				'selector'        => 'p',
				'html'            => '<p>Test text</p>',
				'expected_result' => 'Test text',
			],
			'nested span in div'             => [
				'selector'        => 'div span',
				'html'            => '<div><span>Nested text</span></div>',
				'expected_result' => 'Nested text',
			],
			'direct child selector'          => [
				'selector'        => 'div > p',
				'html'            => '<div><p>Direct child</p></div>',
				'expected_result' => 'Direct child',
			],
			'class selector'                 => [
				'selector'        => '.highlight',
				'html'            => '<span class="highlight">Highlighted text</span>',
				'expected_result' => 'Highlighted text',
			],
			'complex nested selector'        => [
				'selector'        => 'article > div p.content',
				'html'            => '<article><div><p class="content">Complex content</p></div></article>',
				'expected_result' => 'Complex content',
			],
			'multiple nested levels'         => [
				'selector'        => 'div > ul > li',
				'html'            => '<div><ul><li>List item</li></ul></div>',
				'expected_result' => 'List item',
			],
			'attribute selector'             => [
				'selector'        => 'p[data-test="value"]',
				'html'            => '<p data-test="value">Attribute selector text</p>',
				'expected_result' => 'Attribute selector text',
			],
			'ID selector'                    => [
				'selector'        => '#unique-id',
				'html'            => '<div id="unique-id">ID selected text</div>',
				'expected_result' => 'ID selected text',
			],
			'multiple classes'               => [
				'selector'        => '.class-one.class-two',
				'html'            => '<span class="class-one class-two">Multi-class text</span>',
				'expected_result' => 'Multi-class text',
			],
			'nth-child selector'             => [
				'selector'        => 'li:nth-child(3)',
				'html'            => '<ul><li>First</li><li>Second</li><li>Third item</li></ul>',
				'expected_result' => 'Third item',
			],
			'first-child pseudo'             => [
				'selector'        => 'div > span:first-child',
				'html'            => '<div><span>First span</span><span>Second</span></div>',
				'expected_result' => 'First span',
			],
			'last-child pseudo'              => [
				'selector'        => 'p:last-child',
				'html'            => '<div><p>First</p><p>Last paragraph</p></div>',
				'expected_result' => 'Last paragraph',
			],
			'attribute contains selector'    => [
				'selector'        => 'a[href*="example"]',
				'html'            => '<a href="https://example.com">Contains example</a>',
				'expected_result' => 'Contains example',
			],
			'attribute starts-with selector' => [
				'selector'        => 'a[href^="https"]',
				'html'            => '<a href="https://secure.com">Starts with https</a>',
				'expected_result' => 'Starts with https',
			],
			'attribute ends-with selector'   => [
				'selector'        => 'a[href$=".pdf"]',
				'html'            => '<a href="document.pdf">PDF Document</a>',
				'expected_result' => 'PDF Document',
			],
			'nested table cell'              => [
				'selector'        => 'table > tbody > tr > td',
				'html'            => '<table><tbody><tr><td>Table cell content</td></tr></tbody></table>',
				'expected_result' => 'Table cell content',
			],
			'complex descendant'             => [
				'selector'        => 'nav ul li a',
				'html'            => '<nav><ul><li><a>Navigation link</a></li></ul></nav>',
				'expected_result' => 'Navigation link',
			],
			'multiple direct children'       => [
				'selector'        => 'section > article > header > h1',
				'html'            => '<section><article><header><h1>Deep heading</h1></header></article></section>',
				'expected_result' => 'Deep heading',
			],
			'attribute existence'            => [
				'selector'        => 'button[disabled]',
				'html'            => '<button disabled>Disabled button</button>',
				'expected_result' => 'Disabled button',
			],
			'fieldset legend'                => [
				'selector'        => 'fieldset > legend',
				'html'            => '<fieldset><legend>Form section</legend></fieldset>',
				'expected_result' => 'Form section',
			],
			'summary in details'             => [
				'selector'        => 'details > summary',
				'html'            => '<details><summary>Click to expand</summary><p>Content</p></details>',
				'expected_result' => 'Click to expand',
			],
			'figcaption in figure'           => [
				'selector'        => 'figure > figcaption',
				'html'            => '<figure><img src="test.jpg" /><figcaption>Image caption</figcaption></figure>',
				'expected_result' => 'Image caption',
			],
			'blockquote cite'                => [
				'selector'        => 'blockquote > cite',
				'html'            => '<blockquote><p>Quote</p><cite>Author name</cite></blockquote>',
				'expected_result' => 'Author name',
			],
			'definition term'                => [
				'selector'        => 'dl > dt',
				'html'            => '<dl><dt>Term definition</dt><dd>Description</dd></dl>',
				'expected_result' => 'Term definition',
			],
			'ordered list item'              => [
				'selector'        => 'ol > li:nth-child(2)',
				'html'            => '<ol><li>First</li><li>Second ordered item</li><li>Third</li></ol>',
				'expected_result' => 'Second ordered item',
			],
			'nested code in pre'             => [
				'selector'        => 'pre > code',
				'html'            => '<pre><code>Code snippet</code></pre>',
				'expected_result' => 'Code snippet',
			],
			'article aside'                  => [
				'selector'        => 'article > aside',
				'html'            => '<article><aside>Sidebar content</aside></article>',
				'expected_result' => 'Sidebar content',
			],
			'time element'                   => [
				'selector'        => 'time[datetime]',
				'html'            => '<time datetime="2025-12-28">December 28, 2025</time>',
				'expected_result' => 'December 28, 2025',
			],
			'mark element'                   => [
				'selector'        => 'p > mark',
				'html'            => '<p>Highlighted <mark>important text</mark> here</p>',
				'expected_result' => 'important text',
			],
		];
	}

	/**
	 * Test get_attribute with text source.
	 *
	 * @dataProvider data_provider_text_source
	 * @covers ::get_attribute
	 * @covers ::extract_value_from_html
	 *
	 * @param string $selector CSS selector.
	 * @param string $html HTML content.
	 * @param string $expected_result Expected result.
	 */
	public function test_get_attribute_text_source( $selector, $html, $expected_result ) {
		$attribute = [
			'source'   => 'text',
			'selector' => $selector,
		];

		$result = $this->data->get_attribute( $attribute, $html );
		$this->assertSame( $expected_result, $result );
	}

	/**
	 * Data provider for HTML source tests.
	 *
	 * @return array
	 */
	public function data_provider_html_source() {
		return [
			'simple div with strong'     => [
				'selector'          => 'div',
				'html'              => '<div><strong>Bold text</strong></div>',
				'expected_contains' => '<strong>Bold text</strong>',
			],
			'paragraph with link'        => [
				'selector'          => 'p',
				'html'              => '<p>Text with <a href="#">link</a></p>',
				'expected_contains' => '<a href="#">link</a>',
			],
			'div > p selector'           => [
				'selector'          => 'div > p',
				'html'              => '<div><p><em>Emphasized</em> content</p></div>',
				'expected_contains' => '<em>Emphasized</em>',
			],
			'figure with image'          => [
				'selector'          => 'figure',
				'html'              => '<figure><img src="test.jpg" alt="Test" /><figcaption>Caption</figcaption></figure>',
				'expected_contains' => '<img src="test.jpg"',
			],
			'nested blockquote'          => [
				'selector'          => 'article > blockquote',
				'html'              => '<article><blockquote><p>Quote text</p></blockquote></article>',
				'expected_contains' => '<p>Quote text</p>',
			],
			'complex nested structure'   => [
				'selector'          => 'div.content > section > p',
				'html'              => '<div class="content"><section><p>Complex <span class="highlight">nested</span> HTML</p></section></div>',
				'expected_contains' => '<span class="highlight">nested</span>',
			],
			'ordered list with items'    => [
				'selector'          => 'ol',
				'html'              => '<ol><li>First <strong>item</strong></li><li>Second</li></ol>',
				'expected_contains' => '<li>First <strong>item</strong></li>',
			],
			'nav with ul structure'      => [
				'selector'          => 'nav > ul',
				'html'              => '<nav><ul><li><a href="/">Home</a></li></ul></nav>',
				'expected_contains' => '<li><a href="/">Home</a></li>',
			],
			'table with thead'           => [
				'selector'          => 'table > thead',
				'html'              => '<table><thead><tr><th>Header</th></tr></thead></table>',
				'expected_contains' => '<th>Header</th>',
			],
			'address element'            => [
				'selector'          => 'footer > address',
				'html'              => '<footer><address><a href="mailto:test@example.com">Contact</a></address></footer>',
				'expected_contains' => '<a href="mailto:test@example.com">Contact</a>',
			],
			'pre with code'              => [
				'selector'          => 'pre',
				'html'              => '<pre><code class="language-js">const x = 5;</code></pre>',
				'expected_contains' => '<code class="language-js">const x = 5;</code>',
			],
			'details with summary'       => [
				'selector'          => 'details',
				'html'              => '<details><summary>Click</summary><div>Hidden <em>content</em></div></details>',
				'expected_contains' => '<div>Hidden <em>content</em></div>',
			],
			'form with fieldset'         => [
				'selector'          => 'form > fieldset',
				'html'              => '<form><fieldset><legend>Group</legend><input type="text" /></fieldset></form>',
				'expected_contains' => '<legend>Group</legend>',
			],
			'multiple paragraph classes' => [
				'selector'          => 'p.intro.important',
				'html'              => '<p class="intro important">Text with <code>inline code</code></p>',
				'expected_contains' => '<code>inline code</code>',
			],
			'div with data attribute'    => [
				'selector'          => 'div[data-block-type="custom"]',
				'html'              => '<div data-block-type="custom"><span class="icon">★</span> Content</div>',
				'expected_contains' => '<span class="icon">★</span>',
			],
			'nth-child div'              => [
				'selector'          => 'section > div:nth-child(2)',
				'html'              => '<section><div>First</div><div>Second <mark>highlighted</mark></div></section>',
				'expected_contains' => '<mark>highlighted</mark>',
			],
			'video with source'          => [
				'selector'          => 'video',
				'html'              => '<video controls><source src="video.mp4" type="video/mp4" /><track kind="captions" /></video>',
				'expected_contains' => '<source src="video.mp4"',
			],
		];
	}

	/**
	 * Test get_attribute with HTML source.
	 *
	 * @dataProvider data_provider_html_source
	 * @covers ::get_attribute
	 * @covers ::extract_value_from_html
	 *
	 * @param string $selector CSS selector.
	 * @param string $html HTML content.
	 * @param string $expected_contains Expected substring in result.
	 */
	public function test_get_attribute_html_source( $selector, $html, $expected_contains ) {
		$attribute = [
			'source'   => 'html',
			'selector' => $selector,
		];

		$result = (string) $this->data->get_attribute( $attribute, $html );
		$this->assertStringContainsString( $expected_contains, $result );
	}

	/**
	 * Data provider for attribute source tests.
	 *
	 * @return array
	 */
	public function data_provider_attribute_source() {
		return [
			'link href'              => [
				'selector'        => 'a',
				'attribute'       => 'href',
				'html'            => '<a href="https://example.com">Link</a>',
				'expected_result' => 'https://example.com',
			],
			'image src'              => [
				'selector'        => 'img',
				'attribute'       => 'src',
				'html'            => '<img src="/path/to/image.jpg" alt="Test" />',
				'expected_result' => '/path/to/image.jpg',
			],
			'image alt'              => [
				'selector'        => 'img',
				'attribute'       => 'alt',
				'html'            => '<img src="test.jpg" alt="Alternative text" />',
				'expected_result' => 'Alternative text',
			],
			'div data attribute'     => [
				'selector'        => 'div',
				'attribute'       => 'data-id',
				'html'            => '<div data-id="12345">Content</div>',
				'expected_result' => '12345',
			],
			'nested image in figure' => [
				'selector'        => 'figure > img',
				'attribute'       => 'src',
				'html'            => '<figure><img src="nested.jpg" /></figure>',
				'expected_result' => 'nested.jpg',
			],
			'link in paragraph'      => [
				'selector'        => 'p > a',
				'attribute'       => 'href',
				'html'            => '<p><a href="/page">Link text</a></p>',
				'expected_result' => '/page',
			],
			'video source'           => [
				'selector'        => 'video > source',
				'attribute'       => 'src',
				'html'            => '<video><source src="video.mp4" type="video/mp4" /></video>',
				'expected_result' => 'video.mp4',
			],
			'class attribute'        => [
				'selector'        => 'div.container',
				'attribute'       => 'class',
				'html'            => '<div class="container main-content">Text</div>',
				'expected_result' => 'container main-content',
			],
			'input value'            => [
				'selector'        => 'input[type="text"]',
				'attribute'       => 'value',
				'html'            => '<input type="text" value="Default value" />',
				'expected_result' => 'Default value',
			],
			'button type'            => [
				'selector'        => 'button.submit',
				'attribute'       => 'type',
				'html'            => '<button class="submit" type="submit">Submit</button>',
				'expected_result' => 'submit',
			],
			'link target'            => [
				'selector'        => 'a[target]',
				'attribute'       => 'target',
				'html'            => '<a href="#" target="_blank">External</a>',
				'expected_result' => '_blank',
			],
			'img width'              => [
				'selector'        => 'img',
				'attribute'       => 'width',
				'html'            => '<img src="photo.jpg" width="800" height="600" />',
				'expected_result' => '800',
			],
			'iframe src'             => [
				'selector'        => 'iframe',
				'attribute'       => 'src',
				'html'            => '<iframe src="https://embed.example.com"></iframe>',
				'expected_result' => 'https://embed.example.com',
			],
			'form action'            => [
				'selector'        => 'form#contact',
				'attribute'       => 'action',
				'html'            => '<form id="contact" action="/submit">Form</form>',
				'expected_result' => '/submit',
			],
			'script src'             => [
				'selector'        => 'script[type="module"]',
				'attribute'       => 'src',
				'html'            => '<script type="module" src="/app.js"></script>', // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
				'expected_result' => '/app.js',
			],
			'link rel'               => [
				'selector'        => 'link[rel]',
				'attribute'       => 'rel',
				'html'            => '<link rel="stylesheet" href="style.css" />', // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
				'expected_result' => 'stylesheet',
			],
			'meta content'           => [
				'selector'        => 'meta[name="description"]',
				'attribute'       => 'content',
				'html'            => '<meta name="description" content="Page description" />',
				'expected_result' => 'Page description',
			],
			'textarea placeholder'   => [
				'selector'        => 'textarea',
				'attribute'       => 'placeholder',
				'html'            => '<textarea placeholder="Enter text here"></textarea>',
				'expected_result' => 'Enter text here',
			],
			'select name'            => [
				'selector'        => 'select.dropdown',
				'attribute'       => 'name',
				'html'            => '<select class="dropdown" name="country"><option>US</option></select>',
				'expected_result' => 'country',
			],
			'audio source type'      => [
				'selector'        => 'audio > source',
				'attribute'       => 'type',
				'html'            => '<audio><source src="audio.mp3" type="audio/mpeg" /></audio>',
				'expected_result' => 'audio/mpeg',
			],
			'time datetime'          => [
				'selector'        => 'time',
				'attribute'       => 'datetime',
				'html'            => '<time datetime="2025-12-28">Dec 28</time>',
				'expected_result' => '2025-12-28',
			],
			'nested link title'      => [
				'selector'        => 'nav ul li a',
				'attribute'       => 'title',
				'html'            => '<nav><ul><li><a href="/" title="Go to homepage">Home</a></li></ul></nav>',
				'expected_result' => 'Go to homepage',
			],
			'table data-sort'        => [
				'selector'        => 'table thead th',
				'attribute'       => 'data-sort',
				'html'            => '<table><thead><tr><th data-sort="asc">Name</th></tr></thead></table>',
				'expected_result' => 'asc',
			],
			'div role'               => [
				'selector'        => 'div[role="banner"]',
				'attribute'       => 'role',
				'html'            => '<div role="banner">Header content</div>',
				'expected_result' => 'banner',
			],
		];
	}

	/**
	 * Test get_attribute with attribute source.
	 *
	 * @dataProvider data_provider_attribute_source
	 * @covers ::get_attribute
	 * @covers ::extract_value_from_html
	 *
	 * @param string $selector CSS selector.
	 * @param string $attribute Attribute name.
	 * @param string $html HTML content.
	 * @param string $expected_result Expected result.
	 */
	public function test_get_attribute_attribute_source( $selector, $attribute, $html, $expected_result ) {
		$attribute_config = [
			'source'    => 'attribute',
			'selector'  => $selector,
			'attribute' => $attribute,
		];

		$result = $this->data->get_attribute( $attribute_config, $html );
		$this->assertSame( $expected_result, $result );
	}

	/**
	 * Data provider for query source tests.
	 *
	 * @return array
	 */
	public function data_provider_query_source() {
		return [
			'simple list items'                 => [
				'selector'        => 'li',
				'query'           => [
					'content' => [
						'source' => 'text',
					],
				],
				'html'            => '<ul><li>Item 1</li><li>Item 2</li></ul>',
				'expected_count'  => 2,
				'expected_values' => [
					[ 'content' => 'Item 1' ],
					[ 'content' => 'Item 2' ],
				],
			],
			'direct child list items'           => [
				'selector'        => 'ul > li',
				'query'           => [
					'content' => [
						'source' => 'text',
					],
				],
				'html'            => '<ul><li>First</li><li>Second</li><li>Third</li></ul>',
				'expected_count'  => 3,
				'expected_values' => [
					[ 'content' => 'First' ],
					[ 'content' => 'Second' ],
					[ 'content' => 'Third' ],
				],
			],
			'paragraphs with links'             => [
				'selector'        => 'div > p',
				'query'           => [
					'text' => [
						'source' => 'text',
					],
					'url'  => [
						'source'    => 'attribute',
						'selector'  => 'a',
						'attribute' => 'href',
					],
				],
				'html'            => '<div><p><a href="/page1">Link 1</a></p><p><a href="/page2">Link 2</a></p></div>',
				'expected_count'  => 2,
				'expected_values' => [
					[
						'text' => 'Link 1',
						'url'  => '/page1',
					],
					[
						'text' => 'Link 2',
						'url'  => '/page2',
					],
				],
			],
			'figure with images'                => [
				'selector'        => 'figure',
				'query'           => [
					'url' => [
						'source'    => 'attribute',
						'selector'  => 'img',
						'attribute' => 'src',
					],
					'alt' => [
						'source'    => 'attribute',
						'selector'  => 'img',
						'attribute' => 'alt',
					],
				],
				'html'            => '<div><figure><img src="img1.jpg" alt="First" /></figure><figure><img src="img2.jpg" alt="Second" /></figure></div>',
				'expected_count'  => 2,
				'expected_values' => [
					[
						'url' => 'img1.jpg',
						'alt' => 'First',
					],
					[
						'url' => 'img2.jpg',
						'alt' => 'Second',
					],
				],
			],
			'nested article sections'           => [
				'selector'        => 'article > section',
				'query'           => [
					'heading' => [
						'source'   => 'text',
						'selector' => 'h3',
					],
					'content' => [
						'source'   => 'html',
						'selector' => 'p',
					],
				],
				'html'            => '<article><section><h3>Title 1</h3><p>Content <strong>1</strong></p></section><section><h3>Title 2</h3><p>Content <strong>2</strong></p></section></article>',
				'expected_count'  => 2,
				'expected_values' => [
					[
						'heading' => 'Title 1',
						'content' => 'Content <strong>1</strong>',
					],
					[
						'heading' => 'Title 2',
						'content' => 'Content <strong>2</strong>',
					],
				],
			],
			'table rows with cells'             => [
				'selector'        => 'tbody > tr',
				'query'           => [
					'name'  => [
						'source'   => 'text',
						'selector' => 'td:nth-child(1)',
					],
					'value' => [
						'source'   => 'text',
						'selector' => 'td:nth-child(2)',
					],
				],
				'html'            => '<table><tbody><tr><td>Name1</td><td>Value1</td></tr><tr><td>Name2</td><td>Value2</td></tr></tbody></table>',
				'expected_count'  => 2,
				'expected_values' => [
					[
						'name'  => 'Name1',
						'value' => 'Value1',
					],
					[
						'name'  => 'Name2',
						'value' => 'Value2',
					],
				],
			],
			'navigation menu items'             => [
				'selector'        => 'nav > ul > li',
				'query'           => [
					'label' => [
						'source'   => 'text',
						'selector' => 'a',
					],
					'href'  => [
						'source'    => 'attribute',
						'selector'  => 'a',
						'attribute' => 'href',
					],
					'icon'  => [
						'source'    => 'attribute',
						'selector'  => 'span.icon',
						'attribute' => 'data-icon',
					],
				],
				'html'            => '<nav><ul><li><span class="icon" data-icon="home"></span><a href="/">Home</a></li><li><span class="icon" data-icon="about"></span><a href="/about">About</a></li></ul></nav>',
				'expected_count'  => 2,
				'expected_values' => [
					[
						'label' => 'Home',
						'href'  => '/',
						'icon'  => 'home',
					],
					[
						'label' => 'About',
						'href'  => '/about',
						'icon'  => 'about',
					],
				],
			],
			'card components'                   => [
				'selector'        => 'div.card',
				'query'           => [
					'title'       => [
						'source'   => 'text',
						'selector' => 'h4',
					],
					'description' => [
						'source'   => 'html',
						'selector' => 'p',
					],
					'image'       => [
						'source'    => 'attribute',
						'selector'  => 'img',
						'attribute' => 'src',
					],
				],
				'html'            => '<div><div class="card"><img src="card1.jpg" /><h4>Card 1</h4><p>Description <em>one</em></p></div><div class="card"><img src="card2.jpg" /><h4>Card 2</h4><p>Description <em>two</em></p></div></div>',
				'expected_count'  => 2,
				'expected_values' => [
					[
						'title'       => 'Card 1',
						'description' => 'Description <em>one</em>',
						'image'       => 'card1.jpg',
					],
					[
						'title'       => 'Card 2',
						'description' => 'Description <em>two</em>',
						'image'       => 'card2.jpg',
					],
				],
			],
			'definition list items'             => [
				'selector'        => 'dl > div',
				'query'           => [
					'term'       => [
						'source'   => 'text',
						'selector' => 'dt',
					],
					'definition' => [
						'source'   => 'html',
						'selector' => 'dd',
					],
				],
				'html'            => '<dl><div><dt>HTML</dt><dd><strong>HyperText</strong> Markup Language</dd></div><div><dt>CSS</dt><dd><strong>Cascading</strong> Style Sheets</dd></div></dl>',
				'expected_count'  => 2,
				'expected_values' => [
					[
						'term'       => 'HTML',
						'definition' => '<strong>HyperText</strong> Markup Language',
					],
					[
						'term'       => 'CSS',
						'definition' => '<strong>Cascading</strong> Style Sheets',
					],
				],
			],
			'gallery items with multiple attrs' => [
				'selector'        => 'div.gallery > figure',
				'query'           => [
					'src'     => [
						'source'    => 'attribute',
						'selector'  => 'img',
						'attribute' => 'src',
					],
					'alt'     => [
						'source'    => 'attribute',
						'selector'  => 'img',
						'attribute' => 'alt',
					],
					'caption' => [
						'source'   => 'text',
						'selector' => 'figcaption',
					],
					'width'   => [
						'source'    => 'attribute',
						'selector'  => 'img',
						'attribute' => 'width',
					],
				],
				'html'            => '<div class="gallery"><figure><img src="photo1.jpg" alt="Photo 1" width="800" /><figcaption>Caption 1</figcaption></figure><figure><img src="photo2.jpg" alt="Photo 2" width="600" /><figcaption>Caption 2</figcaption></figure></div>',
				'expected_count'  => 2,
				'expected_values' => [
					[
						'src'     => 'photo1.jpg',
						'alt'     => 'Photo 1',
						'caption' => 'Caption 1',
						'width'   => '800',
					],
					[
						'src'     => 'photo2.jpg',
						'alt'     => 'Photo 2',
						'caption' => 'Caption 2',
						'width'   => '600',
					],
				],
			],
			'timeline entries'                  => [
				'selector'        => 'ol.timeline > li',
				'query'           => [
					'date'    => [
						'source'    => 'attribute',
						'selector'  => 'time',
						'attribute' => 'datetime',
					],
					'display' => [
						'source'   => 'text',
						'selector' => 'time',
					],
					'event'   => [
						'source'   => 'html',
						'selector' => 'div.event',
					],
				],
				'html'            => '<ol class="timeline"><li><time datetime="2025-01-01">Jan 1</time><div class="event">Event <strong>One</strong></div></li><li><time datetime="2025-06-15">Jun 15</time><div class="event">Event <strong>Two</strong></div></li></ol>',
				'expected_count'  => 2,
				'expected_values' => [
					[
						'date'    => '2025-01-01',
						'display' => 'Jan 1',
						'event'   => 'Event <strong>One</strong>',
					],
					[
						'date'    => '2025-06-15',
						'display' => 'Jun 15',
						'event'   => 'Event <strong>Two</strong>',
					],
				],
			],
		];
	}

	/**
	 * Test get_attribute with query source.
	 *
	 * @dataProvider data_provider_query_source
	 * @covers ::get_attribute
	 * @covers ::extract_value_from_html
	 *
	 * @param string $selector CSS selector.
	 * @param array  $query Query configuration.
	 * @param string $html HTML content.
	 * @param int    $expected_count Expected number of results.
	 * @param array  $expected_values Expected values.
	 */
	public function test_get_attribute_query_source( $selector, $query, $html, $expected_count, $expected_values ) {
		$attribute = [
			'source'   => 'query',
			'selector' => $selector,
			'query'    => $query,
		];

		$result = $this->data->get_attribute( $attribute, $html );
		$this->assertIsArray( $result );
		$this->assertCount( $expected_count, $result );

		foreach ( $expected_values as $index => $expected ) {
			foreach ( $expected as $key => $value ) {
				$this->assertArrayHasKey( $key, $result[ $index ] );
				$this->assertStringContainsString( $value, $result[ $index ][ $key ] );
			}
		}
	}


	/**
	 * Test get_attribute with type validation.
	 *
	 * @covers ::get_attribute
	 */
	public function test_get_attribute_type_validation() {
		$attribute = [
			'source'   => 'text',
			'selector' => 'p',
			'type'     => 'string',
		];
		$html      = '<p>Test string</p>';

		$result = $this->data->get_attribute( $attribute, $html );
		$this->assertIsString( $result );
		$this->assertSame( 'Test string', $result );
	}

	/**
	 * Data provider for rich-text source tests.
	 *
	 * @return array
	 */
	public function data_provider_rich_text_source() {
		return [
			'div with italic'              => [
				'selector'          => 'div',
				'html'              => '<div><em>Italic</em> text</div>',
				'expected_contains' => '<em>Italic</em>',
			],
			'paragraph with formatting'    => [
				'selector'          => 'p',
				'html'              => '<p><strong>Bold</strong> and <em>italic</em></p>',
				'expected_contains' => '<strong>Bold</strong>',
			],
			'blockquote with nested tags'  => [
				'selector'          => 'blockquote > p',
				'html'              => '<blockquote><p>Quote with <a href="#">link</a></p></blockquote>',
				'expected_contains' => '<a href="#">link</a>',
			],
			'complex formatting'           => [
				'selector'          => 'div.content > p',
				'html'              => '<div class="content"><p>Text with <strong>bold</strong>, <em>italic</em>, and <code>code</code></p></div>',
				'expected_contains' => '<code>code</code>',
			],
			'nested list with links'       => [
				'selector'          => 'li > span',
				'html'              => '<ul><li><span><a href="/link">Link</a> text</span></li></ul>',
				'expected_contains' => '<a href="/link">Link</a>',
			],
			'ID selector'                  => [
				'selector'          => '#main-content',
				'html'              => '<div id="main-content"><strong>Important</strong> content</div>',
				'expected_contains' => '<strong>Important</strong>',
			],
			'descendant combinator'        => [
				'selector'          => 'article section p',
				'html'              => '<article><section><p>Deep <em>nested</em> text</p></section></article>',
				'expected_contains' => '<em>nested</em>',
			],
			'multiple classes'             => [
				'selector'          => 'div.wp-block.has-background',
				'html'              => '<div class="wp-block has-background"><code>Code</code> block</div>',
				'expected_contains' => '<code>Code</code>',
			],
			'attribute existence selector' => [
				'selector'          => 'p[data-custom]',
				'html'              => '<p data-custom="value">Text with <sup>superscript</sup></p>',
				'expected_contains' => '<sup>superscript</sup>',
			],
			'attribute prefix selector'    => [
				'selector'          => 'a[href^="http"]',
				'html'              => '<div><a href="http://example.com">External <em>link</em></a></div>',
				'expected_contains' => '<em>link</em>',
			],
			'nth-child selector'           => [
				'selector'          => 'li:nth-child(2)',
				'html'              => '<ul><li>First</li><li>Second with <strong>bold</strong></li><li>Third</li></ul>',
				'expected_contains' => '<strong>bold</strong>',
			],
			'first-child pseudo'           => [
				'selector'          => 'div > p:first-child',
				'html'              => '<div><p>First <em>paragraph</em></p><p>Second</p></div>',
				'expected_contains' => '<em>paragraph</em>',
			],
			'last-child pseudo'            => [
				'selector'          => 'ul > li:last-child',
				'html'              => '<ul><li>First</li><li>Last <code>item</code></li></ul>',
				'expected_contains' => '<code>item</code>',
			],
			'nested table cell'            => [
				'selector'          => 'table tbody tr td',
				'html'              => '<table><tbody><tr><td>Cell with <strong>content</strong></td></tr></tbody></table>',
				'expected_contains' => '<strong>content</strong>',
			],
			'complex multi-level nesting'  => [
				'selector'          => 'article > div.wrapper > section > div > p',
				'html'              => '<article><div class="wrapper"><section><div><p>Deeply <span class="highlight">nested</span> content</p></div></section></div></article>',
				'expected_contains' => '<span class="highlight">nested</span>',
			],
			'button with icon'             => [
				'selector'          => 'button.wp-block-button__link',
				'html'              => '<button class="wp-block-button__link"><span class="icon">→</span> <strong>Click</strong></button>',
				'expected_contains' => '<strong>Click</strong>',
			],
			'heading with subtitle'        => [
				'selector'          => 'header > h2',
				'html'              => '<header><h2>Main Title <small>subtitle</small></h2></header>',
				'expected_contains' => '<small>subtitle</small>',
			],
			'definition list'              => [
				'selector'          => 'dl > dd',
				'html'              => '<dl><dt>Term</dt><dd>Definition with <em>emphasis</em></dd></dl>',
				'expected_contains' => '<em>emphasis</em>',
			],
		];
	}

	/**
	 * Test get_attribute with rich-text source.
	 *
	 * @dataProvider data_provider_rich_text_source
	 * @covers ::get_attribute
	 * @covers ::extract_value_from_html
	 *
	 * @param string $selector CSS selector.
	 * @param string $html HTML content.
	 * @param string $expected_contains Expected substring in result.
	 */
	public function test_get_attribute_rich_text_source( $selector, $html, $expected_contains ) {
		$attribute = [
			'source'   => 'rich-text',
			'selector' => $selector,
		];

		$result = $this->data->get_attribute( $attribute, $html );
		$this->assertStringContainsString( $expected_contains, $result );
	}

	/**
	 * Test get_attribute without selector.
	 *
	 * @covers ::get_attribute
	 */
	public function test_get_attribute_no_selector() {
		$attribute = [
			'source' => 'text',
		];
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
		$attribute = [
			'source'   => 'query',
			'selector' => 'li',
			'query'    => [
				'content' => [
					'source' => 'text',
				],
			],
		];
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
		add_shortcode(
			'test_shortcode',
			static function () {
				return 'Shortcode output';
			}
		);

		$content = '<!-- wp:paragraph --><p>[test_shortcode]</p><!-- /wp:paragraph -->';
		$result  = $this->data->get_blocks( $content );

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertStringContainsString( 'Shortcode output', $result[0]['rendered'] );

		remove_shortcode( 'test_shortcode' );
	}
}
