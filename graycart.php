<?php
/*
Plugin Name: GrayCart
Description: A modular e-commerce plugin for WordPress, similar to WooCommerce, with freemium features.
Version: 1.0.1
Author: Chris Gray
Author URI: https://graycollectibles.com
License: GPL2
Text Domain: graycart
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('GRAYCART_VERSION', '1.0.1');
define('GRAYCART_PATH', plugin_dir_path(__FILE__));
define('GRAYCART_URL', plugin_dir_url(__FILE__));

// Activation hook
register_activation_hook(__FILE__, 'graycart_activate');
function graycart_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Create carts table
    $table_carts = $wpdb->prefix . 'graycart_carts';
    $sql_carts = "CREATE TABLE $table_carts (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED DEFAULT 0,
        guest_id VARCHAR(50) DEFAULT '',
        items LONGTEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY `user_id` (`user_id`),
        KEY `guest_id` (`guest_id`)
    ) $charset_collate;";
    
    // Create logs table
    $table_logs = $wpdb->prefix . 'graycart_logs';
    $sql_logs = "CREATE TABLE $table_logs (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        type VARCHAR(50) NOT NULL,
        data LONGTEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY `log_type` (`type`)
    ) $charset_collate;";

    // Create products table
    $table_products = $wpdb->prefix . 'graycart_products';
    $sql_products = "CREATE TABLE $table_products (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id BIGINT(20) UNSIGNED NOT NULL,
        stock_quantity INT NOT NULL DEFAULT 0,
        is_open_box TINYINT(1) DEFAULT 0,
        open_box_discount DECIMAL(5,2) DEFAULT 0.00,
        product_type VARCHAR(20) DEFAULT 'physical',
        digital_link VARCHAR(255) DEFAULT '',
        digital_codes LONGTEXT,
        PRIMARY KEY  (id),
        KEY `post_id` (`post_id`)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_carts);
    dbDelta($sql_logs);
    dbDelta($sql_products);

    // Set initial version
    update_option('graycart_version', GRAYCART_VERSION);
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'graycart_deactivate');
function graycart_deactivate() {
    // Optional: Clear transients or scheduled tasks
}

// Load core
require_once GRAYCART_PATH . 'includes/class-graycart-core.php';
require_once GRAYCART_PATH . 'includes/class-graycart-products.php';
require_once GRAYCART_PATH . 'includes/class-graycart-inventory.php';
require_once GRAYCART_PATH . 'includes/class-graycart-admin-tools.php';
require_once GRAYCART_PATH . 'includes/class-graycart-cart.php';
require_once GRAYCART_PATH . 'includes/class-graycart-updater.php';
GrayCart_Core::init();
GrayCart_Products::init();
GrayCart_Admin_Tools::init();
GrayCart_Cart::init();
?>