<?php
/**
 * Theme setup for YKE Global.
 *
 * @package YKE_Global
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function yke_global_setup() {
	add_editor_style( 'assets/css/editor.css' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'wp-block-styles' );
}
add_action( 'after_setup_theme', 'yke_global_setup' );

function yke_global_assets() {
	$style_path   = get_theme_file_path( 'assets/css/site.css' );
	$script_path  = get_theme_file_path( 'assets/js/site-interactions.js' );
	$style_ver    = file_exists( $style_path ) ? (string) filemtime( $style_path ) : wp_get_theme()->get( 'Version' );
	$script_ver   = file_exists( $script_path ) ? (string) filemtime( $script_path ) : wp_get_theme()->get( 'Version' );

	wp_enqueue_style(
		'yke-global-style',
		get_theme_file_uri( 'assets/css/site.css' ),
		array(),
		$style_ver
	);

	wp_enqueue_script(
		'yke-global-interactions',
		get_theme_file_uri( 'assets/js/site-interactions.js' ),
		array(),
		$script_ver,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'yke_global_assets' );

function yke_global_editor_assets() {
	wp_enqueue_style(
		'yke-global-editor',
		get_theme_file_uri( 'assets/css/editor.css' ),
		array(),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'enqueue_block_editor_assets', 'yke_global_editor_assets' );

function yke_global_register_patterns() {
	$pattern_dir = get_theme_file_path( 'patterns' );
	if ( ! WP_Block_Pattern_Categories_Registry::get_instance()->is_registered( 'yke-sections' ) ) {
		register_block_pattern_category(
			'yke-sections',
			array(
				'label' => __( 'YKE Sections', 'yke-global' ),
			)
		);
	}
	foreach ( glob( $pattern_dir . '/*.php' ) as $pattern ) {
		include $pattern;
	}
}
add_action( 'init', 'yke_global_register_patterns' );
