<?php
// includes/class-graycart-core.php
if (!defined('ABSPATH')) {
    exit;
}

class GrayCart_Core {
    private static $instance = null;

    public static function init() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        require_once GRAYCART_PATH . 'includes/class-graycart-module-manager.php';
        GrayCart_Module_Manager::init();
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function add_admin_menu() {
        // Add top-level menu (no callback to allow submenus to handle)
        add_menu_page(
            __('GrayCart', 'graycart'),
            __('GrayCart', 'graycart'),
            'manage_options',
            'graycart',
            null, // No callback here
            'dashicons-cart',
            30
        );

        // Add submenu for main settings (replaces top-level link)
        add_submenu_page(
            'graycart',
            __('Settings', 'graycart'),
            __('Settings', 'graycart'),
            'manage_options',
            'graycart',
            [$this, 'settings_page']
        );

        // Add submenu for logs (temporary lower capability for testing)
        add_submenu_page(
            'graycart',
            __('Logs', 'graycart'),
            __('Logs', 'graycart'),
            'read', // Temporary for test; change back to 'manage_options' if it works
            'graycart-logs',
            [$this, 'render_logs_page']
        );
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('GrayCart Settings', 'graycart'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('graycart_settings');
                do_settings_sections('graycart');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function register_settings() {
        register_setting('graycart_settings', 'graycart_tax_rate', [
            'sanitize_callback' => 'floatval',
            'default' => 0
        ]);

        add_settings_section(
            'graycart_general',
            __('General Settings', 'graycart'),
            null,
            'graycart'
        );

        add_settings_field(
            'tax_rate',
            __('Sales Tax Rate (%)', 'graycart'),
            [$this, 'render_tax_field'],
            'graycart',
            'graycart_general'
        );
    }

    public function render_tax_field() {
        $tax_rate = get_option('graycart_tax_rate', 0);
        ?>
        <input type="number" step="0.01" name="graycart_tax_rate" value="<?php echo esc_attr($tax_rate); ?>">
        <?php
    }

    // Logs functions (consolidated from logger class)
    public static function log($type, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'graycart_logs';
        $wpdb->insert(
            $table,
            [
                'type' => sanitize_text_field($type),
                'data' => wp_json_encode($data),
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s']
        );
    }

    public static function get_logs($type = '', $limit = 50) {
        global $wpdb;
        $table = $wpdb->prefix . 'graycart_logs';
        $where = $type ? $wpdb->prepare('WHERE type = %s', $type) : '';
        $query = "SELECT * FROM $table $where ORDER BY created_at DESC LIMIT %d";
        return $wpdb->get_results($wpdb->prepare($query, $limit), ARRAY_A);
    }

    public function render_logs_page() {
        // Manual capability check (for debug)
        if (!current_user_can('read')) {
            wp_die(__('Sorry, you are not allowed to access this page.', 'graycart'));
        }
        ?>
        <div class="wrap">
            <h1><?php _e('GrayCart Logs', 'graycart'); ?></h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'graycart'); ?></th>
                        <th><?php _e('Type', 'graycart'); ?></th>
                        <th><?php _e('Data', 'graycart'); ?></th>
                        <th><?php _e('Date', 'graycart'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (self::get_logs() as $log): ?>
                        <tr>
                            <td><?php echo esc_html($log['id']); ?></td>
                            <td><?php echo esc_html($log['type']); ?></td>
                            <td><?php echo esc_html($log['data']); ?></td>
                            <td><?php echo esc_html($log['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
?>