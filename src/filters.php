<?php
/**
 * Filter existing blocks to get data out of markup.
 *
 * @package WP_REST_Blocks.
 */

namespace WP_REST_Blocks\Filter;

use WP_REST_Blocks\Data;


/**
 * Bootstrap filters and actions.
 */
function bootstrap() {
	add_filter( 'block_data_core_image', __NAMESPACE__ . '\\block_data_image' );
	add_filter( 'block_data_core_gallery', __NAMESPACE__ . '\\block_data_gallery' );
	add_filter( 'block_data_core_heading', __NAMESPACE__ . '\\block_data_heading' );
	add_filter( 'block_data_core_paragraph', __NAMESPACE__ . '\\block_data_paragraph' );
	add_filter( 'block_data_core_button', __NAMESPACE__ . '\\block_data_button' );
	add_filter( 'block_data_core_video', __NAMESPACE__ . '\\block_data_video' );
	add_filter( 'block_data_core_audio', __NAMESPACE__ . '\\block_data_audio' );
	add_filter( 'block_data_core_file', __NAMESPACE__ . '\\block_data_file' );
}


/**
 * Hook to gallery block and get attributes.
 *
 * @param array $block Gallery block as array.
 *
 * @return mixed
 */
function block_data_gallery( $block ) {
	$defaults                  = [
		'columns'   => 3,
		'imageCrop' => false,
		'linkTo'    => 'none',
		'ids'       => [],
	];
	$block['attrs']            = wp_parse_args( $block['attrs'], $defaults );
	$columns                   = count( $block['attrs']['ids'] );
	$columns                   = min( $columns, $block['attrs']['columns'] );
	$block['attrs']['columns'] = $columns;

	return $block;
}

/**
 * Hook to image block and get attributes.
 *
 * @param array $block Image block as array.
 *
 * @return mixed
 */
function block_data_image( $block ) {
	$data = [
		'url'        => [
			'type'      => 'string',
			'source'    => 'attribute',
			'selector'  => 'img',
			'attribute' => 'src',
		],
		'alt'        => [
			'type'      => 'string',
			'source'    => 'attribute',
			'selector'  => 'img',
			'attribute' => 'alt',
			'default'   => '',
		],
		'caption'    => [
			'type'     => 'string',
			'source'   => 'html',
			'selector' => 'figcaption',
		],
		'href'       => [
			'type'      => 'string',
			'source'    => 'attribute',
			'selector'  => 'a',
			'attribute' => 'href',
		],
		'rel'        => [
			'type'      => 'string',
			'source'    => 'attribute',
			'selector'  => 'a',
			'attribute' => 'rel',
		],
		'linkClass'  => [
			'type'      => 'string',
			'source'    => 'attribute',
			'selector'  => 'a',
			'attribute' => 'class',
		],
		'linkTarget' => [
			'type'      => 'string',
			'source'    => 'attribute',
			'selector'  => 'a',
			'attribute' => 'target',
		],
	];

	return Data\exact_attrs( $block, $data );
}


/**
 * Hook to heading block and get attributes.
 *
 * @param array $block heading block as array.
 *
 * @return mixed
 */
function block_data_heading( $block ) {
	$data = [
		'anchor' => [
			'type'      => 'string',
			'source'    => 'attribute',
			'selector'  => '(h1|h2|h3|h4|h5|h6)',
			'attribute' => 'id',
		],
	];

	return Data\exact_attrs( $block, $data );
}

/**
 * Hook to paragraph block and get attributes.
 *
 * @param array $block paragraph block as array.
 *
 * @return mixed
 */
function block_data_paragraph( $block ) {
	$data = [
		'text' => [
			'type'     => 'string',
			'source'   => 'html',
			'selector' => 'p',
		],
	];

	return Data\exact_attrs( $block, $data );
}

/**
 * Hook to heading block and get attributes.
 *
 * @param array $block heading block as array.
 *
 * @return mixed
 */
