<?php
/**
 * Plugin Name: dizzy-wholesale
 * Description: site wide customizations for wholesale
 * Version: 0.1
 *
 * @package dizzy-wholesale
 */

require __DIR__ . '/includes/Plugin.php';

$plugin = new \DzWholesale\Plugin(__DIR__);
$plugin->run();



