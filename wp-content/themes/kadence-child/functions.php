<?php
/**
 * Enqueue child styles.
 */
add_action( 'wp_enqueue_scripts', function() {
	wp_enqueue_style( 'child-theme', get_stylesheet_directory_uri() . '/style.css', array(), 100 );
});

/**
 * Add custom functions here
 */

add_action( 'init', function() {
	wp_deregister_script('heartbeat');
	wp_deregister_script('kadence-shop-toggle');
}, 1 );