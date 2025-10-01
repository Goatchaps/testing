<?php
// includes/class-graycart-inventory.php
if (!defined('ABSPATH')) {
    exit;
}

class GrayCart_Inventory {
    public static function update_stock($post_id, $quantity, $action = 'decrease') {
        global $wpdb;
        $table = $wpdb->prefix . 'graycart_products';
        $current = $wpdb->get_var($wpdb->prepare("SELECT stock_quantity FROM $table WHERE post_id = %d", $post_id));
        $new_quantity = ($action === 'decrease') ? max(0, $current - $quantity) : $current + $quantity;
        $wpdb->update($table, ['stock_quantity' => $new_quantity], ['post_id' => $post_id], ['%d'], ['%d']);
        update_post_meta($post_id, '_graycart_stock_quantity', $new_quantity);
        do_action('graycart_stock_updated', $post_id, $new_quantity, $action);
    }

    public static function get_margin($post_id) {
        $price = floatval(get_post_meta($post_id, '_graycart_price', true));
        $wholesale = floatval(get_post_meta($post_id, '_graycart_wholesale_price', true));
        if ($price && $wholesale && $price > 0) {
            return round(($price - $wholesale) / $price * 100, 2);
        }
        return 0;
    }

    public static function calculate_tax($price, $tax_rate) {
        return round($price * ($tax_rate / 100), 2);
    }
}
?>