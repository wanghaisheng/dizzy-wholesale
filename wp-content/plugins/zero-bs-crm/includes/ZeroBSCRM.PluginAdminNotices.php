<?php 
/*!
 * Jetpack CRM
 * https://jetpackcrm.com
 *
 * Date: 31 March 2021
 */


// show/hide admin notice :)
add_action( 'admin_notices', 'jpcrm_woo_promo_admin_notice' );
function jpcrm_woo_promo_admin_notice(){

    global $zbs;
    $bundle = false; if ($zbs->hasFreelancerBundleMin()) $bundle = true;

    //default true if not set
    $display_status = get_option( 'jpcrm_hide_woo_promo', 'show' );

    /* Check transient, if available display notice */
    if ( current_user_can( 'activate_plugins' )){
        
        if(is_plugin_active( 'woocommerce/woocommerce.php' ) && zeroBSCRM_isAdminPage() && !zeroBSCRM_isExtensionInstalled('woosync') && !$bundle && $display_status != "hide"){
            jpcrm_woo_promo_admin_notice_banner();
        }
        
    }else{

    }

}

// admin notice
function jpcrm_woo_promo_admin_notice_banner(){

    ?>
        <div id="woo-promo" class="ui segment jpcrm-promo notice is-dismissible">
            <div class="content">
                <b><?= __('WooCommerce Connect is now free for the first year!', 'zero-bs-crm' ) ?></b>
                <br><?= __('Sync your WooCommerce purchases to Jetpack CRM and continue to nurture your new customers.', 'zero-bs-crm') ?>
            </div>
            <div class="button-group">
                <a href="https://jetpackcrm.com/checkout/?plan=woosync&promo=free&utm_source=core_plugin&utm_medium=plugin&utm_campaign=woofree" target="_blank;" class="button ui green"><?php _e("Get Started for free","zero-bs-crm");?></a>
                <a href="https://jetpackcrm.com/feature/woocommerce/" target="_blank;" class="button ui inverse"><?php _e("Learn more","zero-bs-crm");?></a>
            </div>
        </div>

    <?php

}

