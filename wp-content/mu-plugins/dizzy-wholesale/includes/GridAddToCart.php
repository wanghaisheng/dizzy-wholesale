<?php

namespace DzWholesale;

class GridAddToCart {

    private $handlingProduct;

    public function activate()
    {
        add_filter('woocommerce_add_to_cart_handler', function($handler, $product) {

            if (isset($_REQUEST['dz-grid']) && $handler === 'variable') {
                $this->handlingProduct = $product;
                return 'dz_grid_variable';
            }
            return $handler;
        }, 10, 2);

        add_action('woocommerce_add_to_cart_handler_dz_grid_variable', function($url) {

            if (
                isset($_REQUEST['variant_ids']) &&
                isset($_REQUEST['quantities'])
            ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

            }

            wc_add_notice(
                sprintf('Please choose product options by visiting <a href="%1$s" title="%2$s">%2$s</a>.'), 
                    esc_url( get_permalink( $product_id ) ),
                    esc_html( $product->get_name()),
                'error'
            );

        });
    }
}