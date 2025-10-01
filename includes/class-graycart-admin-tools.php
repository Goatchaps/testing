<?php
// includes/class-graycart-admin-tools.php
use Dompdf\Dompdf;

if (!defined('ABSPATH')) {
    exit;
}

class GrayCart_Admin_Tools {
    public static function init() {
        add_action('admin_menu', [self::class, 'add_submenu']);
        add_action('wp_ajax_graycart_generate_pdf', [self::class, 'generate_pdf']);
        add_action('wp_ajax_graycart_export_tax_csv', [self::class, 'export_tax_csv']);
    }

    public static function add_submenu() {
        add_submenu_page(
            'graycart',
            __('Tools', 'graycart'),
            __('Tools', 'graycart'),
            'manage_options',
            'graycart-tools',
            [self::class, 'render_tools_page']
        );
    }

    public static function render_tools_page() {
        wp_enqueue_script('graycart-admin', GRAYCART_URL . 'assets/js/admin.js', ['jquery'], GRAYCART_VERSION, true);
        wp_localize_script('graycart-admin', 'graycartAjax', ['ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('graycart_tools')]);
        ?>
        <div class="wrap">
            <h1><?php _e('GrayCart Tools', 'graycart'); ?></h1>
            <p><button class="button" id="graycart-generate-invoice"><?php _e('Generate Sample Invoice', 'graycart'); ?></button></p>
            <p><button class="button" id="graycart-generate-packing-slip"><?php _e('Generate Sample Packing Slip', 'graycart'); ?></button></p>
            <p><button class="button" id="graycart-export-tax"><?php _e('Export Tax Report', 'graycart'); ?></button></p>
        </div>
        <?php
    }

    public static function generate_pdf() {
        check_ajax_referer('graycart_tools', 'nonce');
        require_once GRAYCART_PATH . 'vendor/dompdf/autoload.inc.php';

        $type = sanitize_text_field($_POST['type']);
        $dompdf = new Dompdf();
        $html = '<h1>' . ($type === 'invoice' ? 'Invoice' : 'Packing Slip') . '</h1>';
        $html .= '<img src="' . esc_url(get_option('site_logo')) . '" style="max-width:150px;">';
        $html .= '<p>Sample ' . esc_html($type) . ' for testing.</p>';
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4');
        $dompdf->render();
        $dompdf->stream('graycart-' . $type . '.pdf', ['Attachment' => 1]);
        wp_die();
    }

    public static function export_tax_csv() {
        check_ajax_referer('graycart_tools', 'nonce');
        // Placeholder: Sample CSV (real orders in Phase 2, Step 4)
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="graycart-tax-report.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Product ID', 'Price', 'Wholesale', 'Margin', 'Tax']);
        // Sample data
        fputcsv($output, [1, 100, 60, GrayCart_Inventory::get_margin(1), GrayCart_Inventory::calculate_tax(100, 8)]);
        fclose($output);
        wp_die();
    }
}

// Initialize
GrayCart_Admin_Tools::init();
?>