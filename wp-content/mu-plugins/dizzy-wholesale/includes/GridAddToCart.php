<?php

namespace DzWholesale;

class GridAddToCart {

    private $handlingProduct;

    public function activate()
    {
        add_filter('woocommerce_add_to_cart_handler', function($handler, $product) {

            if (
                $_SERVER['REQUEST_METHOD'] === 'POST' &&
                isset($_REQUEST['dz-grid']) && 
                $handler === 'variable'
            ) {
                $this->handlingProduct = $product;
                return 'dz_grid_variable';
            }
            return $handler;
        }, 10, 2);

        add_action('woocommerce_add_to_cart_handler_dz_grid_variable', function($url) {

            global $wp;

            $product = $this->handlingProduct;
            $product_id = $product->get_id();

            $availableVariations = $product->get_available_variations();
            
            $attribute_name = $_POST['identity_attribute_name'] ?? null;

            $addedToCart = [];
            $totalQuantity = 0;
            $passedValidation = true;

            foreach ($availableVariations as $variation) {

                $variation_id = $variation['variation_id'];
                $quantity = wc_stock_amount(wp_unslash($_POST["{$variation_id}_quantity"] ?? 0));
                


                if (is_numeric($quantity) && $quantity > 0) {
                    $variations = [ 'Size' => $variation['attributes'][$attribute_name] ];
                    $totalQuantity += $quantity;
                    $passed_validation = apply_filters('woocommerce_add_to_cart_validation',
                        true,
                        $product_id,
                        $quantity,
                        $variation_id,
                        $variations
                    );

                    if ($passed_validation) {
                        $item_key = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variations);
                        if ($item_key !== false) {
                            $addedToCart[$variation_id] = WC()->cart->get_cart_item($item_key);
                        }
                        else {
                            foreach (array_values($addedToCart) as $removeKey) {
                                WC()->cart->remove_cart_item($removeKey);
                            }
                            // wc_add_notice(
                            //     sprintf('Please choose product options by visiting <a href="%1$s" title="%2$s">%2$s</a>.', 
                            //         esc_url( get_permalink( $product->get_id() ) ),
                            //         esc_html( $product->get_name())
                            //     ),
                            //     'error'
                            // );
                        }
                    }
                }
            }

            if (!empty($addedToCart)) {
                $messagePairs = [];
                foreach ($addedToCart as $cart_item_key => $cart_item ) {
                    $messagePairs[$cart_item['variation_id']] = $cart_item['quantity'];
                }
                wc_add_to_cart_message($messagePairs, true);
            }

            $parts = wp_parse_url((wp_get_referer() ?: get_site_url()));
            $query = [];
            parse_str($parts['query'] ?? '', $query);
            $query['dz-nocache'] = esc_url(WC()->cart->get_cart_hash());
            $parts['query'] = http_build_query($query);

            $redirect = "{$parts['scheme']}://{$parts['host']}" . 
                (($parts['port'] ?? false) ? ":{$parts['port']}" : '') .
                ($parts['path'] ?? '') . "?{$parts['query']}" . 
                ($parts['anchor'] ?? false) . "#{$parts['anchor']}";

            wp_safe_redirect($redirect, 303);
            exit;
        });
    }
}