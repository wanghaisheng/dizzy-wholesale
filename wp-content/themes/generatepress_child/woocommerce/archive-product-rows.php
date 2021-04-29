<?php
/**
 * Replace the content-product template from Woocomerce for usage in the 
 * Dizzy Wholesale row layout for the shop page.
 */

defined( 'ABSPATH' ) || exit;


if ( wc_get_loop_prop( 'total' ) ) {
	while ( have_posts() ) {
		the_post();

		global $product;

		// Ensure visibility.
		if ( empty( $product ) || ! $product->is_visible() ) {
			continue;
		}
		/**
		 * Hook: woocommerce_shop_loop.
		 */
		do_action( 'woocommerce_shop_loop' );

		//wc_get_template_part( 'content', 'product' );
		?>
			<li id="<?php echo "dz-archive-row-{$product->get_id()}"; ?>" <?php wc_product_class( 'dz-archive-row', $product ); ?>>
				<div class="dz-card-minor">
					<?php woocommerce_template_loop_product_link_open(); ?>
						<?php woocommerce_template_loop_product_thumbnail(); ?>
					<?php woocommerce_template_loop_product_link_close(); ?>
				</div>

				<div class="product-details content-bg entry-content-wrap">
					<?php woocommerce_template_loop_product_title(); ?>
					<?php woocommerce_template_loop_price(); ?>
					<?php // woocommerce_template_single_excerpt(); ?>
					<?php dz_archive_row_add_to_cart(); ?>
				</div>
				
				<?php
				
				/**
				 * Hook: woocommerce_before_shop_loop_item.
				 *
				 * @hooked woocommerce_template_loop_product_link_open - 10
				 */
				// do_action( 'woocommerce_before_shop_loop_item' );

				/**
				 * Hook: woocommerce_before_shop_loop_item_title.
				 *
				 * @hooked woocommerce_show_product_loop_sale_flash - 10
				 * @hooked woocommerce_template_loop_product_thumbnail - 10
				 */
				// do_action( 'woocommerce_before_shop_loop_item_title' );

				/**
				 * Hook: woocommerce_shop_loop_item_title.
				 *
				 * @hooked woocommerce_template_loop_product_title - 10
				 */
				// do_action( 'woocommerce_shop_loop_item_title' );

				/**
				 * Hook: woocommerce_after_shop_loop_item_title.
				 *
				 * @hooked woocommerce_template_loop_rating - 5
				 * @hooked woocommerce_template_loop_price - 10
				 */
				// do_action( 'woocommerce_after_shop_loop_item_title' );

				/**
				 * Hook: woocommerce_after_shop_loop_item.
				 *
				 * @hooked woocommerce_template_loop_product_link_close - 5
				 * @hooked woocommerce_template_loop_add_to_cart - 10
				 */
				//do_action( 'woocommerce_after_shop_loop_item' );
				?>
			</li>
		<?php
	}
}