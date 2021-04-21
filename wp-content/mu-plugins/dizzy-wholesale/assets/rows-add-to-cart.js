/*global wc_add_to_cart_variation_params */
;(function ( $, window, document, undefined ) {
	/**
	 * VariationForm class which handles variation forms and attributes.
	 */
	class VariationGrid {
        constructor() {
            $('document.body')
                .on('click', '.dz-grid-add-to-cart', this.onAdd.bind(this));
        }

        onAdd() {
            console.log('dz add to cart');
        }
    }

    new VariationGrid();

})( jQuery, window, document );