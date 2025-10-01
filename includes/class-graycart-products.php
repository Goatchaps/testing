<?php
// includes/class-graycart-products.php
if (!defined('ABSPATH')) {
    exit;
}

class GrayCart_Products {
    public static function init() {
        add_action('init', [self::class, 'register_cpt']);
        add_action('add_meta_boxes', [self::class, 'add_meta_boxes']);
        add_action('save_post_graycart_product', [self::class, 'save_meta']);
    }

    public static function register_cpt() {
        register_post_type('graycart_product', [
            'labels' => [
                'name' => __('Products', 'graycart'),
                'singular_name' => __('Product', 'graycart'),
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'thumbnail'],
            'show_in_menu' => 'graycart',
            'taxonomies' => ['category', 'post_tag'],
        ]);
    }

    public static function add_meta_boxes() {
        add_meta_box(
            'graycart_product_details',
            __('Product Details', 'graycart'),
            [self::class, 'render_meta_box'],
            'graycart_product',
            'normal',
            'high'
        );
    }

    public static function render_meta_box($post) {
        wp_nonce_field('graycart_product_save', 'graycart_nonce');
        $price = get_post_meta($post->ID, '_graycart_price', true);
        $wholesale_price = get_post_meta($post->ID, '_graycart_wholesale_price', true);
        $stock = get_post_meta($post->ID, '_graycart_stock_quantity', true);
        $is_open_box = get_post_meta($post->ID, '_graycart_is_open_box', true);
        $open_box_discount = get_post_meta($post->ID, '_graycart_open_box_discount', true);
        $product_type = get_post_meta($post->ID, '_graycart_product_type', true) ?: 'physical';
        $digital_link = get_post_meta($post->ID, '_graycart_digital_link', true);
        $digital_codes = get_post_meta($post->ID, '_graycart_digital_codes', true);
        ?>
        <p>
            <label><?php _e('Price ($)', 'graycart'); ?></label>
            <input type="number" step="0.01" name="graycart_price" value="<?php echo esc_attr($price); ?>">
        </p>
        <p>
            <label><?php _e('Wholesale Price ($)', 'graycart'); ?></label>
            <input type="number" step="0.01" name="graycart_wholesale_price" value="<?php echo esc_attr($wholesale_price); ?>">
        </p>
        <p>
            <label><?php _e('Stock Quantity', 'graycart'); ?></label>
            <input type="number" name="graycart_stock_quantity" value="<?php echo esc_attr($stock); ?>">
        </p>
        <p>
            <label><?php _e('Open Box', 'graycart'); ?></label>
            <input type="checkbox" name="graycart_is_open_box" value="1" <?php checked($is_open_box, 1); ?>>
        </p>
        <p>
            <label><?php _e('Open Box Discount (%)', 'graycart'); ?></label>
            <input type="number" step="0.01" name="graycart_open_box_discount" value="<?php echo esc_attr($open_box_discount); ?>">
        </p>
        <p>
            <label><?php _e('Product Type', 'graycart'); ?></label>
            <select name="graycart_product_type">
                <option value="physical" <?php selected($product_type, 'physical'); ?>><?php _e('Physical', 'graycart'); ?></option>
                <option value="digital" <?php selected($product_type, 'digital'); ?>><?php _e('Digital', 'graycart'); ?></option>
            </select>
        </p>
        <p>
            <label><?php _e('Digital Download Link', 'graycart'); ?></label>
            <input type="url" name="graycart_digital_link" value="<?php echo esc_attr($digital_link); ?>">
        </p>
        <p>
            <label><?php _e('Digital Codes (one per line)', 'graycart'); ?></label>
            <textarea name="graycart_digital_codes" rows="5"><?php echo esc_textarea($digital_codes); ?></textarea>
        </p>
        <?php
    }

    public static function save_meta($post_id) {
        if (!isset($_POST['graycart_nonce']) || !wp_verify_nonce($_POST['graycart_nonce'], 'graycart_product_save')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $fields = [
            'graycart_price' => 'floatval',
            'graycart_wholesale_price' => 'floatval',
            'graycart_stock_quantity' => 'intval',
            'graycart_is_open_box' => 'intval',
            'graycart_open_box_discount' => 'floatval',
            'graycart_product_type' => 'sanitize_text_field',
            'graycart_digital_link' => 'esc_url_raw',
            'graycart_digital_codes' => 'sanitize_textarea_field',
        ];

        foreach ($fields as $field => $sanitizer) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, call_user_func($sanitizer, $_POST[$field]));
            } else {
                delete_post_meta($post_id, '_' . $field);
            }
        }

        global $wpdb;
        $table = $wpdb->prefix . 'graycart_products';
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE post_id = %d", $post_id));
        $data = [
            'post_id' => $post_id,
            'stock_quantity' => intval($_POST['graycart_stock_quantity'] ?? 0),
            'is_open_box' => intval($_POST['graycart_is_open_box'] ?? 0),
            'open_box_discount' => floatval($_POST['graycart_open_box_discount'] ?? 0),
            'product_type' => sanitize_text_field($_POST['graycart_product_type'] ?? 'physical'),
            'digital_link' => esc_url_raw($_POST['graycart_digital_link'] ?? ''),
            'digital_codes' => sanitize_textarea_field($_POST['graycart_digital_codes'] ?? ''),
        ];

        if ($exists) {
            $wpdb->update($table, $data, ['post_id' => $post_id], ['%d', '%d', '%d', '%f', '%s', '%s', '%s']);
        } else {
            $wpdb->insert($table, $data, ['%d', '%d', '%d', '%f', '%s', '%s', '%s']);
        }
    }
}

// Initialize
GrayCart_Products::init();
?>