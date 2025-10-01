// assets/js/admin.js
jQuery(document).ready(function($) {
    $('#graycart-generate-invoice').click(function() {
        $.post(graycartAjax.ajaxurl, {
            action: 'graycart_generate_pdf',
            type: 'invoice',
            nonce: graycartAjax.nonce
        }, function() {
            // Download triggered
        });
    });

    $('#graycart-generate-packing-slip').click(function() {
        $.post(graycartAjax.ajaxurl, {
            action: 'graycart_generate_pdf',
            type: 'packing-slip',
            nonce: graycartAjax.nonce
        }, function() {
            // Download triggered
        });
    });

    $('#graycart-export-tax').click(function() {
        $.post(graycartAjax.ajaxurl, {
            action: 'graycart_export_tax_csv',
            nonce: graycartAjax.nonce
        }, function() {
            // Download triggered
        });
    });
});