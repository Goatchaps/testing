// assets/js/graycart.js
jQuery(document).ready(function($) {
    $('.add-to-cart').click(function(e) {
        e.preventDefault();
        var product_id = $(this).data('product-id');
        $.post(graycartAjax.ajaxurl, {
            action: 'graycart_add_to_cart',
            product_id: product_id,
            quantity: 1,
            nonce: graycartAjax.nonce
        }, function(response) {
            if (response.success) {
                alert('Added to cart!');
            }
        });
    });

    $('.graycart-cart input[type="number"]').change(function() {
        var product_id = $(this).data('product-id');
        var quantity = $(this).val();
        $.post(graycartAjax.ajaxurl, {
            action: 'graycart_update_cart',
            product_id: product_id,
            quantity: quantity,
            nonce: graycartAjax.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            }
        });
    });

    $('.graycart-remove').click(function() {
        var product_id = $(this).data('product-id');
        $.post(graycartAjax.ajaxurl, {
            action: 'graycart_remove_from_cart',
            product_id: product_id,
            nonce: graycartAjax.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            }
        });
    });

    // Basic carousel (add CSS/JS for full functionality, e.g., Slick)
    $('.carousel').slick({ slidesToShow: 4, slidesToScroll: 1 });
});