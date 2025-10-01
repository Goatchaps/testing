<?php
// includes/class-graycart-cart.php
if (!defined('ABSPATH')) {
    exit;
}

class GrayCart_Cart {
    public static function init() {
        add_action('init', [self::class, 'start_session']);
        add_action('wp', [self::class, 'track_viewed_items']);
        add_action('wp_login', [self::class, 'merge_carts_on_login'], 10, 2);
        add_action('wp_logout', [self::class, 'save_cart_on_logout']);
        add_shortcode('graycart_cart', [self::class, 'render_cart']);
        add_shortcode('graycart_recently_viewed', [self::class, 'render_recently_viewed']);
        add_action('wp_ajax_graycart_add_to_cart', [self::class, 'add_to_cart_ajax']);
        add_action('wp_ajax_nopriv_graycart_add_to_cart', [self::class, 'add_to_cart_ajax']);
        add_action('wp_ajax_graycart_update_cart', [self::class, 'update_cart_ajax']);
        add_action('wp_ajax_nopriv_graycart_update_cart', [self::class, 'update_cart_ajax']);
        add_action('wp_ajax_graycart_remove_from_cart', [self::class, 'remove_from_cart_ajax']);
        add_action('wp_ajax_nopriv_graycart_remove_from_cart', [self::class, 'remove_from_cart_ajax']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets']);
    }

    public static function start_session() {
        if (!session_id()) {
            session_start();
        }
    }

    public static function get_cart_id() {
        if (is_user_logged_in()) {
            return get_current_user_id();
        } else {
            if (!isset($_SESSION['graycart_guest_id'])) {
                $_SESSION['graycart_guest_id'] = wp_generate_uuid4();
            }
            return $_SESSION['graycart_guest_id'];
        }
    }

    public static function get_cart() {
        global $wpdb;
        $table = $wpdb->prefix . 'graycart_carts';
        $id = self::get_cart_id();
        $is_guest = !is_user_logged_in();
        $where = $is_guest ? 'guest_id = %s' : 'user_id = %d';
        $cart = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE $where", $id), ARRAY_A);
        return $cart ? json_decode($cart['items'], true) : [];
    }

    public static function save_cart($items) {
        global $wpdb;
        $table = $wpdb->prefix . 'graycart_carts';
        $id = self::get_cart_id();
        $is_guest = !is_user_logged_in();
        $where = $is_guest ? ['guest_id' => $id] : ['user_id' => $id];
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE " . ($is_guest ? 'guest_id = %s' : 'user_id = %d'), $id));
        $data = [
            'items' => wp_json_encode($items),
        ];
        if ($is_guest) {
            $data['guest_id'] = $id;
        } else {
            $data['user_id'] = $id;
        }
        if ($exists) {
            $wpdb->update($table, $data, $where);
        } else {
            $wpdb->insert($table, $data);
        }
    }

    public static function add_to_cart_ajax() {
        check_ajax_referer('graycart_ajax', 'nonce');
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity'] ?? 1);
        $cart = self::get_cart();
        if (isset($cart[$product_id])) {
            $cart[$product_id]['quantity'] += $quantity;
        } else {
            $cart[$product_id] = [
                'quantity' => $quantity,
                'price' => floatval(get_post_meta($product_id, '_graycart_price', true)),
            ];
        }
        self::save_cart($cart);
        wp_send_json_success(['cart' => $cart]);
    }

    public static function update_cart_ajax() {
        check_ajax_referer('graycart_ajax', 'nonce');
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        $cart = self::get_cart();
        if (isset($cart[$product_id])) {
            if ($quantity > 0) {
                $cart[$product_id]['quantity'] = $quantity;
            } else {
                unset($cart[$product_id]);
            }
            self::save_cart($cart);
            wp_send_json_success(['cart' => $cart]);
        }
        wp_send_json_error();
    }

    public static function remove_from_cart_ajax() {
        check_ajax_referer('graycart_ajax', 'nonce');
        $product_id = intval($_POST['product_id']);
        $cart = self::get_cart();
        unset($cart[$product_id]);
        self::save_cart($cart);
        wp_send_json_success(['cart' => $cart]);
    }

    public static function merge_carts_on_login($user_login, $user) {
        $guest_id = $_SESSION['graycart_guest_id'] ?? '';
        if ($guest_id) {
            global $wpdb;
            $table = $wpdb->prefix . 'graycart_carts';
            $guest_cart = $wpdb->get_row($wpdb->prepare("SELECT items FROM $table WHERE guest_id = %s", $guest_id), ARRAY_A);
            $user_cart = $wpdb->get_row($wpdb->prepare("SELECT items FROM $table WHERE user_id = %d", $user->ID), ARRAY_A);
            $merged = array_merge(json_decode($user_cart['items'] ?? '[]', true), json_decode($guest_cart['items'] ?? '[]', true));
            // Merge quantities if duplicates
            foreach ($merged as $id => $item) {
                if (isset($merged[$id])) {
                    $merged[$id]['quantity'] += $item['quantity'];
                }
            }
            self::save_cart($merged);
            $wpdb->delete($table, ['guest_id' => $guest_id]);
            unset($_SESSION['graycart_guest_id']);
        }
    }

    public static function save_cart_on_logout() {
        $cart = self::get_cart();
        self::save_cart($cart);
    }

    public static function render_cart() {
        $cart = self::get_cart();
        ob_start();
        ?>
        <div class="graycart-cart">
            <h2>Your Cart</h2>
            <?php if (empty($cart)): ?>
                <p>Your cart is empty.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart as $id => $item): ?>
                            <tr>
                                <td><?php echo esc_html(get_the_title($id)); ?></td>
                                <td><input type="number" value="<?php echo esc_attr($item['quantity']); ?>" data-product-id="<?php echo esc_attr($id); ?>"></td>
                                <td>$<?php echo esc_html($item['price'] * $item['quantity']); ?></td>
                                <td><button class="graycart-remove" data-product-id="<?php echo esc_attr($id); ?>">Remove</button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function track_viewed_items() {
        if (is_singular('graycart_product')) {
            $product_id = get_the_ID();
            $viewed = array_filter(explode(',', $_COOKIE['graycart_viewed'] ?? ''));
            if (!in_array($product_id, $viewed)) {
                $viewed[] = $product_id;
                setcookie('graycart_viewed', implode(',', $viewed), time() + 3600 * 24 * 30, '/');
            }
        }
    }

    public static function render_recently_viewed() {
        $viewed = array_filter(explode(',', $_COOKIE['graycart_viewed'] ?? ''));
        if (empty($viewed)) return '<p>No recently viewed items.</p>';
        $args = [
            'post_type' => 'graycart_product',
            'post__in' => $viewed,
            'orderby' => 'post__in',
        ];
        $query = new WP_Query($args);
        ob_start();
        ?>
        <div class="graycart-recently-viewed">
            <h2>Recently Viewed</h2>
            <div class="carousel">
                <?php while ($query->have_posts()): $query->the_post(); ?>
                    <div class="item">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        <?php the_post_thumbnail('thumbnail'); ?>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }

    public static function enqueue_assets() {
        wp_enqueue_script('graycart-js', GRAYCART_URL . 'assets/js/graycart.js', ['jquery'], GRAYCART_VERSION, true);
        wp_localize_script('graycart-js', 'graycartAjax', ['ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('graycart_ajax')]);
        wp_enqueue_style('graycart-css', GRAYCART_URL . 'assets/css/graycart.css', [], GRAYCART_VERSION);
    }
}
?>