function block_data_button( $block ) {
	$data = [
		'url'   => [
			'type'      => 'string',
			'source'    => 'attribute',
			'selector'  => 'a',
			'attribute' => 'href',
		],
		'title' => [
			'type'      => 'string',
			'source'    => 'attribute',
			'selector'  => 'a',
			'attribute' => 'title',
		],
		'text'  => [
			'type'     => 'string',
			'source'   => 'html',
			'selector' => 'a',
		],
	];

	return Data\exact_attrs( $block, $data );
}


/**
 * Hook to video block and get attributes.
 *
 * @param array $block video block as array.
 *
 * @return mixed
 */
function block_data_video( $block ) {
	$data = [
		'autoplay'    => [
			'type'      => 'boolean',
			'source'    => 'attribute',
			'selector'  => 'video',
			'attribute' => 'autoplay',
		],
		'caption'     => [
			'type'     => 'string',
			'source'   => 'html',
			'selector' => 'figcaption',
		],
		'controls'    => [
			'type'      => 'boolean',
			'source'    => 'attribute',
			'selector'  => 'video',
			'attribute' => 'controls',
			'default'   => true,
		],
		'id'          => [
			'type' => 'number',
		],
		'loop'        => [
			'type'      => 'boolean',
			'source'    => 'attribute',
			'selector'  => 'video',
			'attribute' => 'loop',
		],
		'muted'       => [
			'type'      => 'boolean',
			'source'    => 'attribute',
			'selector'  => 'video',
			'attribute' => 'muted',
		],
		'poster'      => [
			'type'      => 'string',
			'source'    => 'attribute',
			'selector'  => 'video',
			'attribute' => 'poster',
		],
		'preload'     => [
			'type'      => 'string',
			'source'    => 'attribute',
			'selector'  => 'video',
			'attribute' => 'preload',
			'default'   => 'metadata',
		],
		'src'         => [
			'type'      => 'string',
			'source'    => 'attribute',
			'selector'  => 'video',
			'attribute' => 'src',
		],
		'playsInline' => [
			'type'      => 'boolean',
			'source'    => 'attribute',
			'selector'  => 'video',
			'attribute' => 'playsinline',
		],
	];

	return Data\exact_attrs( $block, $data );
}


/**
 * Hook to audio block and get attributes.
 *
 * @param array $block audio block as array.
 *
 * @return mixed
 */
function block_data_auio( $block ) {
	$data = [
		'src'      => [
			'type'      => 'string',
			'source'    => 'attribute',
			'selector'  => 'audio',
			'attribute' => 'src',
		],
		'caption'  => [
			'type'     => 'string',
			'source'   => 'html',
			'selector' => 'figcaption',
		],
		'id'       => [
			'type' => 'number',
		],
		'autoplay' => [
			'type'      => 'boolean',
			'source'    => 'attribute',
			'selector'  => 'audio',
			'attribute' => 'autoplay',
		],
		'loop'     => [
			'type'      => 'boolean',
			'source'    => 'attribute',
			'selector'  => 'audio',
			'attribute' => 'loop',
		],
		'preload'  => [
			'type'      => 'string',
			'source'    => 'attribute',
			'selector'  => 'audio',
			'attribute' => 'preload',
		],
	];

	return Data\exact_attrs( $block, $data );
}

/**
 * Hook to file block and get attributes.
 *
 * @param array $block file block as array.
 *
 * @return mixed
 */
function block_data_file( $block ) {
	$data = [
		'fileName'           => [
			'type'     => 'string',
			'source'   => 'html',
			'selector' => 'a',
		],
		'textLinkHref'       => [
			'type'      => 'string',
			'source'    => 'attribute',
			'selector'  => 'a',
			'attribute' => 'href',
		],
		'textLinkTarget'     => [
			'type'      => 'string',
			'source'    => 'attribute',
			'selector'  => 'a',
			'attribute' => 'target',
		],
		'downloadButtonText' => [
			'type'     => 'string',
			'source'   => 'html',
			'selector' => 'a',
		],
	];

	return Data\exact_attrs( $block, $data );
}

