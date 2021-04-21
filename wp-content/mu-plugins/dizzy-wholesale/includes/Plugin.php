<?php

namespace DzWholesale;

class Plugin {

    private $pluginPath;
    private $templatePath;
    private $assetsUrl;

    public function construct($path)
    {
        $this->pluginPath = $path;
        $this->templatePath =  $path . '/templates';
        $this->assetsUrl = plugin_dir_url($path);
    }


    public function run()
    {
        add_action('woocommerce_init', function() {
            $this->onWooInit();
        });
    }


    private function onWooInit()
    {
        require __DIR__ . '/GridAddToCart.php';

        $grid = new GridAddToCart();
        $grid->activate();

        add_action('wp_enqueue_scripts', function() {
            wp_enqueue_style( 'dizzy-wholesale', $this->getAssetsUrl() . '/style.css', array(), 100 );
        });

        add_filter( 'woocommerce_coupons_enabled', fn() => false);
    }


    public function getAssetsUrl()
    {
        return $this->assetsUrl;
    }
}