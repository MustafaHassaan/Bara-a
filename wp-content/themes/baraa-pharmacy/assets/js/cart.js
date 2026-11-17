(function () {
    'use strict';

    function updateCartCounts() {
        var quantityInputs = document.querySelectorAll('.woocommerce-cart-form .cart_item input.qty');
        var count = Array.prototype.reduce.call(quantityInputs, function (total, input) {
            return total + (Number(input.value) || 0);
        }, 0);
        var formattedCount = new Intl.NumberFormat(document.documentElement.lang || undefined).format(count);
        var introCount = document.querySelector('.baraa-cart-intro__count');
        var headerCount = document.querySelector('.baraa-header .baraa-cart__count');

        if (introCount) {
            var message = count === 1 ? introCount.dataset.singular : introCount.dataset.plural;
            introCount.textContent = message.replace('%s', formattedCount);
        }

        if (headerCount) {
            headerCount.textContent = formattedCount;
        }
    }

    document.addEventListener('click', function (event) {
        var target = event.target instanceof Element ? event.target : null;

        if (!target) {
            return;
        }

        var clearButton = target.closest('button[name="baraa_clear_cart"]');

        if (clearButton && !window.confirm(clearButton.getAttribute('data-confirm'))) {
            event.preventDefault();
            return;
        }

        var button = target.closest('[data-quantity-step]');

        if (!button || button.disabled) {
            return;
        }

        var item = button.closest('.baraa-cart-item');
        var form = button.closest('.woocommerce-cart-form');
        var input = item ? item.querySelector('input.qty') : null;
        var updateButton = form ? form.querySelector('button[name="update_cart"]') : null;

        if (!input || !form || !updateButton) {
            return;
        }

        var step = Number(input.step) || 1;
        var current = Number(input.value) || Number(input.min) || 1;
        var minimum = Number(input.min) || 1;
        var maximum = Number(input.max);
        var next = current + Number(button.getAttribute('data-quantity-step')) * step;

        next = Math.max(minimum, next);

        if (maximum > 0) {
            next = Math.min(maximum, next);
        }

        if (next === current) {
            return;
        }

        input.value = String(next);
        input.dispatchEvent(new Event('change', { bubbles: true }));
        updateButton.disabled = false;
        updateButton.click();
    });

    if (window.jQuery) {
        window.jQuery(document.body).on('updated_wc_div', updateCartCounts);
    }
})();
