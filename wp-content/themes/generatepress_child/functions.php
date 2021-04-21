<?php
/**
 * GeneratePress child theme functions and definitions.
 *
 * Add your custom PHP in this file.
 * Only edit this file if you have direct access to it on your server (to fix errors if they happen).
 */


/**
 * Enqueue child styles.
 */
add_action( 'wp_enqueue_scripts', function() {
	wp_enqueue_style( 'generatepress-child', get_stylesheet_directory_uri() . '/style.css', array(), 100 );
});

function dz_archive_row_add_to_cart() {
	global $product;

	if ($product->get_type() === 'variable') {
		$attributes = $product->get_variation_attributes();
		if (count($attributes) === 1) {
			require_once __DIR__ . '/woocommerce/variable-multi-add-to-cart.php';
		}
	}
	else {
		woocommerce_template_loop_add_to_cart();
	}
}

/**
 * Remove products per row from woocommerce customizer section
 */
add_action('customize_register', function($wp_customize) {
	$wp_customize->remove_control('woocommerce_catalog_columns');
});

$template_path = __DIR__ . '/template_parts';

add_action('woocommerce_before_shop_loop', function() use ($template_path) {
	load_template("$template_path/shop-filters.php");
});