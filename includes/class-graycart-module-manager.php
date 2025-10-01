<?php
// includes/class-graycart-module-manager.php
if (!defined('ABSPATH')) {
    exit;
}

class GrayCart_Module_Manager {
    private static $modules = [];

    public static function init() {
        // Register settings for module toggles
        add_action('admin_init', [self::class, 'register_settings']);
        
        // Allow modules to register
        do_action('graycart_register_module');
    }

    public static function register_module($module_id, $module_class) {
        self::$modules[$module_id] = $module_class;
    }

    public static function get_modules() {
        return self::$modules;
    }

    public static function register_settings() {
        register_setting('graycart_settings', 'graycart_active_modules', [
            'sanitize_callback' => [self::class, 'sanitize_modules']
        ]);

        add_settings_section(
            'graycart_modules',
            __('Modules', 'graycart'),
            null,
            'graycart'
        );

        add_settings_field(
            'active_modules',
            __('Active Modules', 'graycart'),
            [self::class, 'render_module_field'],
            'graycart',
            'graycart_modules'
        );
    }

    public static function render_module_field() {
        $active_modules = get_option('graycart_active_modules', []);
        foreach (self::$modules as $id => $class) {
            ?>
            <label>
                <input type="checkbox" name="graycart_active_modules[<?php echo esc_attr($id); ?>]" value="1" <?php checked(isset($active_modules[$id])); ?>>
                <?php echo esc_html($class::get_name()); ?>
            </label><br>
            <?php
        }
    }

    public static function sanitize_modules($input) {
        $sanitized = [];
        foreach (self::$modules as $id => $class) {
            if (isset($input[$id]) && $input[$id]) {
                $sanitized[$id] = 1;
            }
        }
        return $sanitized;
    }
}
?>