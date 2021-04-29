<?php

global $product;
global $wp;


$identity_attribute_slug = array_keys($product->get_variation_attributes())[0];
$identity_attribute = $product->get_variation_attributes()[$identity_attribute_slug];


$terms = null;
if ( $product && taxonomy_exists( $identity_attribute_slug ) ) {
    // Get terms if this is a taxonomy - ordered. We need the names too.
    $terms = wc_get_product_terms(
        $product->get_id(),
        $identity_attribute_slug,
        array(
            'fields' => 'all',
        )
    );
}

$available_variations = $product->get_available_variations();

$identity_variants = [];
foreach ($available_variations as $variation) {
	$attribute_name = wc_variation_attribute_name($identity_attribute_slug);
	$attribute_value = $variation['attributes'][$attribute_name];
	$identity_variants[$attribute_value] = $variation;
}

$identity_attribute_name = wc_attribute_label($identity_attribute_slug);

$variations_json = wp_json_encode( $available_variations );
$variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );

do_action( 'woocommerce_before_add_to_cart_form' ); ?>

<form class="variations_form cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', "?add-to-cart={$product->get_id()}&dz-grid#dz-archive-row-{$product->get_id()}" ) ); ?>" method="post" enctype='multipart/form-data' data-product_id="<?php echo absint( $product->get_id() ); ?>" data-product_variations="<?php echo $variations_attr; // WPCS: XSS ok. ?>">
	<?php do_action( 'woocommerce_before_variations_form' ); ?>

	<?php if ( empty( $available_variations ) && false !== $available_variations ) : ?>
		<p class="stock out-of-stock"><?php echo esc_html( apply_filters( 'woocommerce_out_of_stock_message', __( 'This product is currently out of stock and unavailable.', 'woocommerce' ) ) ); ?></p>
	<?php else : ?>
		<table class="variations" cellspacing="0">
			<tbody>
				<tr>
					<th><?php echo wc_attribute_label( $identity_attribute_name ); ?></th>
					<th>Quantity</th>
				</tr>
            <?php foreach ( $terms as $term ) : ?>
				<?php
				$variation = wc_get_product($identity_variants[$term->slug]['variation_id'] ?? null);
				if (! $variation) {
					continue;
				}
				$variation_id = $variation->get_id();
				?>
				<tr>
					<td class="label"><label for="<?php echo esc_attr( "{$variation_id}_quantity" ); ?>"><?php echo wc_attribute_label( $term->name ); // WPCS: XSS ok. ?></label></td>
					<td class="value">
						<?php
							if (
									! $variation->get_max_purchase_quantity() &&
									$variation->get_max_purchase_quantity() < $variation->get_min_purchase_quantity()
							) {
								echo "out of stock";
							}
							else {
								woocommerce_quantity_input(
									array(
										'min_value'   => 0,
										'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $variation->get_max_purchase_quantity(), $variation ),
										'input_name'  => esc_attr( "{$variation_id}_quantity" ),
										'input_value' => 0,
										'placeholder' => 0,
									),
									$variation,
								);
								?>
								Max 25
								<?php
							}
						?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<input type="hidden" name="identity_attribute_name" value="<?php echo esc_attr(wc_variation_attribute_name($identity_attribute_slug)); ?>" />
		<input type="submit" class="button dz-grid-add-to-cart" value="Add to cart">
	<?php endif; ?>

	<?php do_action( 'woocommerce_after_variations_form' ); ?>
</form>

<?php
do_action( 'woocommerce_after_add_to_cart_form' );
