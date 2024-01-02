<?php
/**
 * Umzugmeister Konfigurator
 *
 * Enqueue styles and scripts.
 *
 * @package UmConfigurator
 */

defined( 'ABSPATH' ) || die( 'Kein direkter Zugriff möglich!' );

$mainjs    = null;
$runtimejs = null;
$maincss   = null;

foreach ( glob( dirname( __FILE__, 2 ) . '/app-dist/rcAdmin/*.js' ) as $file ) {
	$temp     = explode( '/', $file );
	$filename = $temp[ count( $temp ) - 1 ];

	if ( strpos( $filename, 'main.' ) !== false ) {
		$mainjs = $filename;
	}

	if ( strpos( $filename, 'runtime.' ) !== false ) {
		$runtimejs = $filename;
	}
}

foreach ( glob( dirname( __FILE__, 2 ) . '/app-dist/rcAdmin/*.css' ) as $file ) {
	$temp     = explode( '/', $file );
	$filename = $temp[ count( $temp ) - 1 ];

	if ( strpos( $filename, 'main.' ) !== false ) {
		$maincss = $filename;
	}
}

if ( $runtimejs ) {
	wp_register_script(
		'runtimejs',
		plugins_url( 'app-dist/rcAdmin/' . $runtimejs, dirname( __FILE__, 1 ) ),
		array( 'jquery' ),
		'1.0',
		true
	);
	wp_enqueue_script( 'runtimejs' );
}

if ( $mainjs ) {
	wp_register_script(
		'mainjs',
		plugins_url( 'app-dist/rcAdmin/' . $mainjs, dirname( __FILE__, 1 ) ),
		array( 'jquery' ),
		'1.0',
		true
	);
	wp_enqueue_script( 'mainjs' );
	
	add_filter("script_loader_tag", "add_module_to_mainjs", 10, 3);
	function add_module_to_mainjs($tag, $handle, $src)
	{
		if ("mainjs" === $handle) {
			$tag = '<script type="module" src="' . esc_url($src) . '"></script>';
		}

		return $tag;
	}

}

if ( $maincss ) {
	wp_register_style(
		'cadminmaincss',
		plugins_url( 'app-dist/rcAdmin/' . $maincss, dirname( __FILE__, 1 ) ),
		null,
		'1.0'
	);
	wp_enqueue_style( 'cadminmaincss' );
}
