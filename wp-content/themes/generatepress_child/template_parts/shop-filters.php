<?php

?>
<div class="dz-product-search-set">
    <header>
        <h2>Search</h2>
    </header>
    <div class="dz-product-search-keywords">
        <?php echo woocommerce_product_search(); ?>
    </div>


    <div class="dz-product-search-filter-set" >
        <h3>Filters</h3>
        <?php
            echo woocommerce_product_search_filter_attribute([
                'attribute' => 'fabric',
                'style' => 'inline',
            ]);
            echo woocommerce_product_search_filter_attribute([
                'attribute' => 'cut',
            ]);
            echo woocommerce_product_search_filter_attribute([
                'attribute' => 'size',
            ]);
            //echo woocommerce_product_filter_products();
        
        ?>
    </div>


</div>
