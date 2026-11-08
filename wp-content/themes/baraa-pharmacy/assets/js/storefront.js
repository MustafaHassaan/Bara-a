/* Baraa Pharmacy — storefront interactions */
(function () {
    'use strict';

    /* ── Dark / Light mode toggle ─────────────────────────────── */
    var html       = document.documentElement;
    var toggleBtn  = document.getElementById('baraa-theme-toggle');
    var iconSun    = document.getElementById('baraa-theme-icon-sun');
    var iconMoon   = document.getElementById('baraa-theme-icon-moon');
    var labelEl    = document.getElementById('baraa-theme-label');

    function isDark() {
        return html.classList.contains('dark');
    }

    function applyTheme(dark) {
        if (dark) {
            html.classList.add('dark');
        } else {
            html.classList.remove('dark');
        }

        if (toggleBtn) {
            var lightLabel = toggleBtn.getAttribute('data-light-label') || 'Light';
            var darkLabel  = toggleBtn.getAttribute('data-dark-label')  || 'Dark';
            toggleBtn.setAttribute('aria-pressed', dark ? 'true' : 'false');
            toggleBtn.setAttribute('aria-label', dark ? lightLabel : darkLabel);
        }

        if (iconSun)  { iconSun.classList.toggle('hidden',  !dark); }
        if (iconMoon) { iconMoon.classList.toggle('hidden', dark); }
        if (labelEl)  { labelEl.textContent = dark ? (toggleBtn ? toggleBtn.getAttribute('data-light-label-short') || 'فاتح' : 'Light') : (toggleBtn ? toggleBtn.getAttribute('data-dark-label-short') || 'داكن' : 'Dark'); }
    }

    function saveTheme(dark) {
        var value   = dark ? 'dark' : 'light';
        var expires = new Date();
        expires.setFullYear(expires.getFullYear() + 1);
        var secure  = window.location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = 'baraa_theme=' + value +
            '; path=/; expires=' + expires.toUTCString() +
            '; SameSite=Lax' + secure;
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            var dark = !isDark();
            applyTheme(dark);
            saveTheme(dark);
        });
    }

    /* Sync on system preference change (only when no cookie saved yet) */
    var cookieSet = document.cookie.indexOf('baraa_theme=') !== -1;
    if (!cookieSet && window.matchMedia) {
        var mq = window.matchMedia('(prefers-color-scheme: dark)');
        applyTheme(mq.matches);
        if (typeof mq.addEventListener === 'function') {
            mq.addEventListener('change', function (e) {
                if (document.cookie.indexOf('baraa_theme=') === -1) {
                    applyTheme(e.matches);
                }
            });
        }
    }

    /* ── Cart quantity stepper (used on product page) ────────── */
    document.addEventListener('click', function (e) {
        var btn = e.target && e.target.closest
            ? e.target.closest('[data-quantity-step]')
            : null;

        if (!btn || btn.disabled) return;

        var container = btn.closest('.baraa-qty-stepper, .cart, form.cart');
        var input     = container ? container.querySelector('input.qty, input[name*="quantity"]') : null;
        if (!input) return;

        var step    = Number(input.step)  || 1;
        var current = Number(input.value) || 1;
        var min     = Number(input.min)   || 1;
        var max     = Number(input.max);
        var delta   = Number(btn.getAttribute('data-quantity-step')) * step;
        var next    = Math.max(min, current + delta);
        if (max > 0) next = Math.min(max, next);
        if (next === current) return;

        input.value = String(next);
        input.dispatchEvent(new Event('change', { bubbles: true }));
    });

})();
