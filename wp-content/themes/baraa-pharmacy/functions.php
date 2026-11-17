<?php

if (!defined('ABSPATH')) {
    exit;
}

function baraa_pharmacy_setup()
{
    load_theme_textdomain('baraa-pharmacy', get_template_directory() . '/languages');

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('woocommerce');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script'));

    // Custom Logo Support
    add_theme_support('custom-logo', array(
        'height'      => 100,
        'width'       => 100,
        'flex-width'  => true,
        'flex-height' => true,
        'header-text' => array('site-title', 'site-description'),
    ));
}
add_action('after_setup_theme', 'baraa_pharmacy_setup');

/**
 * Remove unwanted WooCommerce default output:
 *
 * 1. woocommerce_breadcrumb on shop/taxonomy archives — we render our own.
 * 2. woocommerce_breadcrumb on single product — we render our own.
 * 3. The <h1> page title injected before the cart — we render our own heading.
 * 4. Product data tabs — we render a custom tab UI in content-single-product.php.
 */
function baraa_pharmacy_remove_wc_defaults()
{
    // Breadcrumbs
    remove_action('woocommerce_before_main_content',           'woocommerce_breadcrumb', 20);
    remove_action('woocommerce_before_single_product_summary', 'woocommerce_breadcrumb', 20);

    // Cart page title
    add_filter('woocommerce_show_page_title', '__return_false');

    // Remove default WC tabs — our custom tab section handles description/reviews
    remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10);

    // Move upsells + related below our custom tabs (default priority 15 & 20 → 30 & 35)
    remove_action('woocommerce_after_single_product_summary', 'woocommerce_upsell_display',            15);
    remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_related_products',   20);
    add_action(   'woocommerce_after_single_product_summary', 'woocommerce_upsell_display',            30);
    add_action(   'woocommerce_after_single_product_summary', 'woocommerce_output_related_products',   35);

    // Shop toolbar — archive-product.php renders its own Arabic result count and
    // sort dropdown, so drop WooCommerce's duplicate English versions.
    remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count',     20);
    remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30);
}
add_action('wp', 'baraa_pharmacy_remove_wc_defaults');

/**
 * Remove the postcode field from the shipping calculator.
 * Egypt doesn't use postcodes in practice.
 */
add_filter('woocommerce_shipping_calculator_enable_postcode', '__return_false');

/**
 * Disable XML-RPC. WordPress has no XMLRPC_ENABLED constant, and the
 * `xmlrpc_enabled` filter only turns off the methods that require
 * authentication — the `system.*` endpoints keep replying. Block the whole
 * endpoint instead (xmlrpc.php defines XMLRPC_REQUEST before WordPress boots).
 */
function baraa_pharmacy_disable_xmlrpc()
{
    if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
        status_header(403);
        exit;
    }
}
add_action('init', 'baraa_pharmacy_disable_xmlrpc', 1);

/**
 * Add theme settings for hotline number
 */
function baraa_pharmacy_add_theme_settings()
{
    add_action('admin_init', function() {
        add_settings_section(
            'baraa_pharmacy_contact_settings',
            __('إعدادات التواصل', 'baraa-pharmacy'),
            null,
            'general'
        );

        add_settings_field(
            'baraa_pharmacy_hotline_number',
            __('رقم الهوتلاين', 'baraa-pharmacy'),
            function() {
                $value = get_option('baraa_pharmacy_hotline_number', '16840');
                echo '<input type="text" name="baraa_pharmacy_hotline_number" value="' . esc_attr($value) . '" class="regular-text" placeholder="16840">';
            },
            'general',
            'baraa_pharmacy_contact_settings'
        );

        register_setting('general', 'baraa_pharmacy_hotline_number');
    });
}
add_action('admin_menu', 'baraa_pharmacy_add_theme_settings');

/**
 * The store delivers within Egypt only (the checkout country field is removed
 * and the base country is seeded instead), yet WooCommerce still feeds the
 * cart's shipping calculator the full worldwide country list — 250 English
 * labels. Narrow it to Egypt so the calculator matches the rest of the store.
 */
function baraa_pharmacy_egypt_only_shipping_countries($countries)
{
    if (is_admin() && !wp_doing_ajax()) {
        return $countries;
    }

    if (baraa_pharmacy_is_english_visitor()) {
        return isset($countries['EG']) ? array('EG' => 'Egypt') : $countries;
    }

    if (!isset($countries['EG'])) {
        return $countries;
    }

    return array('EG' => 'مصر');
}
add_filter('woocommerce_countries_shipping_countries', 'baraa_pharmacy_egypt_only_shipping_countries');

function baraa_pharmacy_get_visitor_locale()
{
    $supported_locales = array('ar', 'en_US');
    $requested_locale = isset($_GET['baraa_lang']) && is_string($_GET['baraa_lang'])
        ? sanitize_text_field(wp_unslash($_GET['baraa_lang']))
        : '';
    $saved_locale = isset($_COOKIE['baraa_language']) && is_string($_COOKIE['baraa_language'])
        ? sanitize_text_field(wp_unslash($_COOKIE['baraa_language']))
        : '';

    if (in_array($requested_locale, $supported_locales, true)) {
        return $requested_locale;
    }

    return in_array($saved_locale, $supported_locales, true) ? $saved_locale : '';
}

/**
 * Whether the visitor explicitly switched the storefront to English through
 * the header language switcher. Arabic is the storefront default (the site's
 * WordPress locale is en_US with no language pack), so every Arabic mapping
 * filter below steps aside for this single case only.
 */
function baraa_pharmacy_is_english_visitor()
{
    return 'en_US' === baraa_pharmacy_get_visitor_locale();
}

function baraa_pharmacy_filter_visitor_locale($locale)
{
    if (is_admin()) {
        return $locale;
    }

    $visitor_locale = baraa_pharmacy_get_visitor_locale();

    return $visitor_locale !== '' ? $visitor_locale : $locale;
}
add_filter('locale', 'baraa_pharmacy_filter_visitor_locale', 1);

function baraa_pharmacy_apply_visitor_preferences()
{
    if (is_admin()) {
        return;
    }

    $requested_locale = isset($_GET['baraa_lang']) && is_string($_GET['baraa_lang'])
        ? sanitize_text_field(wp_unslash($_GET['baraa_lang']))
        : '';

    if ($requested_locale !== '' && in_array($requested_locale, array('ar', 'en_US'), true)) {
        setcookie(
            'baraa_language',
            $requested_locale,
            time() + YEAR_IN_SECONDS,
            COOKIEPATH ? COOKIEPATH : '/',
            COOKIE_DOMAIN,
            is_ssl(),
            true
        );
        $_COOKIE['baraa_language'] = $requested_locale;
    }

    $visitor_locale = baraa_pharmacy_get_visitor_locale();

    if ($visitor_locale !== '') {
        if (class_exists('WP_Translation_Controller')) {
            $controller = WP_Translation_Controller::get_instance();

            if (method_exists($controller, 'set_locale')) {
                $controller->set_locale($visitor_locale);
            }
        }

        load_default_textdomain($visitor_locale);

        global $l10n;

        foreach (array_keys($l10n) as $domain) {
            if ('default' !== $domain) {
                unload_textdomain($domain, true);
            }
        }

        $GLOBALS['wp_locale'] = new WP_Locale();
        $GLOBALS['wp_locale']->text_direction = 'ar' === $visitor_locale ? 'rtl' : 'ltr';
        unload_textdomain('baraa-pharmacy', true);
        load_textdomain(
            'baraa-pharmacy',
            get_template_directory() . '/languages/baraa-pharmacy-' . $visitor_locale . '.mo'
        );
    }
}
add_action('after_setup_theme', 'baraa_pharmacy_apply_visitor_preferences', 0);

function baraa_pharmacy_language_switch_url($locale)
{
    return add_query_arg('baraa_lang', $locale, remove_query_arg('baraa_lang'));
}

/**
 * Inject Tailwind CDN, Material Symbols, Google Fonts, and the Tailwind
 * config (MD3 colour tokens + spacing/typography scales) into <head>.
 * Using wp_head action at priority 1 keeps them before wp_enqueue_scripts
 * output so Tailwind resolves classes added by WooCommerce scripts.
 */
function baraa_pharmacy_inject_head_assets()
{
    // ألوان سكين حملة العروض: نفس ألوان الهوية في إعدادات Tailwind تحتها،
    // فتغييرها يسري على الموقع كله بدون أي CSS إضافي.
    $baraa_offer_skin = baraa_pharmacy_offer_skin();
    ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<style>@layer base{html,body{margin:0;padding:0;}body{overscroll-behavior:none;}main>:first-child{margin-top:0!important;}main>:last-child{margin-bottom:0!important;}}::-webkit-scrollbar{display:none;}</style>
<script src="https://cdn.tailwindcss.com"></script>
<script id="baraa-tailwind-config">
tailwind.config = {
    darkMode: false,
    theme: {
        extend: {
            colors: {
                /* ── Light mode (Pharmacy Fresh Green) ───────────────────── */
                "inverse-surface":            "#2f3035",
                "primary-fixed":              "#eaf6eb",
                "outline-variant":            "#d2edd4",
                "surface-container-highest":  "#f0f8f1",
                "on-primary-container":       "#ffffff",
                "on-tertiary":                "#ffffff",
                "on-background":              "#1a201b",
                "tertiary":                   "#994100",
                "inverse-on-surface":         "#eef0ff",
                "on-primary":                 "#ffffff",
                "on-primary-fixed":           "#1e5222",
                "surface-dim":                "#e2eee3",
                "inverse-primary":            "#117A65",
                "primary-fixed-dim":          "#c3e7c6",
                "background":                 "#f8fbf8",
                "surface-container":          "#f0f6f0",
                "primary-container":          "<?php echo esc_js($baraa_offer_skin['on'] ? $baraa_offer_skin['primary_container'] : '#117A65'); ?>",
                "surface-variant":            "#e8f2e9",
                "on-error-container":         "#93000a",
                "error-container":            "#ffdad6",
                "surface-tint":               "#16A085",
                "on-secondary":               "#ffffff",
                "surface-container-lowest":   "#ffffff",
                "on-tertiary-container":      "#ffcab0",
                "secondary-fixed-dim":        "#94ccff",
                "secondary-container":        "#e3f2fd",
                "on-surface-variant":         "#3d4d3e",
                "on-tertiary-fixed":          "#341100",
                "tertiary-fixed-dim":         "#ffb690",
                "on-secondary-fixed-variant": "#004b73",
                "on-tertiary-fixed-variant":  "#783200",
                "surface-container-low":      "#f4f8f4",
                "on-primary-fixed-variant":   "#2d7833",
                "on-secondary-container":     "#00476e",
                "on-surface":                 "#131e14",
                "on-secondary-fixed":         "#001d31",
                "secondary-fixed":            "#cce5ff",
                "outline":                    "#6d7e6e",
                "secondary":                  "#16A085",
                "surface":                    "#ffffff",
                "surface-container-high":     "#e8f0e9",
                "error":                      "#ba1a1a",
                "primary":                    "<?php echo esc_js($baraa_offer_skin['on'] ? $baraa_offer_skin['primary'] : '#16A085'); ?>",
                "on-error":                   "#ffffff",
                "tertiary-container":         "#9a4201",
                "surface-bright":             "#f8fbf8",
                "tertiary-fixed":             "#ffdbca"
            },
            borderRadius: {
                DEFAULT: "0.25rem",
                lg:      "0.5rem",
                xl:      "0.75rem",
                full:    "9999px"
            },
            spacing: {
                "space-md": "1rem",
                "space-xs": "0.25rem",
                "gutter":   "1.25rem",
                "space-sm": "0.5rem",
                "margin":   "2rem",
                "space-xl": "2.5rem",
                "space-lg": "1.5rem"
            },
            fontFamily: {
                "display":           ['"IBM Plex Sans Arabic, IBM Plex Sans, sans-serif"'],
                "headline-lg":       ['"IBM Plex Sans Arabic, IBM Plex Sans, sans-serif"'],
                "headline-md":       ['"IBM Plex Sans Arabic, IBM Plex Sans, sans-serif"'],
                "title-lg":          ['"IBM Plex Sans Arabic, IBM Plex Sans, sans-serif"'],
                "body-lg":           ['"IBM Plex Sans Arabic, IBM Plex Sans, sans-serif"'],
                "body-md":           ['"IBM Plex Sans Arabic, IBM Plex Sans, sans-serif"'],
                "title-md":          ["IBM Plex Sans"],
                "label-sm":          ["IBM Plex Sans"],
                "label-md":          ["IBM Plex Sans"],
                "body-sm":           ["IBM Plex Sans"],
                "display-mobile":    ["IBM Plex Sans"],
                "headline-lg-mobile":["IBM Plex Sans"]
            },
            fontSize: {
                "display":            ["40px",{"lineHeight":"52px","fontWeight":"700"}],
                "headline-lg":        ["32px",{"lineHeight":"44px","fontWeight":"600"}],
                "headline-md":        ["24px",{"lineHeight":"34px","fontWeight":"600"}],
                "title-lg":           ["18px",{"lineHeight":"26px","fontWeight":"600"}],
                "title-md":           ["16px",{"lineHeight":"24px","fontWeight":"600"}],
                "body-lg":            ["16px",{"lineHeight":"26px","fontWeight":"400"}],
                "body-md":            ["14px",{"lineHeight":"22px","fontWeight":"400"}],
                "body-sm":            ["12px",{"lineHeight":"18px","fontWeight":"400"}],
                "label-md":           ["14px",{"lineHeight":"20px","fontWeight":"600"}],
                "label-sm":           ["11px",{"lineHeight":"16px","fontWeight":"500"}],
                "display-mobile":     ["32px",{"lineHeight":"40px","fontWeight":"700"}],
                "headline-lg-mobile": ["26px",{"lineHeight":"34px","fontWeight":"600"}]
            }
        }
    }
};
</script>
    <?php
}
add_action('wp_head', 'baraa_pharmacy_inject_head_assets', 1);

/**
 * Add thumbnail field to product categories
 */
function baraa_add_category_thumbnail_field() {
    ?>
    <div class="form-field term-thumbnail-wrap">
        <label for="thumbnail_id"><?php esc_html_e('صورة الفئة', 'baraa-pharmacy'); ?></label>
        <div id="category-thumbnail-preview" style="float: left; margin-right: 10px;">
            <?php
            $term_id = isset($_GET['tag_ID']) ? intval($_GET['tag_ID']) : 0;
            $thumbnail_id = get_term_meta($term_id, 'thumbnail_id', true);
            if ($thumbnail_id) {
                echo wp_get_attachment_image($thumbnail_id, 'thumbnail');
            }
            ?>
        </div>
        <input type="hidden" id="thumbnail_id" name="thumbnail_id" value="<?php echo esc_attr($thumbnail_id ?? ''); ?>">
        <button type="button" class="upload-thumbnail-button button"><?php esc_html_e('اختر صورة', 'baraa-pharmacy'); ?></button>
        <button type="button" class="remove-thumbnail-button button"><?php esc_html_e('حذف الصورة', 'baraa-pharmacy'); ?></button>
    </div>
    <?php
}
add_action('product_cat_add_form_fields', 'baraa_add_category_thumbnail_field');
add_action('product_cat_edit_form_fields', 'baraa_add_category_thumbnail_field');

/**
 * Save category thumbnail
 */
function baraa_save_category_thumbnail($term_id) {
    if (isset($_POST['thumbnail_id']) && !empty($_POST['thumbnail_id'])) {
        $thumbnail_id = absint($_POST['thumbnail_id']);
        update_term_meta($term_id, 'thumbnail_id', $thumbnail_id);
    }
}
add_action('created_product_cat', 'baraa_save_category_thumbnail');
add_action('edited_product_cat', 'baraa_save_category_thumbnail');

/**
 * Enqueue media uploader for category thumbnails
 */
function baraa_category_thumbnail_enqueue_scripts($hook) {
    if ('edit-tags.php' !== $hook && 'term.php' !== $hook) {
        return;
    }
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Upload button click
        var uploadBtns = document.querySelectorAll('.upload-thumbnail-button');
        uploadBtns.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Create media uploader if not exists
                if (typeof window.baraaMediaUploader === 'undefined') {
                    window.baraaMediaUploader = wp.media({
                        title: '<?php esc_html_e('اختر صورة للفئة', 'baraa-pharmacy'); ?>',
                        button: { text: '<?php esc_html_e('استخدام هذه الصورة', 'baraa-pharmacy'); ?>' },
                        multiple: false
                    });
                }
                
                window.baraaMediaUploader.on('select', function() {
                    var attachment = window.baraaMediaUploader.state().get('selection').first().toJSON();
                    var thumbnailId = document.getElementById('thumbnail_id');
                    var preview = document.getElementById('category-thumbnail-preview');
                    
                    if (thumbnailId) {
                        thumbnailId.value = attachment.id;
                    }
                    if (preview) {
                        preview.innerHTML = '<img src="' + attachment.url + '" style="max-width: 100px;" />';
                    }
                }).open();
            });
        });

        // Remove button click
        var removeBtns = document.querySelectorAll('.remove-thumbnail-button');
        removeBtns.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var thumbnailId = document.getElementById('thumbnail_id');
                var preview = document.getElementById('category-thumbnail-preview');
                
                if (thumbnailId) {
                    thumbnailId.value = '';
                }
                if (preview) {
                    preview.innerHTML = '';
                }
            });
        });
    });
    </script>
    <?php
}
add_action('admin_enqueue_scripts', 'baraa_category_thumbnail_enqueue_scripts');

/**
 * Add "Requires Prescription" field to WooCommerce product admin
 */
function baraa_add_rx_product_field() {
    woocommerce_wp_checkbox(
        array(
            'id'          => '_requires_prescription',
            'label'       => __('يتطلب روشتة', 'baraa-pharmacy'),
            'desc_tip'    => true,
            'description' => __('حدد هذا المنتج إذا كان يتطلب روشتة طبية للشراء', 'baraa-pharmacy'),
        )
    );
}
add_action('woocommerce_product_options_general_product_data', 'baraa_add_rx_product_field');

/**
 * Save "Requires Prescription" field
 */
function baraa_save_rx_product_field($post_id) {
    $checkbox = isset($_POST['_requires_prescription']) ? 'yes' : 'no';
    update_post_meta($post_id, '_requires_prescription', $checkbox);
}
add_action('woocommerce_process_product_meta', 'baraa_save_rx_product_field');

/**
 * WooCommerce element overrides — normalize WC default styles to match the
 * MD3 design tokens coming from Tailwind. Injected inline to avoid a
 * separate HTTP request and to load after Tailwind CDN.
 */
function baraa_pharmacy_wc_style_overrides()
{
    ?>
<style id="baraa-wc-overrides">
/* ── Global resets ────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; }

/* ── Hide WooCommerce auto-injected page titles ─────────────
   The cart page renders its own h1 inside cart.php.
   The shop/product pages render their own headings too.
   We hide any WC-injected .page-title or entry-title.        */
.woocommerce-cart    .page-title,
.woocommerce-cart    .entry-title,
.woocommerce-cart    h1.woocommerce-page-title,
body.woocommerce-cart > main h1:not([id]),
.woocommerce-page    .woocommerce-page-title { display: none !important; }

/* Hide the WC default breadcrumb globally — we use our own   */
.woocommerce-breadcrumb { display: none !important; }
body { font-family: "IBM Plex Sans Arabic", "IBM Plex Sans", Tahoma, Arial, sans-serif; }

/* ── Custom Logo Styling ─────────────────────────────────── */
.custom-logo-link img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    max-width: none;
}
.custom-logo-link {
    display: block;
    width: 100%;
    height: 100%;
}

/* ── Prescription Upload Validation ────────────────────── */
.wc-proceed-to-checkout a.checkout-button.opacity-50 {
    opacity: 0.5;
    pointer-events: none;
    cursor: not-allowed;
}

/* ── Hide default WooCommerce pagination outside custom container ── */
main .woocommerce-pagination:not(.baraa-wc-pagination .woocommerce-pagination) {
    display: none !important;
}

#baraa-rx-upload-label.ring-4 {
    box-shadow: 0 0 0 4px rgba(186, 26, 26, 0.5);
}

/* ── Product Gallery Images Fix for Production ───────────── */
/* Force gallery images to always display correctly on all screen sizes */
.woocommerce-product-gallery__image img,
.woocommerce-product-gallery__image--placeholder img {
    width: 100% !important;
    height: auto !important;
    max-width: 100% !important;
    display: block !important;
    object-fit: contain !important;
    max-height: none !important;
}

/* Fix gallery wrapper to prevent image hiding */
.woocommerce-product-gallery__wrapper {
    width: 100% !important;
    max-width: 100% !important;
}

.woocommerce-product-gallery {
    width: 100% !important;
    max-width: 100% !important;
}

/* Ensure gallery container has proper dimensions */
.woocommerce-product-gallery .woocommerce-product-gallery__image {
    width: 100% !important;
    height: auto !important;
    max-width: 100% !important;
}

/* ── Brand Colors & Component Enforcements (#16A085 & #117A65) ──
 * Light theme only. No dark mode.
 * Header, Category bar, Footer with crisp white text.
 * Card buttons styled with #16A085 (hover #117A65) and white text.
 * ─────────────────────────────────────────────────────────── */

/* ── Header ──────────────────────────────────────────────── */
header.fixed {
    background-color: #16A085 !important;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08) !important;
}
header.fixed,
header.fixed span:not([class*="text-gray"]):not([class*="text-on-surface"]):not([class*="bg-red"]),
header.fixed a:not([class*="bg-white"]):not([class*="text-gray"]),
header.fixed nav a,
header.fixed .font-title-lg,
header.fixed .font-label-sm {
    color: #ffffff !important;
}
header.fixed input {
    color: #1a201b !important;
}

/* ── Category Navigation Bar ─────────────────────────────── */
header.fixed .h-12.w-full {
    background-color: #117A65 !important;
    border-top: 1px solid rgba(255, 255, 255, 0.15) !important;
}
header.fixed .h-12.w-full nav a,
header.fixed .h-12.w-full a {
    color: #ffffff !important;
}

/* ── Footer ──────────────────────────────────────────────── */
footer,
footer.w-full {
    background-color: #16A085 !important;
    border-top: 4px solid #117A65 !important;
    color: #ffffff !important;
}
footer h4,
footer p,
footer span,
footer a,
footer div {
    color: #ffffff !important;
}

/* ── Card & Storefront Buttons ───────────────────────────── */
.woocommerce ul.products li.product .button,
.woocommerce ul.products li.product a.button,
.woocommerce ul.products li.product .add_to_cart_button,
article .button,
.product-card .button,
article a[class*="add_to_cart"],
.product-card a[class*="add_to_cart"],
.woocommerce a.checkout-button,
.wc-proceed-to-checkout .checkout-button,
.woocommerce div.product form.cart .single_add_to_cart_button,
.woocommerce button.button,
.woocommerce a.button {
    background-color: #16A085 !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    transition: all 0.2s ease !important;
}
.woocommerce ul.products li.product .button:hover,
.woocommerce ul.products li.product a.button:hover,
.woocommerce ul.products li.product .add_to_cart_button:hover,
article .button:hover,
.product-card .button:hover,
article a[class*="add_to_cart"]:hover,
.product-card a[class*="add_to_cart"]:hover,
.woocommerce a.checkout-button:hover,
.wc-proceed-to-checkout .checkout-button:hover,
.woocommerce div.product form.cart .single_add_to_cart_button:hover,
.woocommerce button.button:hover,
.woocommerce a.button:hover {
    background-color: #117A65 !important;
    color: #ffffff !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 14px rgba(61, 157, 67, 0.25) !important;
}

/* ── WooCommerce notices ──────────────────────────────────── */
.woocommerce-message,
.woocommerce-info,
.woocommerce-error {
    border: 0 !important;
    border-radius: 0.75rem !important;
    background: #D4E6F1 !important;
    color: #131b2e !important;
    font-family: inherit !important;
    padding: 1rem 1.25rem !important;
    margin-bottom: 1rem !important;
}
.woocommerce-error { background: #ffdad6 !important; color: #93000a !important; }

/* ── Single product summary ──────────────────────────────── */
.woocommerce div.product p.price,
.woocommerce div.product span.price {
    color: #16A085 !important;
    font-size: 1.875rem !important;
    font-weight: 700 !important;
    background: #D4E6F1 !important;
    border-radius: 0.75rem !important;
    padding: 0.875rem 1.125rem !important;
    display: flex !important;
    align-items: baseline !important;
    gap: 0.5rem !important;
    flex-wrap: wrap !important;
    margin: 0 !important;
}
.woocommerce div.product p.price del,
.woocommerce div.product span.price del { color: #6d7a77 !important; font-size: 1rem !important; font-weight: 400 !important; }
.woocommerce div.product p.price ins,
.woocommerce div.product span.price ins { text-decoration: none !important; }
.woocommerce div.product .price .woocommerce-Price-currencySymbol { font-size: 1rem !important; font-weight: 400 !important; }

/* Price suffix — official pricing label */
.woocommerce div.product .price::after {
    content: attr(data-price-label);
    display: block;
    font-size: 0.6875rem;
    font-weight: 400;
    color: #6d7a77;
    width: 100%;
}

.woocommerce div.product .product_title {
    font-size: clamp(1.375rem, 2.5vw, 1.875rem) !important;
    font-weight: 700 !important;
    line-height: 1.35 !important;
    color: #131b2e !important;
    margin: 0 0 0.5rem !important;
    letter-spacing: -0.01em !important;
}
.woocommerce div.product .woocommerce-product-details__short-description {
    color: #3d4947 !important;
    font-size: 0.9375rem !important;
    line-height: 1.75 !important;
    margin-top: 0.5rem !important;
}
.woocommerce div.product .woocommerce-product-rating {
    display: flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
    margin: 0 !important;
}
.woocommerce div.product .star-rating { color: #f59e0b !important; float: none !important; margin: 0 !important; }
.woocommerce div.product .woocommerce-review-link { font-size: 0.75rem !important; color: #6d7a77 !important; }

/* Stock */
.woocommerce div.product .stock {
    font-size: 0.875rem !important;
    font-weight: 600 !important;
    margin: 0 !important;
    padding: 0 !important;
    display: none !important; /* Hidden — we render our own stock UI above */
}

/* Cart form */
.woocommerce div.product form.cart {
    display: flex !important;
    flex-wrap: wrap !important;
    align-items: center !important;
    gap: 0.75rem !important;
    margin: 0 !important;
    padding: 0 !important;
}
.woocommerce div.product form.cart .quantity { margin: 0 !important; }
.woocommerce div.product form.cart .qty {
    border: 1px solid #bec9c6 !important;
    border-radius: 0.75rem !important;
    background: #f4f3fa !important;
    color: #131b2e !important;
    font-family: inherit !important;
    font-size: 1rem !important;
    font-weight: 700 !important;
    min-height: 48px !important;
    width: 90px !important;
    text-align: center !important;
    padding: 0 0.5rem !important;
    transition: border-color 0.2s ease !important;
}
.woocommerce div.product form.cart .qty:focus { border-color: #16A085 !important; outline: none !important; }

.woocommerce div.product form.cart .single_add_to_cart_button,
.woocommerce div.product form.cart button[type="submit"] {
    background: #16A085 !important;
    color: #fff !important;
    border: 0 !important;
    border-radius: 0.75rem !important;
    font-family: inherit !important;
    font-size: 0.9375rem !important;
    font-weight: 700 !important;
    min-height: 48px !important;
    padding: 0.75rem 1.75rem !important;
    cursor: pointer !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
    transition: background-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease !important;
    flex: 1 !important;
}
.woocommerce div.product form.cart .single_add_to_cart_button:hover {
    background: #0E6655 !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 16px rgba(61,157,67,.25) !important;
}

/* Variations */
.woocommerce div.product form.cart .variations { border: 0 !important; margin: 0 0 0.75rem !important; }
.woocommerce div.product form.cart .variations th,
.woocommerce div.product form.cart .variations td { background: transparent !important; border: 0 !important; padding: 0.25rem 0 !important; text-align: start !important; display: block !important; }
.woocommerce div.product form.cart .variations label { font-weight: 600 !important; color: #131b2e !important; }
.woocommerce div.product form.cart .variations select {
    background: #f4f3fa !important;
    border: 1px solid #bec9c6 !important;
    border-radius: 0.75rem !important;
    color: #131b2e !important;
    font: inherit !important;
    min-height: 44px !important;
    padding: 0.5rem 0.875rem !important;
    max-width: 100% !important;
}
.woocommerce div.product form.cart .variations select:focus { border-color: #16A085 !important; outline: none !important; }

/* Product meta */
.woocommerce div.product .product_meta {
    font-size: 0.75rem !important;
    color: #6d7a77 !important;
    border-top: 1px solid #dde8e6 !important;
    padding-top: 0.75rem !important;
    margin-top: 0.75rem !important;
    display: grid !important;
    gap: 0.375rem !important;
}
.woocommerce div.product .product_meta > span { display: block !important; }
.woocommerce div.product .product_meta a { color: #16A085 !important; }

/* Dark mode overrides for product summary */
html.dark .woocommerce div.product p.price,
html.dark .woocommerce div.product span.price { background: #0d2420 !important; color: #5ecfc2 !important; }
html.dark .woocommerce div.product .product_title { color: #dff0ed !important; }
html.dark .woocommerce div.product .woocommerce-product-details__short-description { color: #8db5b0 !important; }
html.dark .woocommerce div.product form.cart .qty { background: #1a2e2c !important; border-color: rgba(80,130,125,.5) !important; color: #dff0ed !important; }
html.dark .woocommerce div.product form.cart .qty:focus { border-color: #5ecfc2 !important; }
html.dark .woocommerce div.product form.cart .single_add_to_cart_button { background: #5ecfc2 !important; color: #002926 !important; }
html.dark .woocommerce div.product form.cart .single_add_to_cart_button:hover { background: #89f5e7 !important; }
html.dark .woocommerce div.product .product_meta { color: #6a9e99 !important; border-top-color: rgba(80,130,125,.3) !important; }
html.dark .woocommerce div.product .product_meta a { color: #5ecfc2 !important; }
html.dark .woocommerce div.product form.cart .variations select { background: #1a2e2c !important; border-color: rgba(80,130,125,.5) !important; color: #dff0ed !important; }

/* ── Product tabs ────────────────────────────────────────── */
/* Hide the default WC tab UI entirely — we use our custom baraa-tab-btn UI */
.woocommerce div.product .woocommerce-tabs.wc-tabs-wrapper { display: none !important; }

/* ── Product description prose ────────────────────────────── */
/* Applied inside our custom "دواعي الاستعمال" tab panel */
.prose-pharmacy h1, .prose-pharmacy h2, .prose-pharmacy h3 {
    color: #131b2e !important;
    font-family: inherit !important;
    font-weight: 700 !important;
    margin-top: 1.25rem !important;
    margin-bottom: 0.5rem !important;
}
.prose-pharmacy p  { margin-bottom: 0.75rem !important; line-height: 1.8 !important; }
.prose-pharmacy ul, .prose-pharmacy ol {
    padding-inline-start: 1.25rem !important;
    margin-bottom: 0.75rem !important;
    line-height: 1.8 !important;
}
.prose-pharmacy li { margin-bottom: 0.375rem !important; }
.prose-pharmacy a  { color: #16A085 !important; }
html.dark .prose-pharmacy h1,
html.dark .prose-pharmacy h2,
html.dark .prose-pharmacy h3 { color: #dff0ed !important; }
html.dark .prose-pharmacy a  { color: #5ecfc2 !important; }

/* ── Reviews tab (WC comment form + list) ─────────────────── */
.baraa-tab-panel #reviews .woocommerce-Reviews-title {
    font-family: inherit !important;
    font-size: 1.125rem !important;
    font-weight: 700 !important;
    color: #131b2e !important;
    margin-bottom: 1rem !important;
}
.baraa-tab-panel #reviews .comment-respond label {
    color: #131b2e !important;
    font-family: inherit !important;
    font-size: 0.875rem !important;
    font-weight: 600 !important;
}
.baraa-tab-panel #reviews .comment-respond input:not([type="submit"]):not([type="radio"]):not([type="hidden"]),
.baraa-tab-panel #reviews .comment-respond textarea {
    background: #f4f3fa !important;
    border: 1px solid #bec9c6 !important;
    border-radius: 0.5rem !important;
    color: #131b2e !important;
    font-family: inherit !important;
    font-size: 0.875rem !important;
    padding: 0.625rem 0.75rem !important;
    width: 100% !important;
}
.baraa-tab-panel #reviews .comment-respond input:focus,
.baraa-tab-panel #reviews .comment-respond textarea:focus {
    border-color: #16A085 !important;
    outline: none !important;
}
.baraa-tab-panel #reviews .comment-respond .submit {
    background: #16A085 !important;
    border: 0 !important;
    border-radius: 0.5rem !important;
    color: #fff !important;
    cursor: pointer !important;
    font-family: inherit !important;
    font-size: 0.875rem !important;
    font-weight: 700 !important;
    padding: 0.625rem 1.25rem !important;
    transition: background-color 0.2s ease !important;
}
.baraa-tab-panel #reviews .comment-respond .submit:hover { background: #0E6655 !important; }
.baraa-tab-panel #reviews .stars a { color: #f59e0b !important; }
.baraa-tab-panel #reviews .woocommerce-noreviews {
    color: #6d7a77 !important;
    font-family: inherit !important;
    font-size: 0.875rem !important;
}
/* Dark mode */
html.dark .baraa-tab-panel #reviews .woocommerce-Reviews-title { color: #dff0ed !important; }
html.dark .baraa-tab-panel #reviews .comment-respond label { color: #dff0ed !important; }
html.dark .baraa-tab-panel #reviews .comment-respond input:not([type="submit"]):not([type="radio"]):not([type="hidden"]),
html.dark .baraa-tab-panel #reviews .comment-respond textarea { background: #1a2e2c !important; border-color: rgba(80,130,125,.5) !important; color: #dff0ed !important; }
html.dark .baraa-tab-panel #reviews .comment-respond .submit { background: #5ecfc2 !important; color: #002926 !important; }

/* ── Related / upsells ──────────────────────────────────── */
.woocommerce .related ul.products,
.woocommerce .upsells ul.products {
    display: grid !important;
    grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
    gap: 1rem !important;
    margin: 0 !important;
}
@media (max-width: 900px) {
    .woocommerce .related ul.products,
    .woocommerce .upsells ul.products { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
}
.woocommerce .related ul.products::before,
.woocommerce .related ul.products::after,
.woocommerce .upsells ul.products::before,
.woocommerce .upsells ul.products::after { display: none !important; }
.woocommerce .related ul.products li.product,
.woocommerce .upsells ul.products li.product {
    background: #ffffff !important;
    border: 1px solid #bec9c6 !important;
    border-radius: 0.75rem !important;
    float: none !important;
    margin: 0 !important;
    padding: 0.875rem !important;
    width: auto !important;
}
.woocommerce .related ul.products li.product .woocommerce-loop-product__title { color: #131b2e !important; font-size: 0.875rem !important; }
.woocommerce .related ul.products li.product .price,
.woocommerce .upsells ul.products li.product .price { color: #16A085 !important; }
.woocommerce .related ul.products li.product .button,
.woocommerce .upsells ul.products li.product .button {
    background: #16A085 !important;
    border-radius: 0.5rem !important;
    color: #fff !important;
    font-family: inherit !important;
    font-size: 0.75rem !important;
    padding: 0.5rem 0.875rem !important;
}

/* ── Shop pagination ─────────────────────────────────────── */
/* Scoped to .baraa-wc-pagination to avoid conflicts */
.baraa-wc-pagination .woocommerce-pagination ul {
    border: 0 !important;
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 0.25rem !important;
    justify-content: center !important;
    padding: 0 !important;
    margin: 0 !important;
}
.baraa-wc-pagination .woocommerce-pagination ul li { border: 0 !important; float: none !important; }
.baraa-wc-pagination .woocommerce-pagination ul li a,
.baraa-wc-pagination .woocommerce-pagination ul li span {
    background: #f4f3fa !important;
    border-radius: 0.5rem !important;
    color: #131b2e !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-family: inherit !important;
    font-size: 0.875rem !important;
    font-weight: 600 !important;
    height: 36px !important;
    min-width: 36px !important;
}
.baraa-wc-pagination .woocommerce-pagination ul li span.current,
.baraa-wc-pagination .woocommerce-pagination ul li a:hover {
    background: #16A085 !important;
    color: #fff !important;
}
html.dark .baraa-wc-pagination .woocommerce-pagination ul li a,
html.dark .baraa-wc-pagination .woocommerce-pagination ul li span { background: #1a2e2c !important; color: #dff0ed !important; }
html.dark .baraa-wc-pagination .woocommerce-pagination ul li span.current,
html.dark .baraa-wc-pagination .woocommerce-pagination ul li a:hover { background: #5ecfc2 !important; color: #00201d !important; }

/* ── Shipping calculator in cart ─────────────────────────── */
.woocommerce .shipping-calculator-button {
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.375rem !important;
    color: #16A085 !important;
    font-family: inherit !important;
    font-size: 0.875rem !important;
    font-weight: 600 !important;
    text-decoration: none !important;
    margin-bottom: 0.75rem !important;
}
.woocommerce .shipping-calculator-button::before {
    content: 'edit_location';
    font-family: 'Material Symbols Outlined';
    font-size: 1rem;
    line-height: 1;
}
.woocommerce .shipping-calculator-button:hover { text-decoration: underline !important; }
.woocommerce .shipping-calculator-form { margin-top: 0.75rem !important; }
.woocommerce .shipping-calculator-form p { margin-bottom: 0.625rem !important; }
.woocommerce .shipping-calculator-form .form-row { margin-bottom: 0.625rem !important; }
.woocommerce .shipping-calculator-form label {
    display: block !important;
    color: #131b2e !important;
    font-family: inherit !important;
    font-size: 0.8125rem !important;
    font-weight: 600 !important;
    margin-bottom: 0.25rem !important;
}
.woocommerce .shipping-calculator-form input[type="text"],
.woocommerce .shipping-calculator-form input[type="number"],
.woocommerce .shipping-calculator-form select {
    background: #f4f3fa !important;
    border: 1px solid #bec9c6 !important;
    border-radius: 0.5rem !important;
    color: #131b2e !important;
    font-family: inherit !important;
    font-size: 0.875rem !important;
    min-height: 40px !important;
    padding: 0.5rem 0.75rem !important;
    width: 100% !important;
    transition: border-color 0.2s ease !important;
}
.woocommerce .shipping-calculator-form input:focus,
.woocommerce .shipping-calculator-form select:focus {
    border-color: #16A085 !important;
    outline: none !important;
}
.woocommerce .shipping-calculator-form button[type="submit"],
.woocommerce .shipping-calculator-form input[type="submit"] {
    background: #16A085 !important;
    border: 0 !important;
    border-radius: 0.5rem !important;
    color: #fff !important;
    cursor: pointer !important;
    font-family: inherit !important;
    font-size: 0.875rem !important;
    font-weight: 700 !important;
    padding: 0.625rem 1.25rem !important;
    transition: background-color 0.2s ease !important;
    width: 100% !important;
}
.woocommerce .shipping-calculator-form button[type="submit"]:hover,
.woocommerce .shipping-calculator-form input[type="submit"]:hover { background: #0E6655 !important; }
html.dark .woocommerce .shipping-calculator-button { color: #5ecfc2 !important; }
html.dark .woocommerce .shipping-calculator-form label { color: #dff0ed !important; }
html.dark .woocommerce .shipping-calculator-form input[type="text"],
html.dark .woocommerce .shipping-calculator-form input[type="number"],
html.dark .woocommerce .shipping-calculator-form select { background: #1a2e2c !important; border-color: rgba(80,130,125,.5) !important; color: #dff0ed !important; }
html.dark .woocommerce .shipping-calculator-form button[type="submit"] { background: #5ecfc2 !important; color: #002926 !important; }
.woocommerce-cart table.cart,
.woocommerce-cart table.shop_table {
    border: 0 !important;
    border-collapse: collapse !important;
    display: none !important; /* We render our own cart items — hide WC table */
}

/* Cart totals block — rendered inside .cart-collaterals */
.woocommerce .cart_totals {
    float: none !important;
    width: 100% !important;
    background: transparent !important;
}
.woocommerce .cart_totals h2 {
    display: none !important; /* We have our own heading */
}
.woocommerce .cart_totals .shop_table {
    display: table !important; /* Show the totals table */
    border: 0 !important;
    border-collapse: collapse !important;
    width: 100% !important;
    margin: 0 !important;
}
.woocommerce .cart_totals .shop_table th,
.woocommerce .cart_totals .shop_table td {
    background: transparent !important;
    border: 0 !important;
    border-bottom: 1px solid rgba(190,201,198,.3) !important;
    padding: 0.75rem 0 !important;
    font-family: inherit !important;
    font-size: 0.875rem !important;
    color: #3d4947 !important;
    text-align: start !important;
}
.woocommerce .cart_totals .shop_table th { font-weight: 600 !important; }
.woocommerce .cart_totals .shop_table td { text-align: end !important; color: #131b2e !important; }
.woocommerce .cart_totals .shop_table .order-total th,
.woocommerce .cart_totals .shop_table .order-total td {
    border: 0 !important;
    background: #f4f3fa !important;
    color: #16A085 !important;
    font-size: 1.1rem !important;
    font-weight: 700 !important;
    padding: 0.875rem 0.75rem !important;
}
.woocommerce .cart_totals .shop_table .order-total th { border-radius: 0 0.5rem 0.5rem 0 !important; }
.woocommerce .cart_totals .shop_table .order-total td { border-radius: 0.5rem 0 0 0.5rem !important; }
.woocommerce .cart_totals .shop_table .cart-discount td,
.woocommerce .cart_totals .shop_table .cart-discount th { color: #16A085 !important; }
.woocommerce .cart_totals .shop_table .cart-discount .woocommerce-remove-coupon {
    color: #ba1a1a !important;
    font-size: 0.75rem !important;
}

/* Proceed to checkout button */
.woocommerce .wc-proceed-to-checkout { padding: 0 !important; }
.woocommerce .wc-proceed-to-checkout .checkout-button,
.woocommerce a.checkout-button {
    display: flex !important;
    width: 100% !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 0.5rem !important;
    padding: 0.875rem 1.25rem !important;
    border-radius: 0.75rem !important;
    background: #16A085 !important;
    color: #fff !important;
    font-family: inherit !important;
    font-size: 1.0625rem !important;
    font-weight: 700 !important;
    border: 0 !important;
    cursor: pointer !important;
    transition: background-color 0.2s ease, transform 0.15s ease, box-shadow 0.2s ease !important;
    text-align: center !important;
    box-shadow: 0 4px 14px rgba(61,157,67,.25) !important;
    margin: 0 !important;
}
.woocommerce .wc-proceed-to-checkout .checkout-button:hover,
.woocommerce a.checkout-button:hover {
    background: #0E6655 !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 20px rgba(61,157,67,.35) !important;
    color: #fff !important;
}

/* Shipping calculator inside cart */
.woocommerce .shipping-calculator-button { color: #16A085 !important; font-size: 0.8125rem !important; }
.woocommerce .shipping-calculator-form { margin-top: 0.75rem !important; }
.woocommerce .shipping-calculator-form .form-row { margin-bottom: 0.625rem !important; }

/* Cart notices */
.woocommerce-cart .woocommerce-message,
.woocommerce-cart .woocommerce-info,
.woocommerce-cart .woocommerce-error {
    border-radius: 0.75rem !important;
    margin: 0 0 1rem !important;
    font-family: inherit !important;
}
.woocommerce-cart .woocommerce-message { background: #D4E6F1 !important; border-inline-start: 3px solid #16A085 !important; color: #131b2e !important; border-top: 0 !important; }
.woocommerce-cart .woocommerce-error   { background: #ffdad6 !important; border-inline-start: 3px solid #ba1a1a !important; color: #410002 !important; border-top: 0 !important; }
.woocommerce-cart .woocommerce-info    { background: #f4f3fa !important; border-inline-start: 3px solid #6d7a77 !important; color: #131b2e !important; border-top: 0 !important; }
.woocommerce-cart .woocommerce-notices-wrapper:empty { display: none !important; }

/* Qty input (WC default) — matches our stepper style */
.woocommerce-cart-form .quantity { margin: 0 !important; display: inline-flex !important; }
.woocommerce-cart-form input.qty {
    background: transparent !important;
    border: 0 !important;
    box-shadow: none !important;
    color: #131b2e !important;
    font: inherit !important;
    font-size: 0.9375rem !important;
    font-weight: 700 !important;
    height: 28px !important;
    min-height: 28px !important;
    padding: 0 !important;
    text-align: center !important;
    width: 32px !important;
}

/* Dark mode cart */
html.dark .woocommerce .cart_totals .shop_table th { color: #8db5b0 !important; }
html.dark .woocommerce .cart_totals .shop_table td { color: #dff0ed !important; }
html.dark .woocommerce .cart_totals .shop_table th,
html.dark .woocommerce .cart_totals .shop_table td { border-bottom-color: rgba(80,130,125,.3) !important; }
html.dark .woocommerce .cart_totals .shop_table .order-total th,
html.dark .woocommerce .cart_totals .shop_table .order-total td { background: #1a2e2c !important; color: #5ecfc2 !important; }
html.dark .woocommerce .wc-proceed-to-checkout .checkout-button,
html.dark .woocommerce a.checkout-button { background: #5ecfc2 !important; color: #002926 !important; }
html.dark .woocommerce .wc-proceed-to-checkout .checkout-button:hover,
html.dark .woocommerce a.checkout-button:hover { background: #89f5e7 !important; }
html.dark .woocommerce-cart .woocommerce-message { background: #0d2420 !important; color: #89f5e7 !important; }
html.dark .woocommerce-cart .woocommerce-error   { background: #2d1216 !important; color: #ffb3ab !important; }
html.dark .woocommerce-cart input.qty            { color: #dff0ed !important; }

/* ── Form elements (WC generated) ───────────────────────── */
.woocommerce input[type="text"],
.woocommerce input[type="email"],
.woocommerce input[type="number"],
.woocommerce input[type="password"],
.woocommerce input[type="tel"],
.woocommerce textarea,
.woocommerce select {
    background: #f4f3fa !important;
    border: 1px solid #bec9c6 !important;
    border-radius: 0.5rem !important;
    color: #131b2e !important;
    font-family: inherit !important;
    font-size: 0.875rem !important;
    padding: 0.625rem 0.75rem !important;
}
.woocommerce input[type="text"]:focus,
.woocommerce input[type="email"]:focus,
.woocommerce input[type="number"]:focus,
.woocommerce textarea:focus,
.woocommerce select:focus {
    border-color: #16A085 !important;
    outline: none !important;
    box-shadow: 0 0 0 3px rgba(61, 157, 67, 0.15) !important;
}

/* ── Buttons ─────────────────────────────────────────────── */
.woocommerce button.button,
.woocommerce a.button,
.woocommerce input.button,
.woocommerce .checkout-button {
    background: #16A085 !important;
    border: 0 !important;
    border-radius: 0.5rem !important;
    color: #fff !important;
    cursor: pointer !important;
    font-family: inherit !important;
    font-size: 0.875rem !important;
    font-weight: 700 !important;
    padding: 0.625rem 1.25rem !important;
    transition: background-color 0.2s ease !important;
}
.woocommerce button.button:hover,
.woocommerce a.button:hover,
.woocommerce .checkout-button:hover { background: #0E6655 !important; color: #fff !important; }
.woocommerce button.button.alt { background: #16A085 !important; }
.woocommerce button.button:disabled,
.woocommerce button.button[disabled] { opacity: 0.5 !important; cursor: not-allowed !important; }
</style>
    <?php
}
add_action('wp_head', 'baraa_pharmacy_wc_style_overrides', 99);

/**
 * Checkout page overrides — the classic checkout markup is generated by
 * WooCommerce (not our templates), so we theme it here to match the MD3 /
 * Tailwind design tokens used across the storefront. Loaded on checkout only.
 */
function baraa_pharmacy_checkout_style_overrides()
{
    if (!function_exists('is_checkout') || !is_checkout() || (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-received'))) {
        return;
    }
    ?>
<style id="baraa-checkout-overrides">
/* ═══════════════════════════════════════════════════════════
   CHECKOUT — Baraa Pharmacy
   ═══════════════════════════════════════════════════════════ */

/* Hide the raw <h1> page title printed by index.php */
body.woocommerce-checkout main article > h1:first-child { display: none !important; }

/* ── Server messages ─────────────────────────────────────── */
.woocommerce-checkout .woocommerce-notices-wrapper { width: 100%; padding: 0 2rem 1.25rem; }
.woocommerce-checkout .woocommerce-error {
    display: flex !important; flex-direction: column !important; gap: .25rem !important;
    background: #ffdad6 !important; color: #93000a !important;
    border: 0 !important; border-radius: .75rem !important;
    padding: 1rem 1.25rem !important; margin: 0 !important;
    font-size: .875rem !important; list-style: none !important;
}

/* ── Coupon / login toggles ──────────────────────────────── */
.woocommerce-checkout .woocommerce-form-coupon-toggle .woocommerce-info,
.woocommerce-checkout .woocommerce-form-login-toggle .woocommerce-info {
    background: #f4f3fa !important; color: #3d4947 !important;
    border: 0 !important; border-radius: .75rem !important;
    padding: .875rem 1.125rem !important; margin: 0 0 1rem !important;
    font-size: .8125rem !important; font-family: inherit !important;
}
.woocommerce-checkout .woocommerce-form-coupon-toggle a.showcoupon,
.woocommerce-checkout .woocommerce-form-login-toggle a.showlogin { color: #16A085 !important; font-weight: 700 !important; }
.woocommerce-checkout form.checkout_coupon,
.woocommerce-checkout form.woocommerce-form-login {
    background: #ffffff !important; border: 1px solid rgba(190,201,198,.5) !important;
    border-radius: .75rem !important; padding: 1.25rem !important; margin: 0 0 1.25rem !important;
}
.woocommerce-checkout form.checkout_coupon p { margin: 0 0 .625rem !important; }
.woocommerce-checkout form.checkout_coupon .form-row { display: flex !important; gap: .5rem !important; margin: 0 !important; padding: 0 !important; }
.woocommerce-checkout form.checkout_coupon input[type="text"] { flex: 1 !important; }

/* ── Customer details card ───────────────────────────────── */
.woocommerce-checkout #customer_details {
    display: flex !important; flex-direction: column !important; gap: 1.5rem !important;
    background: #ffffff !important; border-radius: .75rem !important;
    box-shadow: 0 1px 4px rgba(16,24,40,.06) !important;
    padding: 1.5rem !important; width: 100% !important;
}
.woocommerce-checkout #customer_details h3 {
    font-family: inherit !important; font-size: 1rem !important; font-weight: 700 !important;
    color: #131b2e !important; line-height: 1.4 !important;
    margin: 0 0 1.125rem !important; padding: 0 0 .75rem !important;
    border-bottom: 1px solid rgba(190,201,198,.5) !important;
}

/* ── Fields grid ─────────────────────────────────────────── */
.woocommerce-checkout .woocommerce-billing-fields__field-wrapper,
.woocommerce-checkout .woocommerce-shipping-fields__field-wrapper,
.woocommerce-checkout .woocommerce-additional-fields__field-wrapper {
    display: grid !important;
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    gap: .875rem 1rem !important;
}
.woocommerce-checkout #customer_details .form-row {
    grid-column: 1 / -1 !important; display: block !important;
    float: none !important; width: auto !important; margin: 0 !important; padding: 0 !important;
}
.woocommerce-checkout #customer_details .form-row-first { grid-column: 1 / 2 !important; }
.woocommerce-checkout #customer_details .form-row-last  { grid-column: 2 / 3 !important; }
.woocommerce-checkout #customer_details .form-row label {
    display: block !important; font-family: inherit !important;
    font-size: .8125rem !important; font-weight: 600 !important;
    color: #3d4947 !important; margin: 0 0 .375rem !important;
}
.woocommerce-checkout #customer_details .form-row .required { color: #ba1a1a !important; text-decoration: none !important; }
.woocommerce-checkout #customer_details .woocommerce-input-wrapper { display: block !important; width: 100% !important; }
.woocommerce-checkout #customer_details input[type="text"],
.woocommerce-checkout #customer_details input[type="email"],
.woocommerce-checkout #customer_details input[type="tel"],
.woocommerce-checkout #customer_details input[type="number"],
.woocommerce-checkout #customer_details input[type="password"],
.woocommerce-checkout #customer_details textarea,
.woocommerce-checkout #customer_details select {
    width: 100% !important; min-height: 46px !important;
    background: #f4f3fa !important; border: 1px solid #bec9c6 !important;
    border-radius: .5rem !important; color: #131b2e !important;
    font-family: inherit !important; font-size: .875rem !important;
    line-height: 1.4 !important; padding: .625rem .875rem !important;
    transition: border-color .2s ease, box-shadow .2s ease !important;
}
.woocommerce-checkout #customer_details textarea { min-height: 96px !important; }
.woocommerce-checkout #customer_details input:focus,
.woocommerce-checkout #customer_details textarea:focus,
.woocommerce-checkout #customer_details select:focus {
    border-color: #16A085 !important; outline: none !important;
    box-shadow: 0 0 0 3px rgba(61,157,67,.15) !important;
}
.woocommerce-checkout .select2-container--default .select2-selection--single {
    height: 46px !important; background: #f4f3fa !important;
    border: 1px solid #bec9c6 !important; border-radius: .5rem !important;
}
.woocommerce-checkout .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 44px !important; color: #131b2e !important; }
.woocommerce-checkout .select2-container--default .select2-selection--single .select2-selection__arrow { height: 44px !important; }

/* ── Ship to a different address ─────────────────────────── */
.woocommerce-checkout #ship-to-different-address { margin: 0 0 1.125rem !important; }
.woocommerce-checkout #ship-to-different-address label {
    display: inline-flex !important; align-items: center !important; gap: .5rem !important;
    font-size: .875rem !important; font-weight: 600 !important; color: #131b2e !important; cursor: pointer !important;
}
.woocommerce-checkout #ship-to-different-address input[type="checkbox"] {
    width: 16px !important; height: 16px !important; margin: 0 !important; accent-color: #16A085 !important;
}

/* ── Order review table ──────────────────────────────────── */
.woocommerce-checkout .baraa-review-table {
    width: 100% !important; border: 0 !important; border-collapse: collapse !important;
    background: transparent !important; margin: 0 0 1rem !important;
}
.woocommerce-checkout .baraa-review-table thead th {
    font-family: inherit !important; font-size: .6875rem !important; font-weight: 600 !important;
    letter-spacing: .04em !important; text-transform: uppercase !important;
    color: #6d7a77 !important; text-align: start !important;
    padding: 0 0 .5rem !important; border-bottom: 1px solid rgba(190,201,198,.5) !important;
}
.woocommerce-checkout .baraa-review-table thead th.product-total { text-align: end !important; }
.woocommerce-checkout .baraa-review-table tbody td {
    padding: .75rem 0 !important; vertical-align: top !important;
    border-bottom: 1px solid rgba(190,201,198,.35) !important;
    font-size: .875rem !important; color: #131b2e !important;
}
.woocommerce-checkout .baraa-review-table td.product-name { display: flex !important; align-items: flex-start !important; gap: .625rem !important; }
.woocommerce-checkout .baraa-review-thumb-wrap { flex-shrink: 0 !important; display: block !important; }
.woocommerce-checkout .baraa-review-thumb {
    width: 44px !important; height: 44px !important; border-radius: .5rem !important;
    background: #f4f3fa !important; object-fit: contain !important; padding: 3px !important; display: block !important;
}
.woocommerce-checkout .baraa-review-info { display: flex !important; flex-direction: column !important; gap: 3px !important; min-width: 0 !important; } 
.woocommerce-checkout .baraa-review-name { font-weight: 600 !important; font-size: .84375rem !important; line-height: 1.45 !important; color: #131b2e !important; }
.woocommerce-checkout .baraa-review-meta { display: flex !important; align-items: center !important; gap: .375rem !important; flex-wrap: wrap !important; }
.woocommerce-checkout .baraa-review-table .product-quantity {
    font-size: .6875rem !important; font-weight: 700 !important; color: #16A085 !important;
    background: #D4E6F1 !important; border-radius: 9999px !important; padding: 1px 7px !important;
}
.woocommerce-checkout .baraa-review-rx {
    display: inline-flex !important; align-items: center !important; gap: 3px !important;
    font-size: .6875rem !important; font-weight: 700 !important;
    color: #00201d !important; background: #89f5e7 !important;
    border-radius: 9999px !important; padding: 1px 8px !important;
}
.woocommerce-checkout .baraa-review-table td.product-total {
    text-align: end !important; white-space: nowrap !important;
    font-weight: 700 !important; font-size: .875rem !important; color: #16A085 !important;
}
.woocommerce-checkout .baraa-review-table tfoot th,
.woocommerce-checkout .baraa-review-table tfoot td {
    padding: .6rem 0 !important; font-size: .84375rem !important; font-weight: 400 !important;
    background: transparent !important; border: 0 !important; color: #3d4947 !important;
}
.woocommerce-checkout .baraa-review-table tfoot th { text-align: start !important; font-weight: 600 !important; }
.woocommerce-checkout .baraa-review-table tfoot td { text-align: end !important; color: #131b2e !important; }
.woocommerce-checkout .baraa-review-table tfoot .woocommerce-shipping-totals td { text-align: start !important; }
.woocommerce-checkout .baraa-review-table ul.woocommerce-shipping-methods { list-style: none !important; margin: 0 !important; padding: 0 !important; }
.woocommerce-checkout .baraa-review-table ul.woocommerce-shipping-methods li { display: flex !important; align-items: center !important; gap: .375rem !important; font-size: .8125rem !important; }
.woocommerce-checkout .baraa-review-table ul.woocommerce-shipping-methods label { font-weight: 600 !important; color: #131b2e !important; }
.woocommerce-checkout .baraa-review-table tfoot .order-total th,
.woocommerce-checkout .baraa-review-table tfoot .order-total td {
    background: #f4f3fa !important; color: #16A085 !important;
    font-size: 1.0625rem !important; font-weight: 700 !important; padding: .75rem !important;
}
.woocommerce-checkout .baraa-review-table tfoot .order-total th { border-radius: .5rem 0 0 .5rem !important; }
.woocommerce-checkout .baraa-review-table tfoot .order-total td { border-radius: 0 .5rem .5rem 0 !important; }

/* ── Payment methods ─────────────────────────────────────── */
.woocommerce-checkout #payment { background: transparent !important; border-radius: 0 !important; padding: 0 !important; margin: 0 !important; }
.woocommerce-checkout #payment ul.wc_payment_methods {
    list-style: none !important; display: grid !important; gap: .625rem !important;
    margin: 0 0 1rem !important; padding: 0 !important;
}
.woocommerce-checkout #payment ul.wc_payment_methods li.wc_payment_method {
    background: #f4f3fa !important; border: 1px solid transparent !important;
    border-radius: .75rem !important; padding: .875rem 1rem !important; margin: 0 !important;
    transition: background-color .2s ease, border-color .2s ease !important;
}
.woocommerce-checkout #payment ul.wc_payment_methods li.wc_payment_method:hover { background: #eaedff !important; }
.woocommerce-checkout #payment ul.wc_payment_methods li.wc_payment_method:has(input.input-radio:checked) {
    background: #D4E6F1 !important; border-color: #16A085 !important;
}
.woocommerce-checkout #payment ul.wc_payment_methods li label {
    display: inline-flex !important; align-items: center !important; gap: .5rem !important;
    font-family: inherit !important; font-size: .875rem !important; font-weight: 600 !important;
    color: #131b2e !important; margin: 0 !important; cursor: pointer !important;
}
.woocommerce-checkout #payment ul.wc_payment_methods li input.input-radio {
    width: 17px !important; height: 17px !important; margin: 0 !important;
    flex-shrink: 0 !important; accent-color: #16A085 !important;
}
.woocommerce-checkout #payment ul.wc_payment_methods li label img.kashier-icon {
    height: 20px !important; width: auto !important; display: inline-block !important;
    vertical-align: middle !important; margin-inline-start: 2px !important;
}
.woocommerce-checkout #payment .payment_box {
    background: rgba(61,157,67,.06) !important; border-radius: .5rem !important;
    margin: .75rem 0 0 !important; padding: .75rem .875rem !important;
    font-size: .8125rem !important; line-height: 1.6 !important; color: #3d4947 !important;
}
.woocommerce-checkout #payment .payment_box::before { display: none !important; }
.woocommerce-checkout #payment .payment_box p { margin: 0 0 .375rem !important; }
.woocommerce-checkout #payment .payment_box p:last-child { margin-bottom: 0 !important; }
.woocommerce-checkout #secured-by-kashier-container img { max-width: 130px !important; height: auto !important; margin-top: .5rem !important; }

/* ── Place order ─────────────────────────────────────────── */
.woocommerce-checkout #payment .form-row.place-order {
    display: block !important; margin: 0 !important; padding: 0 !important;
    background: transparent !important; border: 0 !important;
}
.woocommerce-checkout #place_order {
    width: 100% !important; min-height: 52px !important;
    background: #16A085 !important; color: #ffffff !important;
    border: 0 !important; border-radius: .75rem !important;
    font-family: inherit !important; font-size: 1.0625rem !important; font-weight: 700 !important;
    padding: .875rem 1.25rem !important; cursor: pointer !important;
    box-shadow: 0 4px 14px rgba(61,157,67,.25) !important;
    transition: background-color .2s ease, transform .15s ease, box-shadow .2s ease !important;
}
.woocommerce-checkout #place_order:hover {
    background: #0E6655 !important; transform: translateY(-2px) !important;
    box-shadow: 0 6px 20px rgba(61,157,67,.35) !important;
}
.woocommerce-checkout #place_order:disabled { opacity: .6 !important; cursor: not-allowed !important; transform: none !important; }

/* ── Terms & privacy ─────────────────────────────────────── */
.woocommerce-checkout .woocommerce-terms-and-conditions-wrapper { margin-bottom: .875rem !important; }
.woocommerce-checkout .woocommerce-privacy-policy-text p {
    font-size: .75rem !important; line-height: 1.7 !important; color: #6d7a77 !important; margin: 0 !important;
}
.woocommerce-checkout .woocommerce-privacy-policy-text a { color: #16A085 !important; }
.woocommerce-checkout .woocommerce-terms-and-conditions-wrapper label,
.woocommerce-checkout .woocommerce-terms-and-conditions-wrapper .form-row label {
    font-size: .8125rem !important; color: #3d4947 !important; font-weight: 500 !important;
}

/* ── RouteMile delivery cards ────────────────────────────── */
/* RouteMile paints its checkout cards with an orange/green "food delivery"
   palette (its own SCSS compiles the colours to literals). Re-map them onto
   the Baraa Pharmacy teal palette, exactly like the My Account page. */
.woocommerce-checkout .routew-ui {
    --rm-brand: #16A085;
    --rm-brand-strong: #0E6655;
    --rm-brand-deep: #003732;
    --rm-ink: #131b2e;
    --rm-body: #3d4947;
    --rm-muted: #6d7a77;
    --rm-faint: #bec9c6;
    --rm-line: rgba(190,201,198,.5);
    --rm-wash: #f4f3fa;
    --rm-surface: #ffffff;
    --rm-delivered: #0E6655;
    --rm-delivered-tint: #cdeee9;
    --rm-cancelled: #ba1a1a;
    --rm-cancelled-tint: #ffdad6;
    --rm-transit: #006398;
    --rm-transit-tint: #dceafc;
}
.woocommerce-checkout .routew-ui.routew-checkout-card {
    background: #ffffff !important;
    border: 1px solid rgba(190,201,198,.5) !important;
    border-radius: .75rem !important;
    box-shadow: 0 1px 4px rgba(16,24,40,.06) !important;
}
.woocommerce-checkout .routew-ui .routew-checkout-step__num {
    background: linear-gradient(160deg, #16A085 0%, #0E6655 100%) !important;
    box-shadow: 0 4px 12px rgba(61,157,67,.3) !important;
}
.woocommerce-checkout .routew-ui .routew-checkout-step__title { color: #131b2e !important; }
.woocommerce-checkout .routew-ui .routew-checkout-step__sub { color: #6d7a77 !important; }

/* Search bar */
.woocommerce-checkout .routew-ui .routew-location-search-wrapper__icon { fill: #bec9c6 !important; }
.woocommerce-checkout .routew-ui .routew-location-search-wrapper #routew-location-search-input {
    min-height: 46px !important;
    background: #f4f3fa !important;
    border: 1px solid #bec9c6 !important;
    border-radius: .5rem !important;
    color: #131b2e !important;
    font-family: inherit !important;
    font-size: .875rem !important;
}
.woocommerce-checkout .routew-ui .routew-location-search-wrapper #routew-location-search-input::placeholder { color: #bec9c6 !important; }
.woocommerce-checkout .routew-ui .routew-location-search-wrapper #routew-location-search-input:focus {
    border-color: #16A085 !important;
    box-shadow: 0 0 0 3px rgba(61,157,67,.15) !important;
}
.woocommerce-checkout .routew-ui .routew-locate-btn {
    background: #16A085 !important; color: #ffffff !important; font-family: inherit !important;
}
.woocommerce-checkout .routew-ui .routew-locate-btn:hover,
.woocommerce-checkout .routew-ui .routew-locate-btn:focus-visible { background: #0E6655 !important; color: #ffffff !important; }

/* Map */
.woocommerce-checkout .routew-ui .routew-map-shell {
    border: 1px solid rgba(190,201,198,.5) !important;
    border-radius: .5rem !important;
    background: #f4f3fa !important;
}
.woocommerce-checkout .routew-ui .routew-restaurant-badge__dot { background: #16A085 !important; }
/* The delivery-radius ring is drawn green by the map provider. */
.woocommerce-checkout #routew-map .leaflet-overlay-pane path { stroke: #16A085 !important; fill: #16A085 !important; }

/* Confirmed delivery point */
.woocommerce-checkout .routew-ui .routew-location-confirm { background: #cdeee9 !important; border-color: #b6e3dd !important; }
.woocommerce-checkout .routew-ui .routew-location-confirm__icon { background: #0E6655 !important; }
.woocommerce-checkout .routew-ui .routew-location-confirm__label { color: #0E6655 !important; }
.woocommerce-checkout .routew-ui .routew-location-confirm__address { color: #131b2e !important; }

/* Fee / status toast. The map JS mounts it OUTSIDE `.routew-ui` (right after
   the picker container), so the plugin's `.routew-ui #routew-map-notification`
   rules never apply — it needs its own scope. */
.woocommerce-checkout #routew-map-notification {
    margin: 0 0 1.25rem !important;
    border: 1px solid rgba(190,201,198,.5) !important;
    border-radius: .5rem !important;
    background: #f4f3fa !important; color: #3d4947 !important;
    font-family: inherit !important;
}
.woocommerce-checkout #routew-map-notification.success { background: #cdeee9 !important; border-color: #b6e3dd !important; color: #0E6655 !important; }
.woocommerce-checkout #routew-map-notification.success::before { background: #0E6655 !important; }
.woocommerce-checkout #routew-map-notification.error { background: #ffdad6 !important; border-color: #ffb4ab !important; color: #93000a !important; }
.woocommerce-checkout #routew-map-notification.error::before { background: #ba1a1a !important; }
.woocommerce-checkout #routew-map-notification.info { background: #dceafc !important; border-color: #c4d6f8 !important; color: #006398 !important; }
.woocommerce-checkout #routew-map-notification.info::before { background: #006398 !important; }

/* ── Mobile ──────────────────────────────────────────────── */
@media (max-width: 640px) {
    .woocommerce-checkout .woocommerce-billing-fields__field-wrapper,
    .woocommerce-checkout .woocommerce-shipping-fields__field-wrapper,
    .woocommerce-checkout .woocommerce-additional-fields__field-wrapper { grid-template-columns: 1fr !important; }
    .woocommerce-checkout #customer_details .form-row-first,
    .woocommerce-checkout #customer_details .form-row-last { grid-column: 1 / -1 !important; }
    .woocommerce-checkout .woocommerce-notices-wrapper { padding: 0 1rem 1rem; }
}

/* ── Dark mode ───────────────────────────────────────────── */
html.dark .woocommerce-checkout .woocommerce { color: #dff0ed !important; }
html.dark .woocommerce-checkout #customer_details { background: #132220 !important; box-shadow: 0 1px 4px rgba(0,30,28,.6) !important; }
html.dark .woocommerce-checkout #customer_details h3 { color: #dff0ed !important; border-bottom-color: rgba(80,130,125,.35) !important; }
html.dark .woocommerce-checkout #customer_details .form-row label { color: #8db5b0 !important; }
html.dark .woocommerce-checkout #customer_details input[type="text"],
html.dark .woocommerce-checkout #customer_details input[type="email"],
html.dark .woocommerce-checkout #customer_details input[type="tel"],
html.dark .woocommerce-checkout #customer_details input[type="number"],
html.dark .woocommerce-checkout #customer_details input[type="password"],
html.dark .woocommerce-checkout #customer_details textarea,
html.dark .woocommerce-checkout #customer_details select {
    background: #1a2e2c !important; border-color: rgba(80,130,125,.5) !important; color: #dff0ed !important;
}
html.dark .woocommerce-checkout .select2-container--default .select2-selection--single { background: #1a2e2c !important; border-color: rgba(80,130,125,.5) !important; }
html.dark .woocommerce-checkout .select2-container--default .select2-selection--single .select2-selection__rendered { color: #dff0ed !important; }
html.dark .woocommerce-checkout #ship-to-different-address label { color: #dff0ed !important; }
html.dark .woocommerce-checkout #ship-to-different-address input[type="checkbox"] { accent-color: #5ecfc2 !important; }
html.dark .woocommerce-checkout .baraa-review-table thead th { color: #6a9e99 !important; border-bottom-color: rgba(80,130,125,.35) !important; }
html.dark .woocommerce-checkout .baraa-review-table tbody td { border-bottom-color: rgba(80,130,125,.25) !important; color: #dff0ed !important; }
html.dark .woocommerce-checkout .baraa-review-thumb { background: #1a2e2c !important; }
html.dark .woocommerce-checkout .baraa-review-name { color: #dff0ed !important; }
html.dark .woocommerce-checkout .baraa-review-table .product-quantity { background: #0d2420 !important; color: #5ecfc2 !important; }
html.dark .woocommerce-checkout .baraa-review-table td.product-total { color: #5ecfc2 !important; }
html.dark .woocommerce-checkout .baraa-review-table tfoot th { color: #8db5b0 !important; }
html.dark .woocommerce-checkout .baraa-review-table tfoot td { color: #dff0ed !important; }
html.dark .woocommerce-checkout .baraa-review-table ul.woocommerce-shipping-methods label { color: #dff0ed !important; }
html.dark .woocommerce-checkout .baraa-review-table tfoot .order-total th,
html.dark .woocommerce-checkout .baraa-review-table tfoot .order-total td { background: #1a2e2c !important; color: #5ecfc2 !important; }
html.dark .woocommerce-checkout #payment ul.wc_payment_methods li.wc_payment_method { background: #1a2e2c !important; }
html.dark .woocommerce-checkout #payment ul.wc_payment_methods li.wc_payment_method:hover { background: #1f3735 !important; }
html.dark .woocommerce-checkout #payment ul.wc_payment_methods li.wc_payment_method:has(input.input-radio:checked) { background: #0d2420 !important; border-color: #5ecfc2 !important; }
html.dark .woocommerce-checkout #payment ul.wc_payment_methods li label { color: #dff0ed !important; }
html.dark .woocommerce-checkout #payment .payment_box { background: rgba(94,207,194,.08) !important; color: #8db5b0 !important; }
html.dark .woocommerce-checkout #place_order { background: #5ecfc2 !important; color: #002926 !important; box-shadow: 0 4px 14px rgba(0,30,28,.5) !important; }
html.dark .woocommerce-checkout #place_order:hover { background: #89f5e7 !important; }
html.dark .woocommerce-checkout .woocommerce-privacy-policy-text p { color: #6a9e99 !important; }
html.dark .woocommerce-checkout .woocommerce-privacy-policy-text a { color: #5ecfc2 !important; }
html.dark .woocommerce-checkout .woocommerce-terms-and-conditions-wrapper label { color: #8db5b0 !important; }
html.dark .woocommerce-checkout .woocommerce-form-coupon-toggle .woocommerce-info,
html.dark .woocommerce-checkout .woocommerce-form-login-toggle .woocommerce-info { background: #1a2e2c !important; color: #8db5b0 !important; }
html.dark .woocommerce-checkout form.checkout_coupon,
html.dark .woocommerce-checkout form.woocommerce-form-login { background: #132220 !important; border-color: rgba(80,130,125,.35) !important; }
html.dark .woocommerce-checkout .woocommerce-error { background: #2d1216 !important; color: #ffb3ab !important; }

/* Dark mode — RouteMile delivery cards */
html.dark .woocommerce-checkout .routew-ui {
    --rm-brand: #5ecfc2;
    --rm-brand-strong: #89f5e7;
    --rm-brand-deep: #2f8c81;
    --rm-ink: #dff0ed;
    --rm-body: #b8d8d3;
    --rm-muted: #8db5b0;
    --rm-faint: #6a9e99;
    --rm-line: rgba(80,130,125,.35);
    --rm-wash: #1a2e2c;
    --rm-surface: #1a2e2c;
    --rm-delivered: #5ecfc2;
    --rm-delivered-tint: #0d2420;
    --rm-cancelled: #ffb3ab;
    --rm-cancelled-tint: #2d1216;
    --rm-transit: #93c5fd;
    --rm-transit-tint: #12243a;
}
html.dark .woocommerce-checkout .routew-ui.routew-checkout-card {
    background: #132220 !important;
    border-color: rgba(80,130,125,.35) !important;
    box-shadow: 0 1px 4px rgba(0,30,28,.6) !important;
}
html.dark .woocommerce-checkout .routew-ui .routew-checkout-step__num {
    background: linear-gradient(160deg, #5ecfc2 0%, #2f8c81 100%) !important;
    color: #002926 !important; box-shadow: none !important;
}
html.dark .woocommerce-checkout .routew-ui .routew-checkout-step__title { color: #dff0ed !important; }
html.dark .woocommerce-checkout .routew-ui .routew-checkout-step__sub { color: #8db5b0 !important; }
html.dark .woocommerce-checkout .routew-ui .routew-location-search-wrapper__icon { fill: #6a9e99 !important; }
html.dark .woocommerce-checkout .routew-ui .routew-location-search-wrapper #routew-location-search-input {
    background: #1a2e2c !important; border-color: rgba(80,130,125,.5) !important; color: #dff0ed !important;
}
html.dark .woocommerce-checkout .routew-ui .routew-location-search-wrapper #routew-location-search-input::placeholder { color: #6a9e99 !important; }
html.dark .woocommerce-checkout .routew-ui .routew-locate-btn { background: #5ecfc2 !important; color: #002926 !important; }
html.dark .woocommerce-checkout .routew-ui .routew-locate-btn:hover,
html.dark .woocommerce-checkout .routew-ui .routew-locate-btn:focus-visible { background: #89f5e7 !important; color: #002926 !important; }
html.dark .woocommerce-checkout .routew-ui .routew-map-shell { background: #1a2e2c !important; border-color: rgba(80,130,125,.35) !important; }
html.dark .woocommerce-checkout .routew-ui .routew-restaurant-badge__dot { background: #5ecfc2 !important; }
html.dark .woocommerce-checkout #routew-map .leaflet-overlay-pane path { stroke: #5ecfc2 !important; fill: #5ecfc2 !important; }
html.dark .woocommerce-checkout .routew-ui .routew-location-confirm { background: #0d2420 !important; border-color: rgba(94,207,194,.35) !important; }
html.dark .woocommerce-checkout .routew-ui .routew-location-confirm__icon { background: #5ecfc2 !important; }
html.dark .woocommerce-checkout .routew-ui .routew-location-confirm__icon svg { fill: #002926 !important; }
html.dark .woocommerce-checkout .routew-ui .routew-location-confirm__label { color: #5ecfc2 !important; }
html.dark .woocommerce-checkout .routew-ui .routew-location-confirm__address { color: #dff0ed !important; }
html.dark .woocommerce-checkout #routew-map-notification { background: #1a2e2c !important; border-color: rgba(80,130,125,.35) !important; color: #b8d8d3 !important; }
html.dark .woocommerce-checkout #routew-map-notification.success { background: #0d2420 !important; border-color: rgba(94,207,194,.35) !important; color: #5ecfc2 !important; }
html.dark .woocommerce-checkout #routew-map-notification.success::before { background: #5ecfc2 !important; }
html.dark .woocommerce-checkout #routew-map-notification.error { background: #2d1216 !important; border-color: rgba(255,179,171,.35) !important; color: #ffb3ab !important; }
html.dark .woocommerce-checkout #routew-map-notification.error::before { background: #ffb3ab !important; }
html.dark .woocommerce-checkout #routew-map-notification.info { background: #12243a !important; border-color: rgba(147,197,253,.3) !important; color: #93c5fd !important; }
html.dark .woocommerce-checkout #routew-map-notification.info::before { background: #93c5fd !important; }
</style>
    <?php
}
add_action('wp_head', 'baraa_pharmacy_checkout_style_overrides', 100);

/**
 * Order-received (thank-you) page styling.
 *
 * The classic checkout overrides above are skipped on the order-received
 * endpoint, so the custom thank-you template (woocommerce/checkout/thankyou.php)
 * gets its own MD3-styled block, including a dark-mode palette.
 */
function baraa_pharmacy_thankyou_style_overrides()
{
    if (!function_exists('is_wc_endpoint_url') || !is_wc_endpoint_url('order-received')) {
        return;
    }
    ?>
<style id="baraa-thankyou-overrides">
/* Hide the theme's plain page title ("Order received") — we render our own hero */
body.woocommerce-order-received #main > article > h1 { display: none !important; }

/* ── Hero ────────────────────────────────────────────────── */
.baraa-thankyou-hero {
    background-image: linear-gradient(120deg, #16A085 0%, #117A65 55%, #006398 100%) !important;
    color: #ffffff !important;
    border: 0 !important;
}
.baraa-thankyou-hero h1,
.baraa-thankyou-hero p,
.baraa-thankyou-hero .font-label-sm { color: #ffffff !important; }

/* ── Cards ───────────────────────────────────────────────── */
.baraa-thankyou-card { background: #ffffff !important; border: 0 !important; }
html.dark .baraa-thankyou-card { background: #132220 !important; }
.baraa-thankyou-failed { border-inline-start: 4px solid #ba1a1a !important; }

/* ── Summary table ───────────────────────────────────────── */
.baraa-thankyou-table-wrap { overflow-x: auto; }
.baraa-thankyou-table { border-collapse: collapse !important; width: 100% !important; margin: 0 !important; }
.baraa-thankyou-table thead th {
    background: #f4f3fa !important; color: #6d7a77 !important;
    font-size: .6875rem !important; font-weight: 700 !important; letter-spacing: .04em !important;
    text-transform: uppercase !important; padding: .75rem 1rem !important;
    border: 0 !important; border-bottom: 1px solid #bec9c6 !important;
}
.baraa-thankyou-table tbody td {
    padding: .875rem 1rem !important; border: 0 !important;
    border-bottom: 1px solid rgba(190,201,198,.5) !important;
    color: #131b2e !important; vertical-align: middle !important;
}
.baraa-thankyou-table tfoot th,
.baraa-thankyou-table tfoot td {
    padding: .5rem 1rem !important; border: 0 !important;
    color: #3d4947 !important; font-weight: 500 !important;
}
.baraa-thankyou-table tfoot tr:first-child th,
.baraa-thankyou-table tfoot tr:first-child td { padding-top: 1rem !important; }
.baraa-thankyou-grandtotal th,
.baraa-thankyou-grandtotal td {
    background: #f4f3fa !important; color: #16A085 !important;
    font-weight: 800 !important; font-size: 1rem !important;
    padding-top: .875rem !important; padding-bottom: .875rem !important;
}
.baraa-thankyou-thumb { background: #f4f3fa !important; }
.baraa-thankyou-name { color: #131b2e !important; }
.baraa-thankyou-total,
.baraa-thankyou-discount { color: #16A085 !important; }
.baraa-thankyou-gateway p { margin: 0 !important; }

/* ── Buttons ─────────────────────────────────────────────── */
.baraa-thankyou-btn-primary {
    background: #16A085 !important; color: #ffffff !important;
    box-shadow: 0 4px 14px rgba(61,157,67,.25) !important;
    transition: background-color .2s ease, transform .15s ease !important;
}
.baraa-thankyou-btn-primary:hover { background: #0E6655 !important; color: #ffffff !important; transform: translateY(-2px) !important; }
.baraa-thankyou-btn-ghost {
    background: #eaedff !important; color: #16A085 !important;
    transition: background-color .2s ease !important;
}
.baraa-thankyou-btn-ghost:hover { background: #dae2fd !important; color: #0E6655 !important; }

/* ── Dark mode ───────────────────────────────────────────── */
html.dark .baraa-thankyou-table thead th { background: #1a2e2c !important; color: #6a9e99 !important; border-bottom-color: rgba(80,130,125,.45) !important; }
html.dark .baraa-thankyou-table tbody td { border-bottom-color: rgba(80,130,125,.3) !important; color: #dff0ed !important; }
html.dark .baraa-thankyou-table tfoot th { color: #8db5b0 !important; }
html.dark .baraa-thankyou-table tfoot td { color: #dff0ed !important; }
html.dark .baraa-thankyou-grandtotal th,
html.dark .baraa-thankyou-grandtotal td { background: #1a2e2c !important; color: #5ecfc2 !important; }
html.dark .baraa-thankyou-thumb { background: #1a2e2c !important; }
html.dark .baraa-thankyou-name { color: #dff0ed !important; }
html.dark .baraa-thankyou-total,
html.dark .baraa-thankyou-discount { color: #5ecfc2 !important; }
html.dark .baraa-thankyou-btn-primary { background: #5ecfc2 !important; color: #002926 !important; }
html.dark .baraa-thankyou-btn-primary:hover { background: #89f5e7 !important; }
html.dark .baraa-thankyou-btn-ghost { background: #1a2e2c !important; color: #5ecfc2 !important; }
html.dark .baraa-thankyou-btn-ghost:hover { background: #1f3735 !important; color: #89f5e7 !important; }
</style>
    <?php
}
add_action('wp_head', 'baraa_pharmacy_thankyou_style_overrides', 101);

/**
 * My Account (حسابي) styling.
 *
 * RouteMile owns the account layout (`routew-my-account-styled`) and paints it
 * with its own orange "food delivery" palette plus Bootstrap defaults — and it
 * ships no dark mode at all. Everything below re-maps those colours onto the
 * Baraa Pharmacy MD3 teal palette without touching the plugin's files.
 */
function baraa_pharmacy_account_style_overrides()
{
    if (!function_exists('is_account_page') || !is_account_page()) {
        return;
    }
    ?>
<style id="baraa-account-overrides">
/* ═══════════════════════════════════════════════════════════
   MY ACCOUNT — Baraa Pharmacy palette
   ═══════════════════════════════════════════════════════════ */

/* ── Re-map RouteMile's design tokens onto ours ───────────── */
.woocommerce-account.routew-my-account-styled,
.woocommerce-account.routew-my-account-styled .routew-ui,
.routew-ui.routew-account {
    --rm-brand: #16A085;
    --rm-brand-strong: #0E6655;
    --rm-brand-deep: #0B4F3E;
    --rm-brand-soft: #D4E6F1;
    --rm-brand-line: #9DD3CF;
    --rm-ink: #131b2e;
    --rm-body: #3d4947;
    --rm-muted: #6d7a77;
    --rm-faint: #bec9c6;
    --rm-line: #dde8e6;
    --rm-wash: #f4f3fa;
    --rm-surface: #ffffff;
    --rm-canvas: #faf8ff;
    --rm-canvas-2: #f4f3fa;
    --rm-tint-orange: #D4E6F1;
    --rm-tint-blue: #D4E6F1;
    --rm-tint-green: #D4E6F1;
    --rm-tint-amber: #D4E6F1;
    --rm-tint-pink: #D4E6F1;
    --rm-brand-canvas-wash: #EBF5FB;
    --rm-preparing: #16A085;
    --rm-preparing-tint: #D4E6F1;
    --rm-assigned: #006398;
    --rm-assigned-tint: #dceafc;
    --rm-transit: #006398;
    --rm-transit-tint: #dceafc;
    --rm-delivered: #0E6655;
    --rm-delivered-tint: #A8D5CC;
    --rm-cancelled: #ba1a1a;
    --rm-cancelled-tint: #ffdad6;
    --rm-on-brand: #ffffff;
    /* RouteMile scopes Bootstrap's reboot vars to `.routew-ui`, and its
       `.routew-ui { background-color: var(--bs-body-bg) }` rule paints the
       whole wrapper with the orange canvas. Re-point both at our palette. */
    --bs-body-bg: #faf8ff;
    --bs-body-color: #131b2e;
}

/* ── Page canvas ──────────────────────────────────────────── */
.woocommerce-account.routew-my-account-styled {
    background:
        radial-gradient(90% 340px at 50% -60px, rgba(61, 157, 67, .07) 0%, rgba(61, 157, 67, 0) 70%),
        #faf8ff !important;
}
/* The `.routew-ui` wrapper carries its own solid `background-color`, which
   would otherwise hide the page canvas above. Let it show through. */
.woocommerce-account.routew-my-account-styled .routew-ui.routew-account,
.routew-ui.routew-account {
    background-color: transparent !important;
}

/* ── Login form heading (plain WC markup, no routew wrapper) ─ */
body.woocommerce-account .woocommerce > h2 { color: #131b2e !important; }
body.woocommerce-account .woocommerce-LostPassword a { color: #16A085 !important; font-weight: 600 !important; }

/* ── Account navigation rail ──────────────────────────────── */
.woocommerce-account.routew-my-account-styled .woocommerce-MyAccount-navigation ul {
    background: #ffffff !important;
    border-color: #dde8e6 !important;
    box-shadow: 0 1px 2px rgba(61, 157, 67, .05), 0 4px 16px rgba(61, 157, 67, .06) !important;
}
.woocommerce-account.routew-my-account-styled .woocommerce-MyAccount-navigation li a {
    color: #3d4947 !important;
}
.woocommerce-account.routew-my-account-styled .woocommerce-MyAccount-navigation li a::before {
    background-color: #6d7a77 !important;
}
.woocommerce-account.routew-my-account-styled .woocommerce-MyAccount-navigation li a:hover {
    background: #f4f3fa !important;
    color: #131b2e !important;
}
.woocommerce-account.routew-my-account-styled .woocommerce-MyAccount-navigation li a:hover::before {
    background-color: #16A085 !important;
}
.woocommerce-account.routew-my-account-styled .woocommerce-MyAccount-navigation li.is-active a {
    background: #D4E6F1 !important;
    color: #0E6655 !important;
}
.woocommerce-account.routew-my-account-styled .woocommerce-MyAccount-navigation li.is-active a::before {
    background-color: #0E6655 !important;
}
.woocommerce-account.routew-my-account-styled .woocommerce-MyAccount-navigation li.is-active a::after {
    background: #16A085 !important;
}
@media (max-width: 782px) {
    .woocommerce-account.routew-my-account-styled .woocommerce-MyAccount-navigation ul {
        background: #ffffff !important;
        border-top-color: #dde8e6 !important;
        box-shadow: 0 -6px 24px rgba(61, 157, 67, .08) !important;
    }
    .woocommerce-account.routew-my-account-styled .woocommerce-MyAccount-navigation li a { color: #6d7a77 !important; }
    .woocommerce-account.routew-my-account-styled .woocommerce-MyAccount-navigation li a::before { background-color: #6d7a77 !important; }
}

/* ── Greeting hero ────────────────────────────────────────── */
.routew-ui .routew-hero {
    background:
        radial-gradient(130% 160% at 88% -20%, rgba(137, 245, 231, .38) 0%, rgba(137, 245, 231, 0) 55%),
        linear-gradient(150deg, #117A65 0%, #16A085 62%) !important;
    box-shadow: 0 12px 32px rgba(61, 157, 67, .25) !important;
}
.routew-ui .routew-hero::before {
    background: radial-gradient(circle, rgba(137, 245, 231, .30) 0%, rgba(137, 245, 231, 0) 70%) !important;
}
.routew-ui .routew-hero__eyebrow {
    color: #89f5e7 !important;
    border-color: rgba(137, 245, 231, .35) !important;
}
.routew-ui .routew-hero__avatar {
    background: rgba(255, 255, 255, .16) !important;
    color: #ffffff !important;
    border-color: rgba(255, 255, 255, .30) !important;
    box-shadow: 0 8px 20px rgba(0, 32, 29, .45) !important;
}

/* ── "Order again" banner ─────────────────────────────────── */
.routew-ui .routew-reorder-banner {
    border-color: #b6e3dd !important;
    background:
        radial-gradient(120% 170% at 100% 0%, rgba(255, 255, 255, .55) 0%, rgba(255, 255, 255, 0) 42%),
        linear-gradient(115deg, #D4E6F1 0%, #b6e3dd 100%) !important;
    box-shadow: 0 6px 20px rgba(61, 157, 67, .16) !important;
}
.routew-ui .routew-reorder-banner__title { color: #00201d !important; }
.routew-ui .routew-reorder-banner__sub { color: #0E6655 !important; }
.routew-ui .routew-reorder-banner--ghost {
    background: #ffffff !important;
    border-color: #dde8e6 !important;
    box-shadow: 0 1px 2px rgba(61, 157, 67, .05), 0 4px 16px rgba(61, 157, 67, .06) !important;
}
.routew-ui .routew-reorder-banner--ghost .routew-reorder-banner__sub { color: #6d7a77 !important; }

/* ── Quick-action tiles ───────────────────────────────────── */
.routew-ui .routew-quick-action { border-color: rgba(61, 157, 67, .08) !important; }
.routew-ui .routew-quick-action:nth-child(1) { background: linear-gradient(165deg, #dff4f1 0%, #f5fbfa 90%) !important; }
.routew-ui .routew-quick-action:nth-child(2) { background: linear-gradient(165deg, #e2f0fa 0%, #f4faff 90%) !important; }
.routew-ui .routew-quick-action:nth-child(3) { background: linear-gradient(165deg, #daf0ee 0%, #f3faf9 90%) !important; }
.routew-ui .routew-quick-action:nth-child(4) { background: linear-gradient(165deg, #e6f3f2 0%, #f6fbfa 90%) !important; }
.routew-ui .routew-quick-action:nth-child(1) .routew-tile-icon { background: #c6e9e3 !important; color: #0E6655 !important; }
.routew-ui .routew-quick-action:nth-child(2) .routew-tile-icon { background: #cfe3f7 !important; color: #006398 !important; }
.routew-ui .routew-quick-action:nth-child(3) .routew-tile-icon { background: #c6e9e3 !important; color: #0E6655 !important; }
.routew-ui .routew-quick-action:nth-child(4) .routew-tile-icon { background: #d4ebe8 !important; color: #16A085 !important; }
.routew-ui .routew-quick-action__label { color: #131b2e !important; }
.routew-ui .routew-quick-action__hint  { color: #6d7a77 !important; }
.routew-ui .routew-quick-action:hover   { box-shadow: 0 10px 26px rgba(61, 157, 67, .13) !important; }

/* ── Tile icons ───────────────────────────────────────────── */
.routew-ui .routew-tile-icon--brand     { background: #D4E6F1 !important; color: #0E6655 !important; }
.routew-ui .routew-tile-icon--transit   { background: #D4E6F1 !important; color: #16A085 !important; }
.routew-ui .routew-tile-icon--delivered { background: #cdeee9 !important; color: #0E6655 !important; }
.routew-ui .routew-tile-icon--assigned  { background: #dceafc !important; color: #006398 !important; }
.routew-ui .routew-tile-icon--neutral   { background: #f4f3fa !important; color: #3d4947 !important; }

/* ── Status pills ─────────────────────────────────────────── */
.routew-ui .routew-pill--placed    { background: #eaedff !important; color: #3d4947 !important; }
.routew-ui .routew-pill--preparing { background: #D4E6F1 !important; color: #16A085 !important; }
.routew-ui .routew-pill--assigned  { background: #dceafc !important; color: #006398 !important; }
.routew-ui .routew-pill--transit   { background: #dceafc !important; color: #006398 !important; }
.routew-ui .routew-pill--delivered { background: #cdeee9 !important; color: #0E6655 !important; }
.routew-ui .routew-pill--cancelled { background: #ffdad6 !important; color: #ba1a1a !important; }

/* ── Misc atoms ───────────────────────────────────────────── */
.routew-ui .routew-chevron       { background: #eaedff !important; color: #6d7a77 !important; }
.routew-ui .routew-empty__icon   { background: #f4f3fa !important; }
.routew-ui .routew-empty__title  { color: #131b2e !important; }
.routew-ui .routew-empty__text   { color: #6d7a77 !important; }
.routew-ui .routew-section-title__label  { color: #131b2e !important; }
.routew-ui .routew-section-title__label::before { background: linear-gradient(180deg, #16A085 0%, #0E6655 100%) !important; }
.routew-ui .routew-section-title__action { color: #16A085 !important; }
.routew-ui .routew-lead { color: #6d7a77 !important; }

/* ── Recent-order rows ────────────────────────────────────── */
.routew-ui .routew-order-row { background: #ffffff !important; border-bottom-color: #dde8e6 !important; }
.routew-ui .routew-order-row::before              { background: #bec9c6 !important; }
.routew-ui .routew-order-row--placed::before      { background: #bec9c6 !important; }
.routew-ui .routew-order-row--preparing::before   { background: #16A085 !important; }
.routew-ui .routew-order-row--assigned::before    { background: #006398 !important; }
.routew-ui .routew-order-row--transit::before     { background: #006398 !important; }
.routew-ui .routew-order-row--delivered::before   { background: #00897b !important; }
.routew-ui .routew-order-row--cancelled::before   { background: #ba1a1a !important; }
.routew-ui .routew-order-row:hover                { background: #f2f8f7 !important; }
.routew-ui .routew-order-row:hover .routew-order-row__number { color: #0E6655 !important; }
.routew-ui .routew-order-row__number  { color: #131b2e !important; }
.routew-ui .routew-order-row__summary { color: #3d4947 !important; }
.routew-ui .routew-order-row__meta    { color: #6d7a77 !important; }
.routew-ui .routew-order-row__total   { color: #131b2e !important; }

/* ── Cards & lists ────────────────────────────────────────── */
.routew-ui .card,
.routew-ui .routew-order-list { background-color: #ffffff !important; border-color: #dde8e6 !important; }
.routew-ui .routew-list {
    background: #ffffff !important;
    border-color: #dde8e6 !important;
    box-shadow: 0 1px 2px rgba(61, 157, 67, .05), 0 4px 16px rgba(61, 157, 67, .06) !important;
}
.routew-ui .routew-list .routew-list-item {
    background: #ffffff !important;
    border-color: #dde8e6 !important;
    color: #131b2e !important;
}
.routew-ui .routew-list .routew-list-item:hover { background: #f2f8f7 !important; }
.routew-ui .routew-list .routew-list-item:hover .routew-list-item__label { color: #0E6655 !important; }
.routew-ui .routew-list .routew-list-item__label { color: #131b2e !important; }
.routew-ui .routew-list .routew-list-item__hint  { color: #6d7a77 !important; }
.routew-ui .routew-address-card {
    background: linear-gradient(160deg, #f4f8f7 0%, #ffffff 65%) !important;
    border-color: #b6e3dd !important;
    box-shadow: 0 1px 2px rgba(61, 157, 67, .05), 0 4px 16px rgba(61, 157, 67, .06) !important;
}
.routew-ui .routew-address-card__name { color: #131b2e !important; }
.routew-ui .routew-address-card__line { color: #6d7a77 !important; }

/* ── Buttons ──────────────────────────────────────────────── */
.routew-ui .routew-btn-brand {
    background: linear-gradient(160deg, #16A085 0%, #0E6655 100%) !important;
    box-shadow: 0 8px 20px rgba(61, 157, 67, .30) !important;
}
.routew-ui .routew-btn-brand:hover,
.routew-ui .routew-btn-brand:focus-visible {
    background: linear-gradient(160deg, #00796e 0%, #16A085 100%) !important;
    box-shadow: 0 10px 26px rgba(61, 157, 67, .38) !important;
}
.routew-ui .routew-btn-signout {
    border-color: #dde8e6 !important;
    background: #ffffff !important;
    color: #3d4947 !important;
    box-shadow: 0 1px 2px rgba(61, 157, 67, .05), 0 4px 16px rgba(61, 157, 67, .06) !important;
}
.routew-ui .routew-btn-signout:hover {
    border-color: #ba1a1a !important;
    background: #ffdad6 !important;
    color: #ba1a1a !important;
}

/* ── Sub-page header + profile card ───────────────────────── */
.routew-ui .routew-subpage-header__back {
    background: #ffffff !important;
    border-color: #dde8e6 !important;
    color: #131b2e !important;
}
.routew-ui .routew-subpage-header__back:hover {
    background: #f4f3fa !important;
    border-color: #bec9c6 !important;
    color: #16A085 !important;
}
.routew-ui .routew-subpage-header__title { color: #131b2e !important; }
.routew-ui .routew-subpage-profile {
    background: #ffffff !important;
    border-color: #dde8e6 !important;
    box-shadow: 0 1px 2px rgba(61, 157, 67, .05), 0 4px 16px rgba(61, 157, 67, .06) !important;
}
.routew-ui .routew-subpage-profile__name  { color: #131b2e !important; }
.routew-ui .routew-subpage-profile__email { color: #6d7a77 !important; }

/* ── Order filter chips ───────────────────────────────────── */
.routew-ui .routew-orders-filter__chip {
    background: #ffffff !important;
    border-color: #dde8e6 !important;
    color: #3d4947 !important;
}
.routew-ui .routew-orders-filter__chip:hover {
    background: #f4f3fa !important;
    border-color: #bec9c6 !important;
    color: #131b2e !important;
}
.routew-ui .routew-orders-filter__chip--active,
.routew-ui .routew-orders-filter__chip--active:hover {
    background: #16A085 !important;
    border-color: #16A085 !important;
    color: #ffffff !important;
}

/* ── Tables (orders, order details, payment methods) ──────── */
.routew-ui.routew-account table.woocommerce-orders-table,
.routew-ui.routew-account table.woocommerce-MyAccount-paymentMethods,
.routew-ui.routew-account table.woocommerce-table--order-details {
    border-color: #dde8e6 !important;
    background: #ffffff !important;
    box-shadow: 0 1px 2px rgba(61, 157, 67, .05), 0 4px 16px rgba(61, 157, 67, .06) !important;
}
.routew-ui.routew-account table.woocommerce-orders-table th,
.routew-ui.routew-account table.woocommerce-MyAccount-paymentMethods th,
.routew-ui.routew-account table.woocommerce-table--order-details th {
    color: #6d7a77 !important;
    background: #f8faf9 !important;
    border-bottom-color: #dde8e6 !important;
}
.routew-ui.routew-account table.woocommerce-orders-table td,
.routew-ui.routew-account table.woocommerce-MyAccount-paymentMethods td,
.routew-ui.routew-account table.woocommerce-table--order-details td {
    color: #3d4947 !important;
    border-bottom-color: #e6edec !important;
}
.routew-ui.routew-account table a { color: #16A085 !important; }
.routew-ui.routew-account table a:hover { color: #003732 !important; }
.routew-ui.routew-account table.woocommerce-table--order-details .woocommerce-Price-amount { color: #131b2e !important; }
.routew-ui.routew-account table .woocommerce-button,
.routew-ui.routew-account table .button,
.routew-ui.routew-account table.woocommerce-table--order-details td a.order-actions-button {
    border-color: #dde8e6 !important;
    background: #f4f3fa !important;
    color: #131b2e !important;
}
.routew-ui.routew-account table .woocommerce-button:hover,
.routew-ui.routew-account table .button:hover,
.routew-ui.routew-account table.woocommerce-table--order-details td a.order-actions-button:hover {
    background: #D4E6F1 !important;
    border-color: #b6e3dd !important;
    color: #0E6655 !important;
}
.routew-ui.routew-account table.woocommerce-table--order-details td a.routew_reorder {
    background: #16A085 !important;
    color: #ffffff !important;
}
.routew-ui.routew-account table.woocommerce-table--order-details td a.routew_reorder:hover {
    background: #0E6655 !important;
}
.routew-ui.routew-account .order-again .button { background: #16A085 !important; color: #ffffff !important; }
.routew-ui.routew-account .order-again .button:hover { background: #0E6655 !important; }

/* ── Order status badges ──────────────────────────────────── */
.routew-ui.routew-account .woocommerce-orders-table__cell-order-status mark,
.routew-ui.routew-account .woocommerce-order-overview mark.order-status {
    background: #eaedff !important;
    color: #3d4947 !important;
}
.routew-ui.routew-account mark.order-status.status-processing,
.routew-ui.routew-account mark.order-status.status-routew-in-kitchen,
.routew-ui.routew-account mark.order-status.status-routew-preparing { background: #D4E6F1 !important; color: #16A085 !important; }
.routew-ui.routew-account mark.order-status.status-routew-assigned   { background: #dceafc !important; color: #006398 !important; }
.routew-ui.routew-account mark.order-status.status-routew-picked-up,
.routew-ui.routew-account mark.order-status.status-routew-transit    { background: #dceafc !important; color: #006398 !important; }
.routew-ui.routew-account mark.order-status.status-completed,
.routew-ui.routew-account mark.order-status.status-routew-delivered { background: #cdeee9 !important; color: #0E6655 !important; }
.routew-ui.routew-account mark.order-status.status-cancelled,
.routew-ui.routew-account mark.order-status.status-failed,
.routew-ui.routew-account mark.order-status.status-refunded          { background: #ffdad6 !important; color: #ba1a1a !important; }

/* ── Order overview list ──────────────────────────────────── */
.routew-ui.routew-account .woocommerce-order-overview {
    background: #ffffff !important;
    border-color: #dde8e6 !important;
    box-shadow: 0 1px 2px rgba(61, 157, 67, .05), 0 4px 16px rgba(61, 157, 67, .06) !important;
}
.routew-ui.routew-account .woocommerce-order-overview li { border-color: #dde8e6 !important; }
.routew-ui.routew-account .woocommerce-order-overview li strong { color: #6d7a77 !important; }
.routew-ui.routew-account .woocommerce-order-overview li > span { color: #131b2e !important; }

/* ── Address cards ────────────────────────────────────────── */
.routew-ui.routew-account .routew-address-col {
    background: #ffffff !important;
    border-color: #dde8e6 !important;
    box-shadow: 0 1px 2px rgba(61, 157, 67, .05), 0 4px 16px rgba(61, 157, 67, .06) !important;
}
.routew-ui.routew-account .routew-address-col::before,
.routew-ui.routew-account .routew-address-col header.routew-address-card-header h2::before { background: #006398 !important; }
.routew-ui.routew-account .routew-address-col--shipping::before,
.routew-ui.routew-account .routew-address-col--shipping header.routew-address-card-header h2::before { background: #16A085 !important; }
.routew-ui.routew-account .routew-address-col header.routew-address-card-header { border-bottom-color: #f4f3fa !important; }
.routew-ui.routew-account .routew-address-col header.routew-address-card-header h2 { color: #131b2e !important; }
.routew-ui.routew-account .routew-address-col address { color: #3d4947 !important; }
.routew-ui.routew-account .routew-address-col a.routew-address-edit {
    background: #D4E6F1 !important;
    color: #0E6655 !important;
}
.routew-ui.routew-account .routew-address-col a.routew-address-edit::before { background-color: #0E6655 !important; }
.routew-ui.routew-account .routew-address-col a.routew-address-edit:hover {
    background: #b6e3dd !important;
    border-color: #16A085 !important;
    color: #003732 !important;
}
.routew-ui.routew-account .routew-address-col a.routew-address-edit:hover::before { background-color: #003732 !important; }
.routew-ui.routew-account .routew-address-col--empty { background: rgba(255, 255, 255, .6) !important; }
.routew-ui.routew-account .routew-address-col--empty::before { background: #dde8e6 !important; }
.routew-ui.routew-account .routew-address-col--empty address { color: #6d7a77 !important; }

/* ── Forms ────────────────────────────────────────────────── */
.routew-ui.routew-account form fieldset {
    border-color: #dde8e6 !important;
    background: #ffffff !important;
}
.routew-ui.routew-account form legend { color: #131b2e !important; }
.routew-ui.routew-account form label  { color: #131b2e !important; }
.routew-ui.routew-account form .button { background: #16A085 !important; color: #ffffff !important; }
.routew-ui.routew-account form .button:hover { background: #0E6655 !important; }

/* ── Customer details card ────────────────────────────────── */
.routew-ui.routew-account .woocommerce-customer-details > section {
    background: #ffffff !important;
    border-color: #dde8e6 !important;
    box-shadow: 0 1px 2px rgba(61, 157, 67, .05), 0 4px 16px rgba(61, 157, 67, .06) !important;
}
.routew-ui.routew-account .woocommerce-customer-details address { color: #3d4947 !important; }
.routew-ui.routew-account .woocommerce-customer-details h2 { color: #131b2e !important; }

/* ── Notices ──────────────────────────────────────────────── */
.routew-ui.routew-account .woocommerce-message,
.routew-ui.routew-account .woocommerce-info,
.routew-ui.routew-account .woocommerce-error {
    border-color: #dde8e6 !important;
    background: #f4f3fa !important;
    color: #3d4947 !important;
}
.routew-ui.routew-account .woocommerce-message { background: #D4E6F1 !important; border-color: #b6e3dd !important; color: #0E6655 !important; }
.routew-ui.routew-account .woocommerce-error   { background: #ffdad6 !important; border-color: #f5c6c6 !important; color: #ba1a1a !important; }
.routew-ui.routew-account .woocommerce-message a,
.routew-ui.routew-account .woocommerce-info a,
.routew-ui.routew-account .woocommerce-error a { color: #16A085 !important; }

/* ═══════════════════════════════════════════════════════════
   DARK MODE — Warm Slate Teal
   RouteMile ships no dark theme, so the whole account gets one.
   ═══════════════════════════════════════════════════════════ */
html.dark .woocommerce-account.routew-my-account-styled,
html.dark .woocommerce-account.routew-my-account-styled .routew-ui,
html.dark .routew-ui.routew-account {
    --rm-brand: #2f9e91;
    --rm-brand-strong: #1f7a70;
    --rm-brand-deep: #17594f;
    --rm-brand-soft: rgba(94, 207, 194, .14);
    --rm-brand-line: rgba(94, 207, 194, .28);
    --rm-ink: #dff0ed;
    --rm-body: #cfe6e2;
    --rm-muted: #8db5b0;
    --rm-faint: #6a9e99;
    --rm-line: rgba(80, 130, 125, .35);
    --rm-wash: #1a2e2c;
    --rm-surface: #132220;
    --rm-canvas: #0e1b1a;
    --rm-canvas-2: #132220;
    --rm-tint-orange: rgba(94, 207, 194, .12);
    --rm-tint-blue: rgba(94, 207, 194, .12);
    --rm-tint-green: rgba(94, 207, 194, .12);
    --rm-tint-amber: rgba(94, 207, 194, .12);
    --rm-brand-canvas-wash: #162a28;
    --rm-on-brand: #00201d;
    --rm-shadow-card: 0 1px 2px rgba(0, 15, 14, .5), 0 4px 16px rgba(0, 15, 14, .45);
    --bs-body-bg: #0e1b1a;
    --bs-body-color: #dff0ed;
}

html.dark .woocommerce-account.routew-my-account-styled {
    background: radial-gradient(90% 340px at 50% -60px, rgba(94, 207, 194, .07) 0%, rgba(94, 207, 194, 0) 70%), #0e1b1a !important;
}
html.dark .woocommerce-account.routew-my-account-styled .routew-ui.routew-account,
html.dark .routew-ui.routew-account {
    background-color: transparent !important;
}

/* Headings + login links */
html.dark body.woocommerce-account .woocommerce > h2 { color: #dff0ed !important; }
html.dark body.woocommerce-account .woocommerce-LostPassword a { color: #5ecfc2 !important; }

/* Nav rail */
html.dark .woocommerce-account.routew-my-account-styled .woocommerce-MyAccount-navigation ul {
    background: #132220 !important;
    border-color: rgba(80, 130, 125, .35) !important;
    box-shadow: 0 1px 2px rgba(0, 15, 14, .5), 0 4px 16px rgba(0, 15, 14, .45) !important;
}
html.dark .woocommerce-account.routew-my-account-styled .woocommerce-MyAccount-navigation li a { color: #cfe6e2 !important; }
html.dark .woocommerce-account.routew-my-account-styled .woocommerce-MyAccount-navigation li a::before { background-color: #8db5b0 !important; }
html.dark .woocommerce-account.routew-my-account-styled .woocommerce-MyAccount-navigation li a:hover { background: #1a2e2c !important; color: #dff0ed !important; }
html.dark .woocommerce-account.routew-my-account-styled .woocommerce-MyAccount-navigation li a:hover::before { background-color: #5ecfc2 !important; }
html.dark .woocommerce-account.routew-my-account-styled .woocommerce-MyAccount-navigation li.is-active a { background: rgba(94, 207, 194, .16) !important; color: #89f5e7 !important; }
html.dark .woocommerce-account.routew-my-account-styled .woocommerce-MyAccount-navigation li.is-active a::before { background-color: #89f5e7 !important; }
html.dark .woocommerce-account.routew-my-account-styled .woocommerce-MyAccount-navigation li.is-active a::after { background: #5ecfc2 !important; }
@media (max-width: 782px) {
    html.dark .woocommerce-account.routew-my-account-styled .woocommerce-MyAccount-navigation ul {
        background: #132220 !important;
        border-top-color: rgba(80, 130, 125, .35) !important;
        box-shadow: 0 -6px 24px rgba(0, 15, 14, .6) !important;
    }
    html.dark .woocommerce-account.routew-my-account-styled .woocommerce-MyAccount-navigation li a { color: #8db5b0 !important; }
    html.dark .woocommerce-account.routew-my-account-styled .woocommerce-MyAccount-navigation li a::before { background-color: #8db5b0 !important; }
}

/* Hero */
html.dark .routew-ui .routew-hero {
    background:
        radial-gradient(130% 160% at 88% -20%, rgba(94, 207, 194, .35) 0%, rgba(94, 207, 194, 0) 55%),
        linear-gradient(150deg, #14524b 0%, #0b2a27 62%) !important;
    box-shadow: 0 12px 32px rgba(0, 15, 14, .55) !important;
}
html.dark .routew-ui .routew-hero::before { background: radial-gradient(circle, rgba(94, 207, 194, .28) 0%, rgba(94, 207, 194, 0) 70%) !important; }
html.dark .routew-ui .routew-hero__eyebrow { color: #89f5e7 !important; border-color: rgba(137, 245, 231, .3) !important; background: rgba(255, 255, 255, .08) !important; }
html.dark .routew-ui .routew-hero__avatar { background: rgba(255, 255, 255, .12) !important; color: #ffffff !important; border-color: rgba(94, 207, 194, .35) !important; box-shadow: 0 8px 20px rgba(0, 15, 14, .6) !important; }

/* Order-again banner */
html.dark .routew-ui .routew-reorder-banner {
    background: radial-gradient(120% 170% at 100% 0%, rgba(255, 255, 255, .06) 0%, rgba(255, 255, 255, 0) 42%), linear-gradient(115deg, #162a28 0%, #123f3a 100%) !important;
    border-color: rgba(94, 207, 194, .28) !important;
    box-shadow: 0 6px 20px rgba(0, 15, 14, .45) !important;
}
html.dark .routew-ui .routew-reorder-banner__title { color: #dff0ed !important; }
html.dark .routew-ui .routew-reorder-banner__sub { color: #8db5b0 !important; }
html.dark .routew-ui .routew-reorder-banner--ghost { background: #132220 !important; border-color: rgba(80, 130, 125, .35) !important; }
html.dark .routew-ui .routew-reorder-banner--ghost .routew-reorder-banner__sub { color: #8db5b0 !important; }

/* Quick-action tiles */
html.dark .routew-ui .routew-quick-action { border-color: rgba(80, 130, 125, .3) !important; }
html.dark .routew-ui .routew-quick-action:nth-child(1),
html.dark .routew-ui .routew-quick-action:nth-child(2),
html.dark .routew-ui .routew-quick-action:nth-child(3),
html.dark .routew-ui .routew-quick-action:nth-child(4) { background: linear-gradient(165deg, #162a28 0%, #132220 90%) !important; }
html.dark .routew-ui .routew-quick-action:hover { box-shadow: 0 10px 26px rgba(0, 15, 14, .6) !important; }
html.dark .routew-ui .routew-quick-action .routew-tile-icon {
    background: rgba(94, 207, 194, .16) !important;
    color: #5ecfc2 !important;
    box-shadow: inset 0 0 0 1px rgba(94, 207, 194, .18), 0 4px 10px rgba(0, 15, 14, .5) !important;
}
html.dark .routew-ui .routew-quick-action__label { color: #dff0ed !important; }
html.dark .routew-ui .routew-quick-action__hint  { color: #8db5b0 !important; }

/* Tile icons */
html.dark .routew-ui .routew-tile-icon--brand,
html.dark .routew-ui .routew-tile-icon--transit,
html.dark .routew-ui .routew-tile-icon--delivered,
html.dark .routew-ui .routew-tile-icon--assigned,
html.dark .routew-ui .routew-tile-icon--neutral { background: rgba(94, 207, 194, .16) !important; color: #5ecfc2 !important; }

/* Pills */
html.dark .routew-ui .routew-pill--placed    { background: rgba(141, 181, 176, .18) !important; color: #cfe6e2 !important; }
html.dark .routew-ui .routew-pill--preparing { background: rgba(94, 207, 194, .16) !important; color: #89f5e7 !important; }
html.dark .routew-ui .routew-pill--assigned,
html.dark .routew-ui .routew-pill--transit   { background: rgba(129, 196, 255, .16) !important; color: #81c4ff !important; }
html.dark .routew-ui .routew-pill--delivered { background: rgba(94, 207, 194, .22) !important; color: #89f5e7 !important; }
html.dark .routew-ui .routew-pill--cancelled { background: rgba(255, 179, 171, .16) !important; color: #ffb3ab !important; }

/* Misc atoms */
html.dark .routew-ui .routew-chevron      { background: #1a2e2c !important; color: #8db5b0 !important; }
html.dark .routew-ui .routew-empty__icon  { background: #1a2e2c !important; }
html.dark .routew-ui .routew-empty__title { color: #dff0ed !important; }
html.dark .routew-ui .routew-empty__text  { color: #8db5b0 !important; }
html.dark .routew-ui .routew-section-title__label { color: #dff0ed !important; }
html.dark .routew-ui .routew-section-title__label::before { background: linear-gradient(180deg, #5ecfc2 0%, #2f9e91 100%) !important; }
html.dark .routew-ui .routew-section-title__action { color: #5ecfc2 !important; }
html.dark .routew-ui .routew-lead { color: #8db5b0 !important; }

/* Order rows */
html.dark .routew-ui .routew-order-row { background: #132220 !important; border-bottom-color: rgba(80, 130, 125, .3) !important; }
html.dark .routew-ui .routew-order-row::before            { background: #6a9e99 !important; }
html.dark .routew-ui .routew-order-row--placed::before    { background: #6a9e99 !important; }
html.dark .routew-ui .routew-order-row--preparing::before { background: #5ecfc2 !important; }
html.dark .routew-ui .routew-order-row--assigned::before,
html.dark .routew-ui .routew-order-row--transit::before   { background: #81c4ff !important; }
html.dark .routew-ui .routew-order-row--delivered::before { background: #89f5e7 !important; }
html.dark .routew-ui .routew-order-row--cancelled::before { background: #ffb3ab !important; }
html.dark .routew-ui .routew-order-row:hover { background: #1a2e2c !important; }
html.dark .routew-ui .routew-order-row:hover .routew-order-row__number { color: #5ecfc2 !important; }
html.dark .routew-ui .routew-order-row__number  { color: #dff0ed !important; }
html.dark .routew-ui .routew-order-row__summary { color: #cfe6e2 !important; }
html.dark .routew-ui .routew-order-row__meta    { color: #8db5b0 !important; }
html.dark .routew-ui .routew-order-row__total   { color: #dff0ed !important; }

/* Cards & lists */
html.dark .routew-ui .card,
html.dark .routew-ui .routew-order-list,
html.dark .routew-ui .routew-list { background-color: #132220 !important; border-color: rgba(80, 130, 125, .35) !important; }
html.dark .routew-ui .routew-list { box-shadow: 0 1px 2px rgba(0, 15, 14, .5), 0 4px 16px rgba(0, 15, 14, .45) !important; }
html.dark .routew-ui .routew-list .routew-list-item { background: #132220 !important; border-color: rgba(80, 130, 125, .3) !important; color: #dff0ed !important; }
html.dark .routew-ui .routew-list .routew-list-item:hover { background: #1a2e2c !important; }
html.dark .routew-ui .routew-list .routew-list-item:hover .routew-list-item__label { color: #5ecfc2 !important; }
html.dark .routew-ui .routew-list .routew-list-item__label { color: #dff0ed !important; }
html.dark .routew-ui .routew-list .routew-list-item__hint  { color: #8db5b0 !important; }
html.dark .routew-ui .routew-address-card {
    background: linear-gradient(160deg, #162a28 0%, #132220 65%) !important;
    border-color: rgba(94, 207, 194, .28) !important;
    box-shadow: 0 1px 2px rgba(0, 15, 14, .5), 0 4px 16px rgba(0, 15, 14, .45) !important;
}
html.dark .routew-ui .routew-address-card__name { color: #dff0ed !important; }
html.dark .routew-ui .routew-address-card__line { color: #8db5b0 !important; }

/* Buttons */
html.dark .routew-ui .routew-btn-brand {
    background: linear-gradient(160deg, #5ecfc2 0%, #2f9e91 100%) !important;
    color: #00201d !important;
    box-shadow: 0 8px 20px rgba(0, 15, 14, .5) !important;
}
html.dark .routew-ui .routew-btn-brand:hover,
html.dark .routew-ui .routew-btn-brand:focus-visible {
    background: linear-gradient(160deg, #89f5e7 0%, #5ecfc2 100%) !important;
    color: #00201d !important;
}
html.dark .routew-ui .routew-btn-signout {
    border-color: rgba(80, 130, 125, .45) !important;
    background: #132220 !important;
    color: #cfe6e2 !important;
    box-shadow: 0 1px 2px rgba(0, 15, 14, .5), 0 4px 16px rgba(0, 15, 14, .45) !important;
}
html.dark .routew-ui .routew-btn-signout:hover { border-color: #ffb3ab !important; background: rgba(255, 179, 171, .14) !important; color: #ffb3ab !important; }

/* Sub-page header + profile */
html.dark .routew-ui .routew-subpage-header__back { background: #132220 !important; border-color: rgba(80, 130, 125, .4) !important; color: #dff0ed !important; }
html.dark .routew-ui .routew-subpage-header__back:hover { background: #1a2e2c !important; border-color: rgba(94, 207, 194, .45) !important; color: #5ecfc2 !important; }
html.dark .routew-ui .routew-subpage-header__title { color: #dff0ed !important; }
html.dark .routew-ui .routew-subpage-profile { background: #132220 !important; border-color: rgba(80, 130, 125, .35) !important; }
html.dark .routew-ui .routew-subpage-profile__name  { color: #dff0ed !important; }
html.dark .routew-ui .routew-subpage-profile__email { color: #8db5b0 !important; }

/* Filter chips */
html.dark .routew-ui .routew-orders-filter__chip { background: #132220 !important; border-color: rgba(80, 130, 125, .4) !important; color: #cfe6e2 !important; }
html.dark .routew-ui .routew-orders-filter__chip:hover { background: #1a2e2c !important; border-color: rgba(94, 207, 194, .45) !important; color: #dff0ed !important; }
html.dark .routew-ui .routew-orders-filter__chip--active,
html.dark .routew-ui .routew-orders-filter__chip--active:hover { background: #5ecfc2 !important; border-color: #5ecfc2 !important; color: #00201d !important; }

/* Tables */
html.dark .routew-ui.routew-account table.woocommerce-orders-table,
html.dark .routew-ui.routew-account table.woocommerce-MyAccount-paymentMethods,
html.dark .routew-ui.routew-account table.woocommerce-table--order-details { border-color: rgba(80, 130, 125, .35) !important; background: #132220 !important; }
html.dark .routew-ui.routew-account table.woocommerce-orders-table th,
html.dark .routew-ui.routew-account table.woocommerce-MyAccount-paymentMethods th,
html.dark .routew-ui.routew-account table.woocommerce-table--order-details th { color: #8db5b0 !important; background: #162a28 !important; border-bottom-color: rgba(80, 130, 125, .35) !important; }
html.dark .routew-ui.routew-account table.woocommerce-orders-table td,
html.dark .routew-ui.routew-account table.woocommerce-MyAccount-paymentMethods td,
html.dark .routew-ui.routew-account table.woocommerce-table--order-details td { color: #cfe6e2 !important; border-bottom-color: rgba(80, 130, 125, .28) !important; }
html.dark .routew-ui.routew-account table a { color: #5ecfc2 !important; }
html.dark .routew-ui.routew-account table a:hover { color: #89f5e7 !important; }
html.dark .routew-ui.routew-account table.woocommerce-table--order-details .woocommerce-Price-amount { color: #dff0ed !important; }
html.dark .routew-ui.routew-account table .woocommerce-button,
html.dark .routew-ui.routew-account table .button,
html.dark .routew-ui.routew-account table.woocommerce-table--order-details td a.order-actions-button { border-color: rgba(80, 130, 125, .4) !important; background: #1a2e2c !important; color: #dff0ed !important; }
html.dark .routew-ui.routew-account table .woocommerce-button:hover,
html.dark .routew-ui.routew-account table .button:hover,
html.dark .routew-ui.routew-account table.woocommerce-table--order-details td a.order-actions-button:hover { background: rgba(94, 207, 194, .16) !important; border-color: rgba(94, 207, 194, .4) !important; color: #89f5e7 !important; }
html.dark .routew-ui.routew-account table.woocommerce-table--order-details td a.routew_reorder,
html.dark .routew-ui.routew-account .order-again .button { background: #5ecfc2 !important; color: #00201d !important; }
html.dark .routew-ui.routew-account table.woocommerce-table--order-details td a.routew_reorder:hover,
html.dark .routew-ui.routew-account .order-again .button:hover { background: #89f5e7 !important; }

/* Status badges */
html.dark .routew-ui.routew-account .woocommerce-orders-table__cell-order-status mark,
html.dark .routew-ui.routew-account .woocommerce-order-overview mark.order-status { background: rgba(141, 181, 176, .18) !important; color: #cfe6e2 !important; }
html.dark .routew-ui.routew-account mark.order-status.status-processing,
html.dark .routew-ui.routew-account mark.order-status.status-routew-in-kitchen,
html.dark .routew-ui.routew-account mark.order-status.status-routew-preparing { background: rgba(94, 207, 194, .16) !important; color: #89f5e7 !important; }
html.dark .routew-ui.routew-account mark.order-status.status-routew-assigned,
html.dark .routew-ui.routew-account mark.order-status.status-routew-picked-up,
html.dark .routew-ui.routew-account mark.order-status.status-routew-transit { background: rgba(129, 196, 255, .16) !important; color: #81c4ff !important; }
html.dark .routew-ui.routew-account mark.order-status.status-completed,
html.dark .routew-ui.routew-account mark.order-status.status-routew-delivered { background: rgba(94, 207, 194, .22) !important; color: #89f5e7 !important; }
html.dark .routew-ui.routew-account mark.order-status.status-cancelled,
html.dark .routew-ui.routew-account mark.order-status.status-failed,
html.dark .routew-ui.routew-account mark.order-status.status-refunded { background: rgba(255, 179, 171, .16) !important; color: #ffb3ab !important; }

/* Order overview */
html.dark .routew-ui.routew-account .woocommerce-order-overview { background: #132220 !important; border-color: rgba(80, 130, 125, .35) !important; }
html.dark .routew-ui.routew-account .woocommerce-order-overview li { border-color: rgba(80, 130, 125, .3) !important; }
html.dark .routew-ui.routew-account .woocommerce-order-overview li strong { color: #8db5b0 !important; }
html.dark .routew-ui.routew-account .woocommerce-order-overview li > span { color: #dff0ed !important; }

/* Address cards */
html.dark .routew-ui.routew-account .routew-address-col { background: #132220 !important; border-color: rgba(80, 130, 125, .35) !important; }
html.dark .routew-ui.routew-account .routew-address-col::before,
html.dark .routew-ui.routew-account .routew-address-col header.routew-address-card-header h2::before { background: #81c4ff !important; }
html.dark .routew-ui.routew-account .routew-address-col--shipping::before,
html.dark .routew-ui.routew-account .routew-address-col--shipping header.routew-address-card-header h2::before { background: #5ecfc2 !important; }
html.dark .routew-ui.routew-account .routew-address-col header.routew-address-card-header { border-bottom-color: rgba(80, 130, 125, .3) !important; }
html.dark .routew-ui.routew-account .routew-address-col header.routew-address-card-header h2 { color: #dff0ed !important; }
html.dark .routew-ui.routew-account .routew-address-col address { color: #cfe6e2 !important; }
html.dark .routew-ui.routew-account .routew-address-col a.routew-address-edit { background: rgba(94, 207, 194, .16) !important; color: #89f5e7 !important; }
html.dark .routew-ui.routew-account .routew-address-col a.routew-address-edit::before { background-color: #89f5e7 !important; }
html.dark .routew-ui.routew-account .routew-address-col a.routew-address-edit:hover { background: rgba(94, 207, 194, .26) !important; border-color: rgba(94, 207, 194, .45) !important; color: #dff0ed !important; }
html.dark .routew-ui.routew-account .routew-address-col--empty { background: rgba(19, 34, 32, .6) !important; }
html.dark .routew-ui.routew-account .routew-address-col--empty::before { background: rgba(80, 130, 125, .35) !important; }
html.dark .routew-ui.routew-account .routew-address-col--empty address { color: #8db5b0 !important; }

/* Forms */
html.dark .routew-ui.routew-account form fieldset { border-color: rgba(80, 130, 125, .35) !important; background: #132220 !important; }
html.dark .routew-ui.routew-account form legend { color: #dff0ed !important; }
html.dark .routew-ui.routew-account form label  { color: #dff0ed !important; }
html.dark .routew-ui.routew-account form .button { background: #5ecfc2 !important; color: #00201d !important; }
html.dark .routew-ui.routew-account form .button:hover { background: #89f5e7 !important; }

/* Customer details */
html.dark .routew-ui.routew-account .woocommerce-customer-details > section { background: #132220 !important; border-color: rgba(80, 130, 125, .35) !important; }
html.dark .routew-ui.routew-account .woocommerce-customer-details address { color: #cfe6e2 !important; }
html.dark .routew-ui.routew-account .woocommerce-customer-details h2 { color: #dff0ed !important; }

/* Notices */
html.dark .routew-ui.routew-account .woocommerce-message,
html.dark .routew-ui.routew-account .woocommerce-info,
html.dark .routew-ui.routew-account .woocommerce-error { border-color: rgba(80, 130, 125, .35) !important; background: #1a2e2c !important; color: #cfe6e2 !important; }
html.dark .routew-ui.routew-account .woocommerce-message { background: #0d2825 !important; border-color: rgba(94, 207, 194, .35) !important; color: #89f5e7 !important; }
html.dark .routew-ui.routew-account .woocommerce-error   { background: #2d1216 !important; border-color: rgba(255, 179, 171, .35) !important; color: #ffb3ab !important; }
html.dark .routew-ui.routew-account .woocommerce-message a,
html.dark .routew-ui.routew-account .woocommerce-info a,
html.dark .routew-ui.routew-account .woocommerce-error a { color: #5ecfc2 !important; }

</style>
    <?php
}

/**
 * Login / registration screens (logged-out visitors) — the centred card layout.
 *
 * Shared by the my-account page and the stand-alone registration page, both of
 * which carry the `baraa-auth-page` body class.
 */
function baraa_pharmacy_auth_screen_styles()
{
    if (!baraa_pharmacy_is_auth_screen()) {
        return;
    }
    ?>
<style id="baraa-auth-screen">
/* ═══════════════════════════════════════════════════════════
   LOGIN / REGISTER — centred auth screen (logged out)
   ═══════════════════════════════════════════════════════════ */

/* index.php prints the page title as a raw <h1> — the auth screen has its own. */
body.baraa-auth-page .baraa-section > article > h1 { display: none !important; }
body.baraa-auth-page .baraa-section > article > div,
body.baraa-auth-page .baraa-section .woocommerce { width: 100% !important; }

/* Keep WooCommerce notices aligned with the centred card grid. */
body.baraa-auth-page .woocommerce-notices-wrapper {
    width: 100% !important; max-width: 1040px !important;
    margin: 0 auto 1.5rem !important; padding: 0 !important;
}
body.baraa-auth-page .woocommerce-error,
body.baraa-auth-page .woocommerce-info,
body.baraa-auth-page .woocommerce-message {
    border: 0 !important; border-radius: .875rem !important;
    padding: 1rem 1.25rem !important; margin: 0 0 .75rem !important;
    font-family: inherit !important; font-size: .875rem !important;
    line-height: 1.7 !important; list-style: none !important;
    box-shadow: 0 1px 2px rgba(61, 157, 67, .05) !important;
}
body.baraa-auth-page .woocommerce-error   { background: #ffdad6 !important; color: #93000a !important; }
body.baraa-auth-page .woocommerce-info    { background: #D4E6F1 !important; color: #0E6655 !important; }
body.baraa-auth-page .woocommerce-message { background: #D4E6F1 !important; color: #0E6655 !important; }

/* ── Wrapper: everything horizontally centred ─────────────── */
.baraa-auth {
    width: 100% !important; max-width: 1040px !important;
    margin: 0 auto !important;
    display: flex !important; flex-direction: column !important;
    align-items: center !important; gap: 2rem !important;
}
.baraa-auth__head { max-width: 560px !important; text-align: center !important; }
.baraa-auth__glyph {
    width: 64px !important; height: 64px !important; margin: 0 auto .875rem !important;
    display: flex !important; align-items: center !important; justify-content: center !important;
    border-radius: 20px !important; color: #ffffff !important;
    background: linear-gradient(160deg, #117A65 0%, #16A085 70%) !important;
    box-shadow: 0 12px 28px rgba(61, 157, 67, .28) !important;
}
.baraa-auth__glyph .material-symbols-outlined { font-size: 32px !important; }
.baraa-auth__title {
    font-family: inherit !important; font-size: clamp(1.5rem, 3vw, 2rem) !important;
    font-weight: 800 !important; line-height: 1.3 !important;
    color: #131b2e !important; margin: 0 0 .5rem !important;
}
.baraa-auth__sub { font-size: .9375rem !important; line-height: 1.8 !important; color: #6d7a77 !important; margin: 0 !important; }

/* ── Cards ────────────────────────────────────────────────── */
.baraa-auth__grid {
    width: 100% !important; display: grid !important; gap: 1.5rem !important;
    grid-template-columns: minmax(0, 1fr) !important; justify-items: center !important;
}
.baraa-auth__card {
    width: 100% !important; max-width: 480px !important;
    background: #ffffff !important; border: 1px solid #dde8e6 !important;
    border-radius: 1.25rem !important; padding: 1.75rem !important;
    text-align: start !important;
    box-shadow: 0 1px 2px rgba(61, 157, 67, .05), 0 10px 30px rgba(61, 157, 67, .06) !important;
}
.baraa-auth__card--accent {
    border-color: #b6e3dd !important;
    box-shadow: 0 1px 2px rgba(61, 157, 67, .05), 0 14px 34px rgba(61, 157, 67, .10) !important;
}
.baraa-auth__card-title {
    display: flex !important; align-items: center !important; gap: .5rem !important;
    font-family: inherit !important; font-size: 1.125rem !important;
    font-weight: 700 !important; color: #131b2e !important; margin: 0 0 .375rem !important;
}
.baraa-auth__card-title .material-symbols-outlined { font-size: 22px !important; color: #16A085 !important; }
.baraa-auth__card-hint { font-size: .8125rem !important; line-height: 1.8 !important; color: #6d7a77 !important; margin: 0 0 1.25rem !important; }

/* ── Fields: one consistent look ──────────────────────────── */
.baraa-auth form.woocommerce-form {
    margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: none !important;
    background: transparent !important; border: 0 !important; box-shadow: none !important;
}
.baraa-auth form .form-row {
    display: block !important; float: none !important;
    width: 100% !important; margin: 0 0 .875rem !important; padding: 0 !important;
}
.baraa-auth form .form-row label {
    display: block !important; margin: 0 0 .375rem !important;
    font-family: inherit !important; font-size: .8125rem !important;
    font-weight: 600 !important; color: #3d4947 !important; line-height: 1.6 !important;
}
.baraa-auth form .form-row .required { color: #ba1a1a !important; text-decoration: none !important; }
.baraa-auth form .woocommerce-input-wrapper { display: block !important; width: 100% !important; }
.baraa-auth__optional { color: #6d7a77 !important; font-size: .75rem !important; font-weight: 500 !important; }
.baraa-auth form .form-row input.input-text {
    width: 100% !important; min-height: 46px !important; box-sizing: border-box !important;
    background: #f4f3fa !important; border: 1px solid #bec9c6 !important;
    border-radius: .625rem !important; color: #131b2e !important;
    font-family: inherit !important; font-size: .875rem !important;
    line-height: 1.4 !important; padding: .625rem .875rem !important;
    transition: border-color .2s ease, box-shadow .2s ease, background .2s ease !important;
}
.baraa-auth form .form-row input.input-text:focus {
    background: #ffffff !important; border-color: #16A085 !important;
    outline: none !important; box-shadow: 0 0 0 3px rgba(61, 157, 67, .15) !important;
}
.baraa-auth form .baraa-auth__row {
    display: grid !important; grid-template-columns: repeat(2, minmax(0, 1fr)) !important; gap: 0 .875rem !important;
}
@media (max-width: 520px) {
    .baraa-auth form .baraa-auth__row { grid-template-columns: minmax(0, 1fr) !important; }
}

/* ── Submit row ───────────────────────────────────────────── */
.baraa-auth form .baraa-auth__actions {
    display: flex !important; align-items: center !important; justify-content: space-between !important;
    flex-wrap: wrap !important; gap: .75rem !important; margin: 1.25rem 0 0 !important;
}
.baraa-auth form .woocommerce-form-login__rememberme {
    display: inline-flex !important; align-items: center !important; gap: .5rem !important;
    margin: 0 !important; cursor: pointer !important;
    font-size: .8125rem !important; font-weight: 600 !important; color: #3d4947 !important;
}
.baraa-auth form .woocommerce-form-login__rememberme input[type="checkbox"] {
    width: 16px !important; height: 16px !important; margin: 0 !important; accent-color: #16A085 !important;
}
.baraa-auth form button.button {
    min-height: 46px !important; padding: 0 1.5rem !important;
    border: 0 !important; border-radius: .625rem !important;
    background: #16A085 !important; color: #ffffff !important;
    font-family: inherit !important; font-size: .875rem !important;
    font-weight: 700 !important; line-height: 46px !important; cursor: pointer !important;
    transition: background .2s ease !important;
}
.baraa-auth form button.button:hover { background: #0E6655 !important; }
.baraa-auth__card--accent form button.button { width: 100% !important; }
.baraa-auth .woocommerce-LostPassword { margin: .875rem 0 0 !important; text-align: center !important; }
.baraa-auth .woocommerce-LostPassword a { color: #16A085 !important; font-size: .8125rem !important; font-weight: 600 !important; }

/* ── Footer note ──────────────────────────────────────────── */
.baraa-auth__note {
    display: flex !important; align-items: center !important; justify-content: center !important;
    flex-wrap: wrap !important; gap: .5rem !important; text-align: center !important;
    max-width: 720px !important; margin: 0 auto !important;
    font-size: .8125rem !important; line-height: 1.9 !important; color: #6d7a77 !important;
}
.baraa-auth__note .material-symbols-outlined { font-size: 18px !important; color: #16A085 !important; }
.baraa-auth__note a { color: #16A085 !important; font-weight: 700 !important; }

/* ── Login ↔ register switch link ─────────────────────────── */
.baraa-auth__switch {
    text-align: center !important; margin: 0 !important;
    font-size: .875rem !important; line-height: 1.9 !important; color: #6d7a77 !important;
}
.baraa-auth__switch a { color: #16A085 !important; font-weight: 700 !important; }

/* ── Dark mode ────────────────────────────────────────────── */
html.dark .baraa-auth__title { color: #dff0ed !important; }
html.dark .baraa-auth__sub,
html.dark .baraa-auth__card-hint,
html.dark .baraa-auth__note { color: #8db5b0 !important; }
html.dark .baraa-auth__card {
    background: #132220 !important; border-color: rgba(80, 130, 125, .35) !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, .35), 0 14px 34px rgba(0, 0, 0, .38) !important;
}
html.dark .baraa-auth__card-title { color: #dff0ed !important; }
html.dark .baraa-auth__card-title .material-symbols-outlined { color: #5ecfc2 !important; }
html.dark .baraa-auth form .form-row label { color: #cfe6e2 !important; }
html.dark .baraa-auth__optional { color: #8db5b0 !important; }
html.dark .baraa-auth form .form-row input.input-text {
    background: #0f1a19 !important; border-color: rgba(80, 130, 125, .45) !important; color: #dff0ed !important;
}
html.dark .baraa-auth form .form-row input.input-text:focus {
    background: #132220 !important; border-color: #5ecfc2 !important;
    box-shadow: 0 0 0 3px rgba(94, 207, 194, .18) !important;
}
html.dark .baraa-auth form .woocommerce-form-login__rememberme { color: #cfe6e2 !important; }
html.dark .baraa-auth form button.button { background: #5ecfc2 !important; color: #00201d !important; }
html.dark .baraa-auth form button.button:hover { background: #89f5e7 !important; }
html.dark .baraa-auth .woocommerce-LostPassword a,
html.dark .baraa-auth__note a,
html.dark .baraa-auth__switch a { color: #5ecfc2 !important; }
html.dark .baraa-auth__note .material-symbols-outlined { color: #5ecfc2 !important; }
html.dark .baraa-auth__switch { color: #8db5b0 !important; }
html.dark body.baraa-auth-page .woocommerce-error   { background: #2d1216 !important; color: #ffb3ab !important; }
html.dark body.baraa-auth-page .woocommerce-info    { background: #1a2e2c !important; color: #cfe6e2 !important; }
html.dark body.baraa-auth-page .woocommerce-message { background: #0d2825 !important; color: #89f5e7 !important; }
</style>
    <?php
}
add_action('wp_head', 'baraa_pharmacy_account_style_overrides', 102);
add_action('wp_head', 'baraa_pharmacy_auth_screen_styles', 102);

/* ═══════════════════════════════════════════════════════════
   REGISTRATION — its own page

   تسجيل الدخول لوحده في صفحة حسابي، وإنشاء الحساب في صفحة
   مستقلة فيها الشورت كود [baraa_register].
   ═══════════════════════════════════════════════════════════ */

/**
 * The page that hosts the registration form.
 *
 * @return int Page ID, or 0 while it does not exist yet.
 */
function baraa_pharmacy_register_page_id()
{
    static $page_id = null;

    if (null !== $page_id) {
        return $page_id;
    }

    $stored = (int) get_option('baraa_register_page_id');

    if ($stored && 'page' === get_post_type($stored) && 'publish' === get_post_status($stored)) {
        return $page_id = $stored;
    }

    $page = get_page_by_path('register');

    if ($page) {
        update_option('baraa_register_page_id', (int) $page->ID, true);
        return $page_id = (int) $page->ID;
    }

    return $page_id = 0;
}

/**
 * Create the registration page once, so the form has a home of its own.
 */
function baraa_pharmacy_maybe_create_register_page()
{
    if (baraa_pharmacy_register_page_id()) {
        return;
    }

    $page_id = wp_insert_post(array(
        'post_title'   => baraa_pharmacy_text('إنشاء حساب جديد', 'Create account'),
        'post_name'    => 'register',
        'post_content' => '[baraa_register]',
        'post_status'  => 'publish',
        'post_type'    => 'page',
    ));

    if ($page_id && !is_wp_error($page_id)) {
        update_option('baraa_register_page_id', (int) $page_id, true);
    }
}
add_action('init', 'baraa_pharmacy_maybe_create_register_page');

/**
 * True while a logged-out visitor is on the login or registration screen.
 */
function baraa_pharmacy_is_auth_screen()
{
    static $is_screen = null;

    if (null !== $is_screen) {
        return $is_screen;
    }

    if (is_user_logged_in()) {
        return $is_screen = false;
    }

    if (function_exists('is_account_page') && is_account_page()) {
        return $is_screen = true;
    }

    $register_page = baraa_pharmacy_register_page_id();

    return $is_screen = ($register_page > 0 && is_page($register_page));
}

/**
 * Tag those two screens so the shared auth CSS applies to both.
 */
function baraa_pharmacy_auth_screen_body_class($classes)
{
    if (baraa_pharmacy_is_auth_screen()) {
        $classes[] = 'baraa-auth-page';
    }

    return $classes;
}
add_filter('body_class', 'baraa_pharmacy_auth_screen_body_class');

/**
 * [baraa_register] — the customer registration form, on its own page.
 *
 * WooCommerce's registration handler runs on `wp_loaded` from any page, so the
 * form keeps working here exactly as it does on the account page.
 */
function baraa_pharmacy_register_shortcode()
{
    if (is_user_logged_in()) {
        return '<p class="baraa-auth__note">'
            . esc_html(baraa_pharmacy_text('أنت مسجّل الدخول بالفعل.', 'You are already logged in.'))
            . ' <a href="' . esc_url(wc_get_page_permalink('myaccount')) . '">'
            . esc_html(baraa_pharmacy_text('اذهب إلى حسابي', 'Go to my account'))
            . '</a></p>';
    }

    ob_start();

    if (function_exists('wc_print_notices')) {
        wc_print_notices();
    }

    wc_get_template('myaccount/baraa-register-form.php');

    return ob_get_clean();
}
add_shortcode('baraa_register', 'baraa_pharmacy_register_shortcode');


/* ═══════════════════════════════════════════════════════════
   CUSTOMER ACCOUNT — remembered profile (cookies)

   بيانات العميل بتتسجّل مرة واحدة: الاسم، رقم الهاتف، البريد،
   العنوان والمدينة. لو أنشأ حساب تتخزّن على الحساب، ولو أكمل الطلب
   كزائر تتخزّن في كوكيز — وفي الحالتين صفحة الدفع بتعبّي بياناته
   تلقائيًا، والعنوان التاني يفضل فاضي لو حبّ يشحن لحد غيره.
   ═══════════════════════════════════════════════════════════ */

/**
 * Registration stays available on the account page and at checkout, and the
 * customer always picks their own password — the form never asks for a
 * separate WordPress username.
 */
function baraa_pharmacy_force_myaccount_registration()
{
    return 'yes';
}
add_filter('pre_option_woocommerce_enable_myaccount_registration', 'baraa_pharmacy_force_myaccount_registration');

function baraa_pharmacy_force_generated_username()
{
    return 'yes';
}
add_filter('pre_option_woocommerce_registration_generate_username', 'baraa_pharmacy_force_generated_username');

function baraa_pharmacy_force_manual_password()
{
    return 'no';
}
add_filter('pre_option_woocommerce_registration_generate_password', 'baraa_pharmacy_force_manual_password');

/**
 * Disable guest checkout — customer must have an account and be logged in to purchase.
 */
function baraa_pharmacy_force_disable_guest_checkout()
{
    return 'no';
}
add_filter('pre_option_woocommerce_enable_guest_checkout', 'baraa_pharmacy_force_disable_guest_checkout');

/**
 * Require login before purchasing / proceeding to checkout.
 * Unauthenticated visitors who attempt to access checkout directly are redirected to
 * the login/register page with redirect_to pointing back to the cart page.
 */
function baraa_pharmacy_force_login_before_checkout()
{
    if (is_checkout() && !is_user_logged_in() && !is_order_received_page()) {
        $cart_url  = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
        $login_url = add_query_arg('redirect_to', urlencode($cart_url), wc_get_page_permalink('myaccount'));
        if (function_exists('wc_add_notice')) {
            wc_add_notice(__('يُرجى تسجيل الدخول أو إنشاء حساب جديد أولاً لمتابعة الشراء.', 'baraa-pharmacy'), 'notice');
        }
        wp_safe_redirect($login_url);
        exit;
    }
}
add_action('template_redirect', 'baraa_pharmacy_force_login_before_checkout');

/**
 * After successful login or registration, redirect the customer back to the cart
 * so they can proceed with their purchase.
 */
function baraa_pharmacy_auth_redirect($redirect, $user = null)
{
    $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');

    // 1. Check if redirect_to or redirect parameter was provided
    $target = '';
    if (!empty($_REQUEST['redirect_to'])) {
        $target = esc_url_raw(wp_unslash($_REQUEST['redirect_to']));
    } elseif (!empty($_REQUEST['redirect'])) {
        $target = esc_url_raw(wp_unslash($_REQUEST['redirect']));
    }

    if (!empty($target)) {
        return wp_validate_redirect($target, $cart_url);
    }

    // 2. If the user has items in their cart, send them directly to cart!
    if (function_exists('WC') && WC()->cart && !WC()->cart->is_empty()) {
        return $cart_url;
    }

    return $redirect;
}
add_filter('woocommerce_login_redirect', 'baraa_pharmacy_auth_redirect', 99, 2);
add_filter('woocommerce_registration_redirect', 'baraa_pharmacy_auth_redirect', 99, 2);

/**
 * The profile keys we remember, in a fixed order.
 */
function baraa_pharmacy_profile_fields()
{
    return array('first_name', 'last_name', 'phone', 'email', 'address', 'city');
}

/**
 * The profile stored in the visitor's cookie, or an empty array.
 *
 * @return array<string,string>
 */
function baraa_pharmacy_remembered_profile()
{
    $profile = array();
    $name    = 'baraa_customer_profile';

    if (empty($_COOKIE[$name]) || !is_string($_COOKIE[$name])) {
        return $profile;
    }

    // base64 keeps the value cookie-safe; rawurldecode() covers the transport
    // encoding whether or not PHP already decoded it for us.
    $json = base64_decode(rawurldecode($_COOKIE[$name]), true);

    if (false === $json) {
        return $profile;
    }

    $data = json_decode($json, true);

    if (!is_array($data)) {
        return $profile;
    }

    foreach (baraa_pharmacy_profile_fields() as $key) {
        if (isset($data[$key]) && is_scalar($data[$key]) && '' !== (string) $data[$key]) {
            $profile[$key] = (string) $data[$key];
        }
    }

    return $profile;
}

/**
 * Store a profile in the cookie so the next visit starts pre-filled.
 */
function baraa_pharmacy_remember_profile(array $profile)
{
    $clean = array();

    foreach (baraa_pharmacy_profile_fields() as $key) {
        if (!isset($profile[$key]) || !is_scalar($profile[$key])) {
            continue;
        }

        $value = sanitize_text_field((string) $profile[$key]);

        if ('' !== $value) {
            $clean[$key] = $value;
        }
    }

    if (empty($clean)) {
        return;
    }

    $value = rawurlencode(base64_encode((string) wp_json_encode($clean)));

    // Browsers drop cookies beyond ~4 KB — stay well below that.
    if (strlen($value) > 3800) {
        return;
    }

    $name = 'baraa_customer_profile';

    setcookie(
        $name,
        $value,
        time() + 90 * DAY_IN_SECONDS,
        (defined('COOKIEPATH') && COOKIEPATH) ? COOKIEPATH : '/',
        (defined('COOKIE_DOMAIN') && COOKIE_DOMAIN) ? COOKIE_DOMAIN : '',
        is_ssl(),
        true
    );

    $_COOKIE[$name] = $value;
}

/**
 * The extra registration boxes, read from the account form.
 */
function baraa_pharmacy_registration_profile()
{
    $map     = array(
        'first_name' => 'baraa_first_name',
        'last_name'  => 'baraa_last_name',
        'phone'      => 'baraa_phone',
        'address'    => 'baraa_address',
        'city'       => 'baraa_city',
    );
    $profile = array();

    foreach ($map as $key => $field) {
        $profile[$key] = (isset($_POST[$field]) && is_string($_POST[$field]))
            ? sanitize_text_field(wp_unslash($_POST[$field]))
            : '';
    }

    return $profile;
}

/**
 * Require the details a pharmacy delivery needs. Only runs for the account
 * page's own form, which posts the `baraa_account_form` marker — the checkout
 * registration keeps WooCommerce's validation untouched.
 */
function baraa_pharmacy_validate_registration($errors, $username, $password, $email)
{
    if (empty($_POST['baraa_account_form'])) {
        return $errors;
    }

    $labels = array(
        'baraa_first_name' => baraa_pharmacy_text('الاسم الأول', 'First name'),
        'baraa_last_name'  => baraa_pharmacy_text('اسم العائلة', 'Last name'),
        'baraa_phone'      => baraa_pharmacy_text('رقم الهاتف', 'Phone'),
    );

    foreach ($labels as $field => $label) {
        if (empty($_POST[$field]) || '' === trim((string) wp_unslash($_POST[$field]))) {
            $errors->add(
                $field,
                sprintf(baraa_pharmacy_text('حقل «%s» مطلوب.', 'The %s field is required.'), $label)
            );
        }
    }

    if (!empty($_POST['baraa_phone'])) {
        $digits = preg_replace('/\D+/', '', (string) wp_unslash($_POST['baraa_phone']));

        if (strlen((string) $digits) < 8) {
            $errors->add('baraa_phone_invalid', baraa_pharmacy_text('رقم الهاتف غير صحيح.', 'Please enter a valid phone number.'));
        }
    }

    return $errors;
}
add_filter('woocommerce_process_registration_errors', 'baraa_pharmacy_validate_registration', 10, 4);

/**
 * Copy the registration details onto the brand-new customer: the address the
 * checkout reads from, plus the cookie that covers logged-out visitors.
 */
function baraa_pharmacy_created_customer($customer_id, $new_customer_data = array(), $password_generated = false)
{
    if (!class_exists('WC_Customer')) {
        return;
    }

    $posted = baraa_pharmacy_registration_profile();

    $first_name = '' !== $posted['first_name']
        ? $posted['first_name']
        : (isset($new_customer_data['first_name']) ? sanitize_text_field((string) $new_customer_data['first_name']) : '');

    $last_name = '' !== $posted['last_name']
        ? $posted['last_name']
        : (isset($new_customer_data['last_name']) ? sanitize_text_field((string) $new_customer_data['last_name']) : '');

    $email = isset($new_customer_data['user_email']) ? sanitize_email((string) $new_customer_data['user_email']) : '';

    if ('' === $email) {
        $user  = get_userdata($customer_id);
        $email = $user ? sanitize_email($user->user_email) : '';
    }

    $customer = new WC_Customer($customer_id);

    if ('' !== $first_name) {
        $customer->set_first_name($first_name);
        $customer->set_billing_first_name($first_name);
    }

    if ('' !== $last_name) {
        $customer->set_last_name($last_name);
        $customer->set_billing_last_name($last_name);
    }

    if ('' !== $posted['phone']) {
        $customer->set_billing_phone($posted['phone']);
    }

    if ('' !== $posted['address']) {
        $customer->set_billing_address_1($posted['address']);
    }

    if ('' !== $posted['city']) {
        $customer->set_billing_city($posted['city']);
    }

    if ('' !== $email) {
        $customer->set_billing_email($email);
    }

    // The store only delivers inside Egypt.
    $customer->set_billing_country('EG');

    $customer->save();

    baraa_pharmacy_remember_profile(array(
        'first_name' => $first_name,
        'last_name'  => $last_name,
        'phone'      => $posted['phone'],
        'email'      => $email,
        'address'    => $posted['address'],
        'city'       => $posted['city'],
    ));
}
add_action('woocommerce_created_customer', 'baraa_pharmacy_created_customer', 10, 3);

/**
 * Pre-fill the checkout's billing fields from the remembered profile.
 *
 * Logged-in customers already get theirs from their account, so this only
 * covers visitors. Shipping fields are deliberately left out: ticking
 * "ship to a different address" must start from an empty form.
 */
function baraa_pharmacy_prefill_checkout_field($value, $input)
{
    if (null !== $value || is_user_logged_in()) {
        return $value;
    }

    $map = array(
        'billing_first_name' => 'first_name',
        'billing_last_name'  => 'last_name',
        'billing_phone'      => 'phone',
        'billing_email'      => 'email',
        'billing_address_1'  => 'address',
        'billing_city'       => 'city',
    );

    if (!isset($map[$input])) {
        return $value;
    }

    $profile = baraa_pharmacy_remembered_profile();

    return isset($profile[$map[$input]]) ? $profile[$map[$input]] : $value;
}
add_filter('woocommerce_checkout_get_value', 'baraa_pharmacy_prefill_checkout_field', 10, 2);

/**
 * Refresh the cookie after a completed order, so the next order (as a guest or
 * after logging out) starts filled in with the same details.
 */
function baraa_pharmacy_remember_order_profile($order_id, $posted_data = array(), $order = null)
{
    if (!$order instanceof WC_Order) {
        $order = function_exists('wc_get_order') ? wc_get_order($order_id) : null;
    }

    if (!$order) {
        return;
    }

    baraa_pharmacy_remember_profile(array(
        'first_name' => $order->get_billing_first_name(),
        'last_name'  => $order->get_billing_last_name(),
        'phone'      => $order->get_billing_phone(),
        'email'      => $order->get_billing_email(),
        'address'    => $order->get_billing_address_1(),
        'city'       => $order->get_billing_city(),
    ));
}
add_action('woocommerce_checkout_order_processed', 'baraa_pharmacy_remember_order_profile', 10, 3);

/**
 * Translate the COD gateway's saved description/instructions.
 *
 * These strings live in the gateway settings option, so the gettext filter
 * never reaches them — the thank-you page prints them verbatim.
 */
function baraa_pharmacy_arabic_cod_settings($value)
{
    if ((is_admin() && !wp_doing_ajax()) || baraa_pharmacy_is_english_visitor()) {
        return $value;
    }

    if (is_array($value)) {
        foreach (array('title', 'description', 'instructions') as $key) {
            if (!empty($value[$key]) && is_string($value[$key])) {
                $value[$key] = str_replace(
                    'Pay with cash upon delivery.',
                    'يتم الدفع نقدًا عند الاستلام.',
                    $value[$key]
                );
            }
        }
    }

    return $value;
}
add_filter('option_woocommerce_cod_settings', 'baraa_pharmacy_arabic_cod_settings');

/* ═══════════════════════════════════════════════════════════
   CHECKOUT — Simplified fields + delivery map picker
   ═══════════════════════════════════════════════════════════ */

/**
 * Remove the "Have a coupon?" toggle from the checkout page only.
 * Coupons keep working on the cart page and through the coupon URL.
 */
function baraa_pharmacy_remove_checkout_coupon_form()
{
    remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);
}
add_action('init', 'baraa_pharmacy_remove_checkout_coupon_form', 20);

/**
 * Drop the billing fields we no longer need (state, postcode) and empty the
 * whole shipping set.
 *
 * Delivery specifics (name, phone, exact address) are captured by the
 * RouteMile card, so the «الشحن إلى عنوان مختلف؟» fields are never rendered.
 * Emptying the set — instead of just hiding it — also stops WooCommerce from
 * requiring them as soon as the box is ticked; it keeps copying the billing
 * address onto the order as it does today.
 *
 * The e-mail field is deliberately kept: it is what creates the customer's
 * account — and therefore their wallet — when the order is placed.
 */
function baraa_pharmacy_simplify_checkout_fields($fields)
{
    unset($fields['billing']['billing_state'], $fields['billing']['billing_postcode']);
    $fields['shipping'] = array();

    return $fields;
}
add_filter('woocommerce_checkout_fields', 'baraa_pharmacy_simplify_checkout_fields');

/**
 * Keep the submitted checkout data safe after the fields above are removed.
 * A fallback billing email is injected because payment gateways
 * (Kashier → CustomerEmail) rely on it being present.
 */
function baraa_pharmacy_default_checkout_data($data)
{
    foreach (array('billing_state', 'billing_postcode') as $key) {
        if (!isset($data[$key])) {
            $data[$key] = '';
        }
    }

    if (empty($data['billing_email'])) {
        $email = '';
        if (is_user_logged_in()) {
            $current = wp_get_current_user();
            $email   = $current ? $current->user_email : '';
        }
        $data['billing_email'] = $email ? $email : get_option('admin_email');
    }

    return $data;
}
add_filter('woocommerce_checkout_posted_data', 'baraa_pharmacy_default_checkout_data');

/**
 * Mirror the billing address onto the order's shipping address.
 *
 * The shipping field set is emptied on checkout (see
 * baraa_pharmacy_simplify_checkout_fields), which also empties the list
 * WooCommerce walks when it copies the billing details over — that would
 * leave every order without a shipping address. Restore the mirror here.
 */
function baraa_pharmacy_mirror_billing_to_shipping($data)
{
    $fields = array(
        'first_name',
        'last_name',
        'company',
        'phone',
        'address_1',
        'address_2',
        'city',
        'state',
        'postcode',
        'country',
    );

    foreach ($fields as $field) {
        $key = 'shipping_' . $field;

        if (empty($data[$key])) {
            $data[$key] = isset($data['billing_' . $field]) ? $data['billing_' . $field] : '';
        }
    }

    return $data;
}
add_filter('woocommerce_checkout_posted_data', 'baraa_pharmacy_mirror_billing_to_shipping', 20);

/**
 * Move the «الشحن إلى عنوان مختلف؟» block on checkout.
 *
 * RouteMile prints its delivery-details card inside the billing section, so
 * WooCommerce's own shipping block (checkout/form-shipping.php) used to land
 * far below the billing fields. It is taken off its default hook and printed
 * right above the RouteMile «رقم المنزل / الشقة / العمارة» field instead.
 */
function baraa_pharmacy_move_checkout_shipping_block()
{
    remove_action('woocommerce_checkout_shipping', array(WC()->checkout(), 'checkout_form_shipping'));
}
add_action('init', 'baraa_pharmacy_move_checkout_shipping_block', 20);

/**
 * Render the shipping block from the theme's checkout/form-shipping.php.
 */
function baraa_pharmacy_checkout_shipping_fields_html()
{
    if (!WC()->cart || !WC()->cart->needs_shipping_address()) {
        return '';
    }

    ob_start();
    wc_get_template('checkout/form-shipping.php', array('checkout' => WC()->checkout()));

    return (string) ob_get_clean();
}

/**
 * Print the shipping block just above the RouteMile address-details field.
 */
function baraa_pharmacy_inject_checkout_shipping_block($field, $key)
{
    if ('routew_address_details' !== $key || !is_checkout() || is_order_received_page()) {
        return $field;
    }

    return baraa_pharmacy_checkout_shipping_fields_html() . $field;
}
add_filter('woocommerce_form_field', 'baraa_pharmacy_inject_checkout_shipping_block', 10, 2);

/**
 * Enqueue the theme's JS (storefront interactions + cart stepper).
 * CSS is fully handled by Tailwind utility classes in templates.
 */
function baraa_pharmacy_enqueue_scripts()
{
    $script_path = get_template_directory() . '/assets/js/storefront.js';
    $version     = file_exists($script_path) ? (string) filemtime($script_path) : '1.0.0';

    wp_enqueue_script(
        'baraa-pharmacy-storefront',
        get_template_directory_uri() . '/assets/js/storefront.js',
        array(),
        $version,
        true
    );
}
add_action('wp_enqueue_scripts', 'baraa_pharmacy_enqueue_scripts');

function baraa_pharmacy_enqueue_cart_script()
{
    if (!is_cart()) {
        return;
    }

    $script_path = get_template_directory() . '/assets/js/cart.js';
    $version     = file_exists($script_path) ? (string) filemtime($script_path) : '1.0.0';

    wp_enqueue_script(
        'baraa-pharmacy-cart',
        get_template_directory_uri() . '/assets/js/cart.js',
        array('jquery'),
        $version,
        true
    );
}
add_action('wp_enqueue_scripts', 'baraa_pharmacy_enqueue_cart_script', 20);

/**
 * Keep the header cart counter and total in sync when WooCommerce refreshes
 * its cart fragments over AJAX (add-to-cart without a full page reload).
 */
function baraa_pharmacy_cart_fragments($fragments)
{
    if (!function_exists('WC') || !WC()->cart) {
        return $fragments;
    }

    $count = WC()->cart->get_cart_contents_count();

    $fragments['#baraa-cart-count'] = sprintf(
        '<span id="baraa-cart-count" class="absolute -top-1.5 -right-1.5 bg-error text-on-error text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold baraa-cart-count"%s>%s</span>',
        $count > 0 ? '' : ' style="display:none"',
        esc_html((string) $count)
    );

    $total = wp_strip_all_tags(WC()->cart->get_cart_total());

    $fragments['#baraa-cart-total'] = sprintf(
        '<span id="baraa-cart-total" class="text-xs font-bold leading-tight baraa-cart-total hidden sm:block"%s>%s</span>',
        $total !== '' ? '' : ' style="display:none"',
        esc_html($total)
    );

    return $fragments;
}
add_filter('woocommerce_add_to_cart_fragments', 'baraa_pharmacy_cart_fragments');

/**
 * Load WooCommerce's AJAX add-to-cart script where product cards are rendered
 * (home page included) so adding to the cart never reloads the page.
 */
function baraa_pharmacy_enqueue_ajax_add_to_cart()
{
    if (!function_exists('wc_get_page_permalink')) {
        return;
    }

    if (is_front_page() || is_home() || (function_exists('is_woocommerce') && is_woocommerce())) {
        wp_enqueue_script('wc-add-to-cart');
    }
}
add_action('wp_enqueue_scripts', 'baraa_pharmacy_enqueue_ajax_add_to_cart', 20);

/**
 * Hide WooCommerce's success and info notices across the storefront
 * (e.g. "… removed. Undo?"). Checkout validation errors are kept.
 */
function baraa_pharmacy_visible_notice_types($types)
{
    return array('error');
}
add_filter('woocommerce_notice_types', 'baraa_pharmacy_visible_notice_types');

/**
 * Block the checkout page while the cart contains a prescription (Rx) product.
 * Those orders can only be completed by contacting the pharmacy.
 */
function baraa_pharmacy_block_rx_checkout()
{
    if (is_admin() || !function_exists('WC') || !function_exists('is_checkout')) {
        return;
    }

    if (!is_checkout() || is_wc_endpoint_url('order-received') || !WC()->cart || WC()->cart->is_empty()) {
        return;
    }

    foreach (WC()->cart->get_cart() as $cart_item) {
        $product_id = !empty($cart_item['product_id']) ? $cart_item['product_id'] : 0;
        if ($product_id && 'yes' === get_post_meta($product_id, '_requires_prescription', true)) {
            wc_add_notice(
                __('سلتك تحتوي على دواء يتطلب روشتة طبية. يرجى التواصل مع الصيدلي عبر واتساب لإتمام الطلب.', 'baraa-pharmacy'),
                'error'
            );
            wp_safe_redirect(wc_get_cart_url());
            exit;
        }
    }
}
add_action('template_redirect', 'baraa_pharmacy_block_rx_checkout');

function baraa_pharmacy_render_classic_cart_block($block_content, $block)
{
    if (!is_cart() || !isset($block['blockName']) || 'woocommerce/cart' !== $block['blockName']) {
        return $block_content;
    }

    return do_shortcode('[woocommerce_cart]');
}
add_filter('render_block', 'baraa_pharmacy_render_classic_cart_block', 10, 2);

/**
 * Render the classic checkout form instead of the WooCommerce Checkout block.
 *
 * The block checkout ignores our custom templates and cannot be themed with the
 * MD3 / Tailwind design system. Swapping it for the [woocommerce_checkout]
 * shortcode lets us use woocommerce/checkout/form-checkout.php (two-column
 * layout) and keeps every WooCommerce hook so payment gateways keep working.
 */
function baraa_pharmacy_render_classic_checkout_block($block_content, $block)
{
    if (!is_checkout() || !isset($block['blockName']) || 'woocommerce/checkout' !== $block['blockName']) {
        return $block_content;
    }

    return do_shortcode('[woocommerce_checkout]');
}
add_filter('render_block', 'baraa_pharmacy_render_classic_checkout_block', 10, 2);

/**
 * Keep a single delivery-location map on the classic checkout.
 *
 * The checkout page stores the WooCommerce Checkout block but we render it
 * with the [woocommerce_checkout] shortcode (see the override above). RouteMile
 * therefore runs BOTH of its picker paths at once: the blocks-checkout content
 * filter (which still sees the block in the raw page content) and the classic
 * billing-form hook. That printed the picker — and its #routew-map /
 * #routew-location-search-input ids — twice, so the map JS only booted on the
 * first copy. Dropping the blocks-injected copy leaves the billing-form picker,
 * which sits inside the classic layout where it belongs.
 */
function baraa_pharmacy_routew_single_map()
{
    if (!function_exists('is_checkout') || !is_checkout() || !class_exists('ROUTEW_Blocks_Checkout')) {
        return;
    }

    global $wp_filter;

    foreach (array('the_content', 'render_block_core/post-content') as $hook) {
        if (empty($wp_filter[$hook])) {
            continue;
        }

        foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                if (is_array($callback['function']) && $callback['function'][0] instanceof ROUTEW_Blocks_Checkout) {
                    remove_filter($hook, $callback['function'], $priority);
                }
            }
        }
    }
}
add_action('wp', 'baraa_pharmacy_routew_single_map', 20);

/**
 * Repair the Kashier payment icons on the classic checkout.
 *
 * Kashier_Gateway::payment_icons() appends ".svg" to icon filenames that already
 * contain the extension, so "credit-card.svg" resolves to "credit-card.svg.svg"
 * and every card icon 404s. Rebuilding the markup here fixes the plugin bug from
 * the theme instead of forking the gateway.
 */
function baraa_pharmacy_fix_kashier_payment_icons($icons)
{
    $fixed = array();

    foreach ($icons as $icon => $markup) {
        $file = preg_replace('/\.svg$/i', '', $icon);

        $fixed[$file] = '<img src="' . WC_KASHIER_PLUGIN_URL . '/assets/images/' . $file . '.svg" class="kashier-' . $file . '-icon kashier-icon" alt="' . $file . '" />';
    }

    return $fixed;
}
add_filter('wc_kashier_payment_icons', 'baraa_pharmacy_fix_kashier_payment_icons');

/**
 * Drop the Kashier Blocks payment scripts from the classic cart & checkout.
 *
 * The cart and checkout pages still contain the WooCommerce Blocks, so
 * WooCommerce Blocks enqueues every registered Kashier block payment script —
 * including the "contact now" gateway, whose script filename contains a space
 * (404) and whose localized JS object ("wc_contact now_params") is invalid
 * syntax. These scripts only matter to the block cart/checkout, and both pages
 * are rendered with the classic templates here, so filter them out of the
 * print queue on the whole frontend.
 */
function baraa_pharmacy_filter_kashier_block_scripts($handles)
{
    if (is_admin()) {
        return $handles;
    }

    $registered = wp_scripts()->registered;

    foreach ($handles as $key => $handle) {
        $src = isset($registered[$handle]) ? (string) $registered[$handle]->src : '';

        if (false !== strpos($src, 'kashier-plugin-main') && false !== strpos($src, 'assets/js/blocks/')) {
            unset($handles[$key]);
        }
    }

    return $handles;
}
add_filter('print_scripts_array', 'baraa_pharmacy_filter_kashier_block_scripts');

/**
 * Arabic storefront wording for WooCommerce.
 *
 * The site runs on the default English WordPress locale (no Arabic language
 * pack installed) while the storefront content is Arabic, so core WooCommerce
 * labels would otherwise render in English on the cart and checkout pages.
 * This maps the customer-facing strings to Arabic on the frontend — including
 * WooCommerce's AJAX order-review refresh — without touching wp-admin screens.
 */
function baraa_pharmacy_arabic_woocommerce_strings($translated, $text, $domain)
{
    if (is_admin() && !wp_doing_ajax()) {
        return $translated;
    }

    if (baraa_pharmacy_is_english_visitor()) {
        return $translated;
    }

    if (!in_array($domain, array('woocommerce', 'default', 'woocommerce-gateway-kashier', 'routemile-for-woocommerce'), true)) {
        return $translated;
    }

    static $map = array(
        // Checkout sections
        'Billing details'                             => 'بيانات الفاتورة',
        'Additional information'                      => 'معلومات إضافية',
        'Ship to a different address?'                => 'الشحن إلى عنوان مختلف؟',
        'Create an account?'                          => 'إنشاء حساب؟ (بياناتك هتتحفظ + خصم المحفظة)',
        'Order notes'                                 => 'ملاحظات الطلب',
        'Order notes (optional)'                      => 'ملاحظات الطلب (اختياري)',
        'Notes about your order, e.g. special notes for delivery.' => 'ملاحظات حول طلبك، مثل تعليمات خاصة للتوصيل.',
        // Order review
        'Product'                                     => 'المنتج',
        'Subtotal'                                    => 'المجموع الفرعي',
        'Total'                                       => 'الإجمالي',
        'Order summary'                               => 'ملخص الطلب',
        'Your order'                                  => 'طلبك',
        // Shipping
        'Shipment'                                    => 'الشحنة',
        'Free shipping'                               => 'شحن مجاني',
        'Free!'                                       => 'مجانًا',
        'Change address'                              => 'تغيير العنوان',
        'Calculate shipping'                          => 'حساب الشحن',
        'Update totals'                               => 'تحديث الإجمالي',
        'Update cart'                                 => 'تحديث السلة',
        'Update'                                      => 'تحديث',
        // Coupon / login toggles
        'Have a coupon?'                              => 'لديك كود خصم؟',
        'Click here to enter your code'               => 'اضغط هنا لإدخال الكود',
        'Coupon code'                                 => 'كود الخصم',
        'Coupon:'                                     => 'الكوبون:',
        'Apply coupon'                                => 'تطبيق الخصم',
        'Apply'                                       => 'تطبيق',
        'Returning customer?'                         => 'عميل مسجّل مسبقًا؟',
        'Click here to login'                         => 'اضغط هنا لتسجيل الدخول',
        // Buttons / navigation
        'Place order'                                 => 'تأكيد الطلب',
        'Proceed to checkout'                         => 'متابعة لإتمام الطلب',
        'Continue to checkout'                        => 'متابعة لإتمام الطلب',
        'Proceed to Checkout'                         => 'متابعة لإتمام الطلب',
        'Return to shop'                              => 'العودة للمتجر',
        'Continue shopping'                           => 'متابعة التسوق',
        // Cart
        'Cart'                                        => 'سلة المشتريات',
        'Your cart is currently empty.'               => 'سلة مشترياتك فارغة حاليًا.',
        'Remove this item'                            => 'إزالة هذا المنتج',
        'Product name'                                => 'اسم المنتج',
        'Price'                                       => 'السعر',
        'Quantity'                                    => 'الكمية',
        'Remove'                                      => 'إزالة',
        // Form fields
        'First name'                                  => 'الاسم الأول',
        'Last name'                                   => 'اسم العائلة',
        'Company name'                                => 'اسم الشركة',
        'Country / Region'                            => 'الدولة / المنطقة',
        'Street address'                              => 'العنوان',
        'Apartment, suite, unit, etc. (optional)'     => 'الشقة، الوحدة، الطابق (اختياري)',
        'Town / City'                                 => 'المدينة / المركز',
        'State / County'                              => 'المحافظة',
        'State'                                       => 'المحافظة',
        'Postcode / ZIP'                              => 'الرمز البريدي',
        'Phone'                                       => 'رقم الهاتف',
        'Email address'                               => 'البريد الإلكتروني',
        'Optional'                                    => 'اختياري',
        // Privacy & misc
        'privacy policy'                              => 'سياسة الخصوصية',
        'Checkout'                                    => 'إتمام الطلب',
        'Order'                                       => 'الطلب',
        'Order details'                               => 'تفاصيل الطلب',
        'Order received'                              => 'تم استلام طلبك',
        // Kashier payment gateway
        'Pay with your Credit Card.'                  => 'ادفع بأمان باستخدام بطاقتك الائتمانية.',
        'Pay with cash upon delivery.'                => 'يتم الدفع نقدًا عند الاستلام.',
        'TEST MODE ENABLED'                           => 'وضع الاختبار مُفعّل',
        // Shop / archives
        'Home'                                        => 'الرئيسية',
        'Shop'                                        => 'المتجر',
        'Products'                                    => 'المنتجات',
        'Add to cart'                                 => 'أضف للسلة',
        'Read more'                                   => 'اقرأ المزيد',
        'Select options'                              => 'اختر الخيارات',
        'View products'                               => 'عرض المنتجات',
        'In stock'                                    => 'متوفر',
        'Out of stock'                                => 'غير متوفر',
        'Available on backorder'                      => 'متاح للطلب المسبق',
        'Sale!'                                       => 'خصم!',
        // Single product meta
        'SKU'                                         => 'رمز المنتج',
        'SKU:'                                        => 'رمز المنتج:',
        'Description'                                 => 'الوصف',
        'Related products'                            => 'منتجات ذات صلة',
        'You may also like&hellip;'                   => 'قد يعجبك أيضًا…',
        // Reviews
        'Reviews'                                     => 'التقييمات',
        'There are no reviews yet.'                   => 'لا توجد تقييمات حتى الآن.',
        'Be the first to review'                      => 'كن أول من يقيّم',
        'Be the first to review &ldquo;%s&rdquo;'     => 'كن أول من يقيّم "%s"',
        'Cancel reply'                                => 'إلغاء الرد',
        'Your email address will not be published.'   => 'لن يتم نشر عنوان بريدك الإلكتروني.',
        'Required fields are marked'                  => 'الحقول المطلوبة مُعلَّمة',
        'Required fields are marked %s'               => 'الحقول المطلوبة مُعلَّمة %s',
        'Your rating'                                 => 'تقييمك',
        'Rate&hellip;'                                => 'قيّم…',
        'Rate…'                                       => 'قيّم…',
        'Perfect'                                     => 'ممتاز',
        'Good'                                        => 'جيد',
        'Average'                                     => 'متوسط',
        'Not that bad'                                => 'مقبول',
        'Very poor'                                   => 'ضعيف جدًا',
        'Your review'                                 => 'مراجعتك',
        'Name'                                        => 'الاسم',
        'Email'                                       => 'البريد الإلكتروني',
        'Save my name, email, and website in this browser for the next time I comment.' => 'احفظ اسمي وبريدي الإلكتروني وموقعي في هذا المتصفح للمرة القادمة.',
        // Search results
        'Search Results for &#8220;%s&#8221;'         => 'نتائج البحث عن «%s»',
        'No products were found matching your selection.' => 'لا توجد منتجات مطابقة لاختيارك.',
        // My account
        'My account'                                  => 'حسابي',
        'Dashboard'                                   => 'لوحة التحكم',
        'Orders'                                      => 'الطلبات',
        'Downloads'                                   => 'التنزيلات',
        'Addresses'                                   => 'العناوين',
        'Account details'                             => 'تفاصيل الحساب',
        'Logout'                                      => 'تسجيل الخروج',
        'Log out'                                     => 'تسجيل الخروج',
        'Username or email address'                   => 'اسم المستخدم أو البريد الإلكتروني',
        'Password'                                    => 'كلمة المرور',
        'Remember me'                                 => 'تذكرني',
        'Log in'                                      => 'تسجيل الدخول',
        'Login'                                       => 'تسجيل الدخول',
        'Lost your password?'                         => 'نسيت كلمة المرور؟',
        'Register'                                    => 'تسجيل حساب جديد',
        'Required'                                    => 'مطلوب',
        // Cart / shipping extras
        'Cart totals'                                 => 'إجمالي السلة',
        'Shipping'                                    => 'الشحن',
        'Shipping to %s.'                             => 'الشحن إلى %s.',
        'Shipping options will be updated during checkout.' => 'سيتم تحديث خيارات الشحن عند إتمام الطلب.',
        'Shipping costs are calculated during checkout.'    => 'يتم حساب تكاليف الشحن عند إتمام الطلب.',
        'Enter your address to view shipping options.'      => 'أدخل عنوانك لعرض خيارات الشحن.',
        'There are no shipping options available. Please ensure that your address has been entered correctly, or contact us if you need any help.' => 'لا توجد خيارات شحن متاحة. تأكد من صحة عنوانك أو تواصل معنا للمساعدة.',
        'No shipping options were found for %s.'      => 'لم يتم العثور على خيارات شحن لـ %s.',
        'Enter a different address'                   => 'أدخل عنوانًا آخر',
        'Remove %s from cart'                         => 'إزالة %s من السلة',
        'View cart'                                   => 'عرض السلة',
        // Cart shipping calculator
        'Country / region'                            => 'الدولة / المنطقة',
        'City:'                                       => 'المدينة:',
        'Postcode / ZIP:'                             => 'الرمز البريدي:',
        'Select a country / region&hellip;'           => 'اختر الدولة / المنطقة…',
        'Select an option&hellip;'                    => 'اختر…',
        'State / County'                              => 'المحافظة / المركز',
        // Order received
        'Thank you. Your order has been received.'    => 'شكرًا لك. تم استلام طلبك.',
        'Order number'                                => 'رقم الطلب',
        'Date'                                        => 'التاريخ',
        'Payment method'                              => 'طريقة الدفع',
        'Billing address'                             => 'عنوان الفاتورة',
        'Shipping address'                            => 'عنوان الشحن',
        // Kashier gateways (domain: woocommerce-gateway-kashier)
        'Card'                                        => 'بطاقة',
        'Mobile Wallet'                               => 'محفظة إلكترونية',
        // WooCommerce checkout footer / no-JS fallback
        'Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our %s.' => 'تُستخدم بياناتك الشخصية لمعالجة طلبك ودعم تجربتك عبر هذا الموقع ولأغراض أخرى موضحة في %s.',
        'Since your browser does not support JavaScript, or it is disabled, please ensure you click the %1$sUpdate Totals%2$s button before placing your order. You may be charged more than the amount stated above if you fail to do so.' => 'نظرًا لأن متصفحك لا يدعم JavaScript أو أنه معطّل، تأكد من الضغط على زر %1$sتحديث الإجمالي%2$s قبل تأكيد الطلب. قد يتم تحصيل مبلغ أكبر من المذكور أعلاه إذا لم تفعل ذلك.',
        // RouteMile delivery map (domain: routemile-for-woocommerce)
        'We are currently closed for deliveries. You can browse and fill your cart — ordering will be available as soon as we reopen.' => 'التوصيل متوقف حاليًا. يمكنك تصفح المنتجات وتعبئة سلتك — وسيتاح الطلب فور إعادة فتح المتجر.',
        'Where should we deliver?'                    => 'أين نوصل طلبك؟',
        'Search your area, use your location, or drag the delivery pin to set the exact drop point. The store marker never moves.' => 'ابحث عن منطقتك، أو استخدم موقعك الحالي، أو اسحب دبوس التوصيل لتحديد نقطة التسليم بالضبط. علامة المتجر لا تتحرك.',
        'Search area or landmark…'                    => 'ابحث عن المنطقة أو علامة مميزة…',
        'Locate me'                                   => 'حدِّد موقعي',
        'Delivery location map'                       => 'خريطة موقع التوصيل',
        'Delivering to'                               => 'التوصيل إلى',
        'Address details'                             => 'تفاصيل العنوان',
        'Help your rider find the exact door.'        => 'ساعد الطيار في الوصول إلى بابك بالضبط.',
        'House / Flat / Building No.'                 => 'رقم المنزل / الشقة / العمارة',
        'e.g., Flat 4B, House 25, Tower A, Block 2'   => 'مثال: شقة 4ب، منزل 25، برج أ، بلوك 2',
        'Nearby Landmark (optional)'                  => 'علامة مميزة قريبة (اختياري)',
        'e.g., Near City Mall, Opposite Park'         => 'مثال: بجوار سيتي مول، أمام الحديقة',
        'Delivery Instructions'                       => 'تعليمات التوصيل',
        'e.g., Ring the bell twice, Leave at the door, Call before arriving...' => 'مثال: اِرن الجرس مرتين، اتركه عند الباب، اتصل قبل الوصول...',
        // RouteMile checkout validation & zone messages (pharmacy wording, not "restaurant")
        'Sorry, we do not deliver to your location — it is %1$s km by road and we deliver within %2$s km of the restaurant.' => 'عذرًا، لا نوصّل إلى موقعك — المسافة %1$s كم بالطريق، وخدمة التوصيل متاحة حتى %2$s كم من الصيدلية.',
        'Sorry, we only deliver within %s km of the restaurant.' => 'عذرًا، نوصّل فقط داخل %s كم من الصيدلية.',
        'Sorry, we do not deliver to your location.'           => 'عذرًا، لا نوصّل إلى موقعك.',
        'Online ordering is currently unavailable — the delivery map is not configured. Please contact the store.' => 'الطلب غير متاح حاليًا — خريطة التوصيل غير مُعدّة. يُرجى التواصل مع الصيدلية.',
        'We are currently closed for deliveries. Please try again later.' => 'التوصيل متوقف حاليًا. يُرجى المحاولة لاحقًا.',
        'Please select your exact location on the map above to enable delivery.' => 'حدِّد موقعك بالضبط على الخريطة أعلاه لتفعيل التوصيل.',
        'Please select your exact location on the map. This ensures accurate delivery and helps our delivery agent find you.' => 'حدِّد موقعك بالضبط على الخريطة، لضمان دقة التوصيل ومساعدة مندوب التوصيل في الوصول إليك.',
        'Please enter your exact address (house / flat / building no.) so our delivery agent can find you.' => 'اكتب عنوانك بالتفصيل (رقم المنزل / الشقة / العمارة) حتى يستطيع مندوب التوصيل الوصول إليك.',
        'Invalid location coordinates detected. Please select your location again using the map.' => 'تم رصد إحداثيات موقع غير صحيحة. يُرجى تحديد موقعك مجددًا من الخريطة.',
        'Delivery service is not properly configured. Please contact support.' => 'خدمة التوصيل غير مُعدّة بشكل صحيح. يُرجى التواصل مع الدعم.',
        'Could not calculate delivery distance. Please try again.' => 'تعذّر حساب مسافة التوصيل. يُرجى المحاولة مرة أخرى.',
        'Please enter a valid phone number (7-15 digits) — your rider will call it on arrival.' => 'أدخل رقم هاتف صحيح (من 7 إلى 15 رقمًا) — سيتصل بك مندوب التوصيل عند الوصول.',
        'The minimum delivery order is %1$s — please add %2$s more to your cart.' => 'الحد الأدنى لطلب التوصيل هو %1$s — يُرجى إضافة %2$s إلى سلتك.',
        'Landmark'                                    => 'علامة مميزة',
    );

    return isset($map[$text]) ? $map[$text] : $translated;
}
add_filter('gettext', 'baraa_pharmacy_arabic_woocommerce_strings', 20, 3);

/**
 * RouteMile admin wording: this store is a pharmacy, not a restaurant.
 *
 * The plugin ships "Restaurant …" labels for its settings and notices
 * (Restaurant Address, Restaurant Coordinates, the receipts section…).
 * The storefront copy is already handled by the Arabic map above; this
 * rewrites the English strings on the wp-admin screens only, so the
 * dashboard reads "Pharmacy …" while the rest of the admin stays
 * untouched. Plurals are replaced first so "restaurants" never becomes
 * "pharmacys".
 */
function baraa_pharmacy_routemile_pharmacy_wording($translated, $text, $domain)
{
    if ('routemile-for-woocommerce' !== $domain) {
        return $translated;
    }

    if (!is_admin() || wp_doing_ajax()) {
        return $translated;
    }

    if (false === strpos($translated, 'estaurant')) {
        return $translated;
    }

    return str_replace(
        array('Restaurants', 'restaurants', 'Restaurant', 'restaurant'),
        array('Pharmacies', 'pharmacies', 'Pharmacy', 'pharmacy'),
        $translated
    );
}
add_filter('gettext', 'baraa_pharmacy_routemile_pharmacy_wording', 20, 3);

/**
 * Plural forms (`_n`) used by WooCommerce on the storefront — the plain
 * gettext filter cannot reach these, they go through ngettext.
 */
function baraa_pharmacy_arabic_woocommerce_plurals($translation, $single, $plural, $number, $domain)
{
    if (is_admin() && !wp_doing_ajax()) {
        return $translation;
    }

    if (baraa_pharmacy_is_english_visitor()) {
        return $translation;
    }

    if (!in_array($domain, array('woocommerce', 'default', 'woocommerce-gateway-kashier'), true)) {
        return $translation;
    }

    $is_one = (1 === (int) $number);

    if ('Category:' === $single) {
        return $is_one ? 'التصنيف:' : 'التصنيفات:';
    }

    if ('Tag:' === $single) {
        return $is_one ? 'الوسم:' : 'الوسوم:';
    }

    if ('%s customer review' === $single) {
        return $is_one ? '%s تقييم' : '%s تقييمات';
    }

    return $translation;
}
add_filter('ngettext', 'baraa_pharmacy_arabic_woocommerce_plurals', 20, 5);

/**
 * Contextual strings (`_x`) — e.g. the shipping package name emitted by
 * WooCommerce's shipping calculator. These go through gettext_with_context.
 */
function baraa_pharmacy_arabic_woocommerce_context_strings($translation, $text, $context, $domain)
{
    if (is_admin() && !wp_doing_ajax()) {
        return $translation;
    }

    if (baraa_pharmacy_is_english_visitor()) {
        return $translation;
    }

    if (!in_array($domain, array('woocommerce', 'default', 'woocommerce-gateway-kashier'), true)) {
        return $translation;
    }

    static $context_map = array(
        'shipping packages' => array('Shipment' => 'الشحنة'),
        'breadcrumb'        => array('Home' => 'الرئيسية'),
    );

    return isset($context_map[$context][$text]) ? $context_map[$context][$text] : $translation;
}
add_filter('gettext_with_context', 'baraa_pharmacy_arabic_woocommerce_context_strings', 20, 4);

/**
 * Payment gateway titles/descriptions are stored as plain DB option strings
 * (e.g. Kashier saves "Card" / "Mobile Wallet" from its settings default while
 * the store saved COD in Arabic), so they never pass through gettext.
 * Translate them on the storefront in whichever direction the visitor picked.
 */
function baraa_pharmacy_arabic_gateway_title($title, $id)
{
    if (is_admin() && !wp_doing_ajax()) {
        return $title;
    }

    static $map = array(
        'Card'            => 'بطاقة',
        'Mobile Wallet'   => 'محفظة إلكترونية',
        'Bank Installment' => 'تقسيط بنكي',
        'Fawry'           => 'فوري',
        'ValU'            => 'فاليو',
        'Souhoola'        => 'سهولة',
        'Aman'            => 'أمان',
        'Contact Now'     => 'تواصل معنا',
        'Cash on delivery' => 'الدفع عند الاستلام',
    );

    if (baraa_pharmacy_is_english_visitor()) {
        $reverse = array_flip($map);

        return isset($reverse[$title]) ? $reverse[$title] : $title;
    }

    return isset($map[$title]) ? $map[$title] : $title;
}
add_filter('woocommerce_gateway_title', 'baraa_pharmacy_arabic_gateway_title', 20, 2);

function baraa_pharmacy_arabic_gateway_description($description, $id)
{
    if (is_admin() && !wp_doing_ajax()) {
        return $description;
    }

    static $map = array(
        'Pay with your Credit Card.'   => 'ادفع بأمان باستخدام بطاقتك الائتمانية.',
        'Pay with your Mobile Wallet.' => 'ادفع باستخدام محفظتك الإلكترونية.',
        'Pay with cash upon delivery.' => 'يتم الدفع نقدًا عند الاستلام.',
        'Pay cash upon receiving your order from the delivery agent.' => 'ادفع نقدًا عند استلام طلبك من مندوب التوصيل.',
    );

    if (baraa_pharmacy_is_english_visitor()) {
        $reverse = array_flip($map);

        return isset($reverse[$description]) ? $reverse[$description] : $description;
    }

    return isset($map[$description]) ? $map[$description] : $description;
}
add_filter('woocommerce_gateway_description', 'baraa_pharmacy_arabic_gateway_description', 20, 2);

/**
 * The checkout privacy-policy notice is stored in the
 * `woocommerce_checkout_privacy_policy_text` option, so it bypasses gettext.
 * Replace it with the Arabic wording while keeping the [privacy_policy]
 * placeholder WooCommerce swaps for the linked page name.
 */
function baraa_pharmacy_arabic_privacy_policy_text($text, $type = '')
{
    if (is_admin() && !wp_doing_ajax()) {
        return $text;
    }

    if (baraa_pharmacy_is_english_visitor()) {
        return $text;
    }

    if ('checkout' === $type) {
        return 'تُستخدم بياناتك الشخصية لمعالجة طلبك ودعم تجربتك عبر هذا الموقع ولأغراض أخرى موضحة في [privacy_policy].';
    }

    return $text;
}
add_filter('woocommerce_get_privacy_policy_text', 'baraa_pharmacy_arabic_privacy_policy_text', 20, 2);

/**
 * WooCommerce creates its pages (Cart, Checkout, My account, Shop) with English
 * titles. The theme renders those titles as the page <h1> and in the <title>
 * tag, so map them to Arabic on the storefront.
 */
function baraa_pharmacy_translate_page_titles($title, $id = null)
{
    if (is_admin() || is_feed() || baraa_pharmacy_is_english_visitor()) {
        return $title;
    }

    static $titles = array(
        'Shop'         => 'المتجر',
        'Cart'         => 'سلة المشتريات',
        'Checkout'     => 'إتمام الطلب',
        'My account'   => 'حسابي',
        'Order received' => 'تم استلام الطلب',
    );

    return isset($titles[$title]) ? $titles[$title] : $title;
}
add_filter('the_title', 'baraa_pharmacy_translate_page_titles', 20, 2);

function baraa_pharmacy_translate_document_title($parts)
{
    if (is_admin() || baraa_pharmacy_is_english_visitor()) {
        return $parts;
    }

    static $titles = array(
        'Shop'       => 'المتجر',
        'Cart'       => 'سلة المشتريات',
        'Checkout'   => 'إتمام الطلب',
        'My account' => 'حسابي',
    );

    if (!empty($parts['title']) && isset($titles[$parts['title']])) {
        $parts['title'] = $titles[$parts['title']];
    }

    return $parts;
}
add_filter('document_title_parts', 'baraa_pharmacy_translate_document_title');

/**
 * Arabic names for the Egyptian governorates shown in the checkout state field
 * (WooCommerce ships them in English).
 */
function baraa_pharmacy_arabic_egypt_states($states)
{
    if (baraa_pharmacy_is_english_visitor() || !isset($states['EG'])) {
        return $states;
    }

    $states['EG'] = array(
        'EGALX' => 'الإسكندرية',
        'EGASN' => 'أسوان',
        'EGAST' => 'أسيوط',
        'EGBA'  => 'البحر الأحمر',
        'EGBH'  => 'البحيرة',
        'EGBNS' => 'بني سويف',
        'EGC'   => 'القاهرة',
        'EGDK'  => 'الدقهلية',
        'EGDT'  => 'دمياط',
        'EGFYM' => 'الفيوم',
        'EGGH'  => 'الغربية',
        'EGGZ'  => 'الجيزة',
        'EGIS'  => 'الإسماعيلية',
        'EGJS'  => 'جنوب سيناء',
        'EGKB'  => 'القليوبية',
        'EGKFS' => 'كفر الشيخ',
        'EGKN'  => 'قنا',
        'EGLX'  => 'الأقصر',
        'EGMN'  => 'المنيا',
        'EGMNF' => 'المنوفية',
        'EGMT'  => 'مطروح',
        'EGPTS' => 'بورسعيد',
        'EGSHG' => 'سوهاج',
        'EGSHR' => 'الشرقية',
        'EGSIN' => 'شمال سيناء',
        'EGSUZ' => 'السويس',
        'EGWAD' => 'الوادي الجديد',
    );

    return $states;
}
add_filter('woocommerce_states', 'baraa_pharmacy_arabic_egypt_states');

/**
 * Arabic labels for the shipping package name and method shown at checkout.
 * These titles are stored in the database, so the gettext filter cannot reach
 * them — they need dedicated filters instead.
 */
function baraa_pharmacy_arabic_shipping_package_name($name)
{
    if ((is_admin() && !wp_doing_ajax()) || baraa_pharmacy_is_english_visitor()) {
        return $name;
    }

    if ('Shipment' === $name) {
        return 'الشحنة';
    }

    if (preg_match('/^Shipment (\d+)$/', $name, $matches)) {
        return 'شحنة ' . $matches[1];
    }

    return $name;
}
add_filter('woocommerce_shipping_package_name', 'baraa_pharmacy_arabic_shipping_package_name');

function baraa_pharmacy_arabic_shipping_method_label($label, $method)
{
    if ((is_admin() && !wp_doing_ajax()) || baraa_pharmacy_is_english_visitor()) {
        return $label;
    }

    if (is_object($method) && 'free_shipping' === $method->id) {
        return 'شحن مجاني';
    }

    if ('Free shipping' === $label) {
        return 'شحن مجاني';
    }

    return $label;
}
add_filter('woocommerce_cart_shipping_method_full_label', 'baraa_pharmacy_arabic_shipping_method_label', 10, 2);

/**
 * Arabic descriptions for the Kashier payment methods.
 *
 * These descriptions are stored in the gateway settings (database), so the
 * gettext filter cannot reach them — they need the gateway's own filter.
 */
function baraa_pharmacy_arabic_kashier_description($description, $gateway_id)
{
    if ((is_admin() && !wp_doing_ajax()) || baraa_pharmacy_is_english_visitor()) {
        return $description;
    }

    return str_replace(
        array(
            'Pay with your Credit Card.',
            "Pay with your Mobile Wallet Bara'a.",
        ),
        array(
            'ادفع بأمان باستخدام بطاقتك الائتمانية.',
            'ادفع عبر محفظتك الإلكترونية.',
        ),
        $description
    );
}
add_filter('wc_kashier_description', 'baraa_pharmacy_arabic_kashier_description', 10, 2);

/**
 * English counterparts for the catalogue's category names.
 *
 * Every other translation in this file layers Arabic on top of the English
 * source strings. Categories are the opposite case: the catalogue was entered
 * in Arabic, so the names live in the database as Arabic text. The Arabic
 * storefront therefore already shows the right thing, and only English visitors
 * need a counterpart — which is why the filter below steps aside unless the
 * visitor explicitly switched to English.
 *
 * The gettext filter cannot reach database content, so the two term filters
 * after the map are used instead. Names with no entry fall through untouched,
 * so a category added in wp-admin never disappears from the storefront.
 */
function baraa_pharmacy_english_category_names()
{
    return array(
        'الادوية'                              => 'Medicines',
        'لبوس'                                 => 'Suppositories',
        'أقراص و كبسولات | الادوية'              => 'Tablets & Capsules',
        'أشربة | الادوية'                       => 'Syrups',
        'الادوية | حقن ومحاليل'                 => 'Injections & Solutions',
        'الادوية | قطرات ومراهم عين وأنف وأذن'   => 'Eye, Nose & Ear Care',
        'الادوية | كريمات و مراهم'              => 'Creams & Ointments',
        'الادوية | نقط بالفم'                   => 'Oral Drops',
    );
}

/**
 * Whether term names should be translated for this request. The admin always
 * keeps the stored Arabic so the catalogue stays editable.
 */
function baraa_pharmacy_should_translate_term_names()
{
    if (is_admin() && !wp_doing_ajax()) {
        return false;
    }

    return baraa_pharmacy_is_english_visitor();
}

function baraa_pharmacy_translate_term($term)
{
    if ($term instanceof WP_Term && baraa_pharmacy_should_translate_term_names()) {
        $names = baraa_pharmacy_english_category_names();

        if (isset($names[$term->name])) {
            $term->name = $names[$term->name];
        }
    }

    return $term;
}
add_filter('get_term', 'baraa_pharmacy_translate_term', 20);

function baraa_pharmacy_translate_terms($terms)
{
    if (!is_array($terms) || !baraa_pharmacy_should_translate_term_names()) {
        return $terms;
    }

    $names = baraa_pharmacy_english_category_names();

    foreach ($terms as $term) {
        if ($term instanceof WP_Term && isset($names[$term->name])) {
            $term->name = $names[$term->name];
        }
    }

    return $terms;
}
add_filter('get_terms', 'baraa_pharmacy_translate_terms', 20);

function baraa_pharmacy_handle_clear_cart()
{
    if (!isset($_POST['baraa_clear_cart'])) {
        return;
    }

    check_admin_referer('baraa-clear-cart', 'baraa-clear-cart-nonce');

    if (!function_exists('WC') || !WC()->cart) {
        return;
    }

    WC()->cart->empty_cart();
    wp_safe_redirect(wc_get_cart_url());
    exit;
}
add_action('wp_loaded', 'baraa_pharmacy_handle_clear_cart', 21);

/* ═══════════════════════════════════════════════════════════
   CUSTOMER WALLET — a discount that follows every customer
   ─────────────────────────────────────────────────────────────
   كل عميل يسجّل حسابًا في المتجر تُنشأ له "محفظة" تحمل نسبة خصم ثابتة.
   النسبة تُضبط مرة واحدة من لوحة التحكم وتُطبَّق تلقائيًا على كل فاتورة،
   فلا حاجة لكوبون أو أي خطوة إضافية لكل طلب. والتسجيل إلزامي حتى يصل
   العرض لكل من يشتري.
   ═══════════════════════════════════════════════════════════ */

/**
 * Force account creation at checkout: the wallet only exists for customers
 * who have an account. The values are filtered instead of written to the
 * database so the behaviour is identical on the local site and on Wasmer.
 */
function baraa_pharmacy_force_registration_enabled()
{
    return '1';
}
add_filter('pre_option_users_can_register', 'baraa_pharmacy_force_registration_enabled');

/**
 * Allow guest checkout: creating an account is offered — and rewarded with the
 * wallet discount — but it is never required to place an order. Shoppers who
 * order without an account still get their details remembered through the
 * profile cookie below.
 */
function baraa_pharmacy_allow_guest_checkout()
{
    return 'yes';
}
add_filter('pre_option_woocommerce_enable_guest_checkout', 'baraa_pharmacy_allow_guest_checkout');

function baraa_pharmacy_force_checkout_signup_on()
{
    return 'yes';
}
add_filter('pre_option_woocommerce_enable_signup_and_login_from_checkout', 'baraa_pharmacy_force_checkout_signup_on');

/**
 * Clamp any wallet rate to a sane 0–100 percentage.
 */
function baraa_pharmacy_wallet_clamp($value)
{
    return max(0.0, min(100.0, (float) $value));
}

/**
 * The store-wide wallet offer, as a percentage of the goods subtotal.
 * Zero (the default) means the wallet is not adding anything to invoices.
 */
function baraa_pharmacy_wallet_global_percent()
{
    return baraa_pharmacy_wallet_clamp(get_option('baraa_wallet_discount_percent', 0));
}

/**
 * Effective wallet rate for a customer: their personal rate when the shop
 * owner set one, otherwise the store-wide offer. A brand new customer has
 * no personal rate, which is exactly how "everyone who registers gets the
 * offer" is achieved.
 */
function baraa_pharmacy_wallet_user_percent($user_id = 0)
{
    $user_id = $user_id ? (int) $user_id : get_current_user_id();

    if (!$user_id) {
        return 0.0;
    }

    $personal = get_user_meta($user_id, 'baraa_wallet_discount', true);

    if ('' !== $personal && null !== $personal) {
        return baraa_pharmacy_wallet_clamp($personal);
    }

    return baraa_pharmacy_wallet_global_percent();
}

/**
 * Wallet copy exists in two languages inside this file (like the rest of the
 * storefront strings), so pick the right one for the current visitor.
 */
function baraa_pharmacy_wallet_text($arabic, $english)
{
    return baraa_pharmacy_is_english_visitor() ? $english : $arabic;
}

/**
 * Format a rate the way the store formats prices, so "10" reads as "10%".
 */
function baraa_pharmacy_wallet_percent_label($percent)
{
    $formatted = function_exists('wc_format_localized_decimal')
        ? wc_format_localized_decimal($percent)
        : number_format_i18n($percent, 2);

    return $formatted . '%';
}

/**
 * Apply the wallet rate as a negative fee. A fee (rather than a coupon or a
 * total override) is used on purpose: WooCommerce prints it as its own named
 * line in the cart, the checkout summary, the invoice and the order e-mails,
 * and recalculates it automatically whenever the cart changes.
 */
function baraa_pharmacy_apply_wallet_discount($cart)
{
    if (!is_user_logged_in() || (is_admin() && !wp_doing_ajax())) {
        return;
    }

    if (!$cart instanceof WC_Cart || $cart->is_empty()) {
        return;
    }

    $percent = baraa_pharmacy_wallet_user_percent();

    if ($percent <= 0) {
        return;
    }

    // The wallet offer is a one-time welcome discount: once the customer has
    // used it on an order, it is never applied again.
    if (baraa_pharmacy_wallet_discount_used(get_current_user_id())) {
        return;
    }

    // The offer applies to the whole invoice: goods after coupons, plus the
    // delivery charge. The shipping total is already resolved at this point
    // (WC_Cart_Totals calculates shipping before fees), so the discount can be
    // worked out against the final amount the customer is about to pay.
    $base = (float) $cart->get_subtotal()
        - (float) $cart->get_discount_total()
        + (float) $cart->get_shipping_total();

    if ($base <= 0) {
        return;
    }

    $amount = round($base * $percent / 100, wc_get_price_decimals());

    if ($amount <= 0) {
        return;
    }

    $name = baraa_pharmacy_wallet_text('خصم المحفظة', 'Wallet discount')
        . ' (' . baraa_pharmacy_wallet_percent_label($percent) . ')';

    $cart->add_fee($name, -1 * $amount, false);
}
add_action('woocommerce_cart_calculate_fees', 'baraa_pharmacy_apply_wallet_discount');

/* ── wp-admin: the store-wide rate + a per-customer override ── */

function baraa_pharmacy_wallet_admin_menu()
{
    add_submenu_page(
        'woocommerce',
        'محفظة العملاء',
        'محفظة العملاء',
        'manage_woocommerce',
        'baraa-wallet',
        'baraa_pharmacy_wallet_settings_page'
    );
}
add_action('admin_menu', 'baraa_pharmacy_wallet_admin_menu');

function baraa_pharmacy_wallet_register_setting()
{
    register_setting('baraa_wallet', 'baraa_wallet_discount_percent', array(
        'type'              => 'number',
        'sanitize_callback' => 'baraa_pharmacy_wallet_clamp',
        'default'           => 0,
    ));
}
add_action('admin_init', 'baraa_pharmacy_wallet_register_setting');

function baraa_pharmacy_wallet_settings_page()
{
    if (!current_user_can('manage_woocommerce')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('محفظة العملاء', 'baraa-pharmacy'); ?></h1>
        <p style="max-width:640px">
            كل عميل يسجّل حسابًا في المتجر تُنشأ له محفظة تحمل نسبة خصم ثابتة،
            وتُطبَّق هذه النسبة تلقائيًا على أول طلب يقوم به فقط (عرض ترحيبي لمرة واحدة) — بدون كوبون ولا خطوات إضافية.
        </p>
        <form method="post" action="options.php">
            <?php settings_fields('baraa_wallet'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="baraa_wallet_discount_percent">نسبة الخصم لكل العملاء (%)</label>
                    </th>
                    <td>
                        <input
                            id="baraa_wallet_discount_percent"
                            name="baraa_wallet_discount_percent"
                            type="number" step="0.01" min="0" max="100"
                            class="small-text"
                            value="<?php echo esc_attr(baraa_pharmacy_wallet_global_percent()); ?>"
                        />
                        <p class="description">
                            اتركها <code>0</code> لإيقاف العرض. ولتخصيص نسبة لعميل بعينه، افتح
                            <a href="<?php echo esc_url(admin_url('users.php')); ?>">بياناته من قائمة المستخدمين</a>.
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function baraa_pharmacy_wallet_profile_field($user)
{
    if (!current_user_can('manage_woocommerce')) {
        return;
    }

    $personal = get_user_meta($user->ID, 'baraa_wallet_discount', true);
    ?>
    <h2><?php esc_html_e('محفظة العميل', 'baraa-pharmacy'); ?></h2>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row">
                <label for="baraa_wallet_discount">نسبة خصم خاصة (%)</label>
            </th>
            <td>
                <input
                    id="baraa_wallet_discount"
                    name="baraa_wallet_discount"
                    type="number" step="0.01" min="0" max="100"
                    class="small-text"
                    value="<?php echo esc_attr('' === $personal || null === $personal ? '' : (float) $personal); ?>"
                />
                <p class="description">
                    اتركها فارغة ليأخذ العميل عرض المتجر العام
                    (<strong><?php echo esc_html(baraa_pharmacy_wallet_percent_label(baraa_pharmacy_wallet_global_percent())); ?></strong>).
                    واكتب <code>0</code> لإلغاء الخصم عن هذا العميل تحديدًا.
                </p>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'baraa_pharmacy_wallet_profile_field');
add_action('edit_user_profile', 'baraa_pharmacy_wallet_profile_field');

function baraa_pharmacy_wallet_save_profile_field($user_id)
{
    if (!current_user_can('manage_woocommerce') || !current_user_can('edit_user', $user_id)) {
        return;
    }

    if (!isset($_POST['baraa_wallet_discount'])) {
        return;
    }

    $raw = trim(sanitize_text_field(wp_unslash($_POST['baraa_wallet_discount'])));

    if ('' === $raw) {
        delete_user_meta($user_id, 'baraa_wallet_discount');

        return;
    }

    update_user_meta($user_id, 'baraa_wallet_discount', (string) baraa_pharmacy_wallet_clamp($raw));
}
add_action('personal_options_update', 'baraa_pharmacy_wallet_save_profile_field');
add_action('edit_user_profile_update', 'baraa_pharmacy_wallet_save_profile_field');

/* ── Storefront: "محفظتي" inside the customer's account ─────── */

function baraa_pharmacy_wallet_register_endpoint()
{
    add_rewrite_endpoint('wallet', EP_ROOT | EP_PAGES);
}
add_action('init', 'baraa_pharmacy_wallet_register_endpoint');

/**
 * Endpoints are new to WooCommerce, so its query vars need to know about it
 * for the account page to route and title the tab.
 */
function baraa_pharmacy_wallet_query_var($vars)
{
    $vars['wallet'] = 'wallet';

    return $vars;
}
add_filter('woocommerce_get_query_vars', 'baraa_pharmacy_wallet_query_var');

/**
 * Rewrite rules only need rebuilding once, the first time the endpoint exists.
 */
function baraa_pharmacy_wallet_maybe_flush_rewrites()
{
    if ('1' !== get_option('baraa_wallet_rewrites_flushed')) {
        flush_rewrite_rules(false);
        update_option('baraa_wallet_rewrites_flushed', '1');
    }
}
add_action('init', 'baraa_pharmacy_wallet_maybe_flush_rewrites', 30);

function baraa_pharmacy_wallet_menu_item($items)
{
    if (isset($items['wallet'])) {
        return $items;
    }

    $label   = baraa_pharmacy_wallet_text('محفظتي', 'My Wallet');
    $updated = array();

    foreach ($items as $endpoint => $text) {
        $updated[$endpoint] = $text;

        if ('dashboard' === $endpoint) {
            $updated['wallet'] = $label;
        }
    }

    if (!isset($updated['wallet'])) {
        $updated['wallet'] = $label;
    }

    return $updated;
}
add_filter('woocommerce_account_menu_items', 'baraa_pharmacy_wallet_menu_item');

function baraa_pharmacy_wallet_endpoint_title($title)
{
    global $wp_query;

    if (!empty($wp_query->query_vars['wallet'])) {
        return baraa_pharmacy_wallet_text('محفظتي', 'My Wallet');
    }

    return $title;
}
add_filter('woocommerce_endpoint_wallet_title', 'baraa_pharmacy_wallet_endpoint_title');

/**
 * خصم المحفظة يُحفظ في الطلب بالاسم الذي يولّده هذا الثيم حسب لغة الزائر
 * («خصم المحفظة (20%)» أو "Wallet discount (20%)")، فنطابق بداية الاسم فقط
 * حتى يعمل السجل مع الطلبات القديمة والجديدة على حدٍّ سواء.
 */
function baraa_pharmacy_is_wallet_fee_name($name)
{
    $name = (string) $name;

    foreach (array('خصم المحفظة', 'Wallet discount') as $prefix) {
        if (0 === strpos($name, $prefix)) {
            return true;
        }
    }

    return false;
}

/**
 * كل بنود خصم المحفظة في طلبات العميل (الأحدث أولًا) مع إجمالي ما وفّره.
 *
 * تُقرأ من بنود الطلبات المحفوظة لا من نسبة الخصم الحالية، فيبقى السجل صحيحًا
 * كما كان وقت الشراء حتى لو غيّر المسؤول النسبة لاحقًا.
 */
function baraa_pharmacy_wallet_order_history($user_id, $limit = 10)
{
    if (!$user_id || !function_exists('wc_get_orders')) {
        return array('rows' => array(), 'total' => 0.0);
    }

    $orders = wc_get_orders(array(
        'customer_id' => (int) $user_id,
        'limit'       => (int) $limit,
        'orderby'     => 'date',
        'order'       => 'DESC',
        'status'      => array('pending', 'processing', 'on-hold', 'completed', 'refunded'),
    ));

    $rows  = array();
    $total = 0.0;

    foreach ($orders as $order) {
        foreach ($order->get_fees() as $fee) {
            if (!baraa_pharmacy_is_wallet_fee_name($fee->get_name())) {
                continue;
            }

            $amount = abs((float) $fee->get_total());

            if ($amount <= 0) {
                continue;
            }

            $rows[] = array('order' => $order, 'amount' => $amount);
            $total += $amount;
        }
    }

    return array('rows' => $rows, 'total' => $total);
}

/**
 * خصم المحفظة عرض ترحيبي يُمنح مرة واحدة فقط لكل عميل: بعد أول طلب يتطبّق
 * عليه الخصم يتحفظ علم دائم على الحساب فما يتكررش تاني أبدًا. العلم بيتحط أول
 * ما نلاقي طلب فيه بند خصم المحفظة — وده بيغطي الطلبات القديمة كمان.
 */
function baraa_pharmacy_wallet_discount_used($user_id)
{
    $user_id = (int) $user_id;

    if (!$user_id) {
        return false;
    }

    if ('yes' === get_user_meta($user_id, 'baraa_wallet_offer_used', true)) {
        return true;
    }

    $history = baraa_pharmacy_wallet_order_history($user_id, 50);

    if (!empty($history['rows'])) {
        update_user_meta($user_id, 'baraa_wallet_offer_used', 'yes');
        return true;
    }

    return false;
}

function baraa_pharmacy_wallet_endpoint_content()
{
    $percent = baraa_pharmacy_wallet_user_percent();
    $history = baraa_pharmacy_wallet_order_history(get_current_user_id());
    $rows    = $history['rows'];
    $saved   = $history['total'];
    $is_en   = baraa_pharmacy_is_english_visitor();
    $used    = baraa_pharmacy_wallet_discount_used(get_current_user_id());

    $ar_months = array(
        1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
        5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
        9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
    );
    ?>
    <div class="baraa-wallet grid grid-cols-1 sm:grid-cols-2 gap-space-md mb-space-lg">
        <div class="bg-surface-container-lowest rounded-xl shadow-sm p-space-lg flex items-center gap-space-md">
            <div class="w-16 h-16 rounded-xl bg-primary/10 text-primary flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-[32px]" aria-hidden="true">account_balance_wallet</span>
            </div>
            <div>
                <p class="font-label-md text-label-md text-on-surface-variant mb-1">
                    <?php echo esc_html($used
                        ? baraa_pharmacy_wallet_text('حالة عرض المحفظة', 'Wallet offer status')
                        : baraa_pharmacy_wallet_text('نسبة الخصم في محفظتك', 'Your wallet discount')); ?>
                </p>
                <p class="font-headline-md text-headline-md font-bold text-primary">
                    <?php
                    if ($used) {
                        echo esc_html(baraa_pharmacy_wallet_text('تم استخدامه', 'Used'));
                    } elseif ($percent > 0) {
                        echo esc_html(baraa_pharmacy_wallet_percent_label($percent));
                    } else {
                        echo esc_html(baraa_pharmacy_wallet_text('لا يوجد خصم حاليًا', 'No discount yet'));
                    }
                    ?>
                </p>
            </div>
        </div>

        <div class="bg-surface-container-lowest rounded-xl shadow-sm p-space-lg flex items-center gap-space-md">
            <div class="w-16 h-16 rounded-xl bg-primary/10 text-primary flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-[32px]" aria-hidden="true">savings</span>
            </div>
            <div>
                <p class="font-label-md text-label-md text-on-surface-variant mb-1">
                    <?php echo esc_html(baraa_pharmacy_wallet_text('إجمالي اللي وفّرناه لك', 'Total you have saved')); ?>
                </p>
                <p class="font-headline-md text-headline-md font-bold text-primary">
                    <?php echo wp_kses_post(wc_price($saved)); ?>
                </p>
            </div>
        </div>
    </div>

    <p class="font-body-md text-body-md text-on-surface-variant mb-space-lg">
        <?php
        if ($used) {
            echo esc_html(baraa_pharmacy_wallet_text(
                'استخدمت عرض المحفظة في أول طلب لك، وسجل الخصومات بالأسفل يوضّح قيمة اللي وفّرناه لك.',
                'You used the wallet offer on your first order. The discount history below shows how much you saved.'
            ));
        } elseif ($percent > 0) {
            echo esc_html(baraa_pharmacy_wallet_text(
                'يُخصم هذا العرض تلقائيًا من إجمالي المنتجات + الشحن في أول طلب تقوم به من المتجر.',
                'This one-time offer is deducted automatically from the goods + delivery total of your first order.'
            ));
        } else {
            echo esc_html(baraa_pharmacy_wallet_text(
                'سيظهر هنا أي عرض يمنحه المتجر لحسابك.',
                'Any offer the store grants your account will appear here.'
            ));
        }
        ?>
    </p>

    <div class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
        <div class="p-space-md border-b border-outline-variant/30 flex items-center gap-2.5">
            <span class="material-symbols-outlined text-primary text-[22px]" aria-hidden="true">history</span>
            <h2 class="font-title-lg text-title-lg font-bold text-on-surface">
                <?php echo esc_html(baraa_pharmacy_wallet_text('سجل الخصومات', 'Discount history')); ?>
            </h2>
        </div>

        <?php if ($rows) : ?>
            <ul class="baraa-wallet-history divide-y divide-outline-variant/30">
                <?php foreach ($rows as $row) :
                    $order   = $row['order'];
                    $created = $order->get_date_created();

                    if (!$created) {
                        $order_date = '';
                    } elseif ($is_en) {
                        $order_date = $created->date_i18n('j F Y');
                    } else {
                        $order_date = $created->date_i18n('j') . ' ' . $ar_months[(int) $created->date_i18n('n')] . ' ' . $created->date_i18n('Y');
                    }
                    ?>
                    <li class="p-space-md flex items-center justify-between gap-space-md">
                        <div class="min-w-0 text-right">
                            <a href="<?php echo esc_url($order->get_view_order_url()); ?>"
                               class="font-title-md text-title-md font-bold text-on-surface hover:text-primary transition-colors">
                                <?php echo esc_html(baraa_pharmacy_wallet_text('طلب رقم', 'Order')); ?>
                                <span dir="ltr">#<?php echo esc_html($order->get_order_number()); ?></span>
                            </a>
                            <p class="font-body-sm text-body-sm text-on-surface-variant mt-0.5"><?php echo esc_html($order_date); ?></p>
                        </div>
                        <div class="shrink-0 text-left">
                            <span class="font-title-md text-title-md font-bold text-primary" dir="ltr">
                                &minus;&nbsp;<?php echo wp_kses_post(wc_price($row['amount'])); ?>
                            </span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p class="p-space-lg font-body-md text-body-md text-on-surface-variant">
                <?php echo esc_html(baraa_pharmacy_wallet_text(
                    'لسه مفيش خصومات في سجلك. أول ما تكمّل طلبك، هيظهر هنا فورًا.',
                    'No discounts yet. As soon as you complete an order it will show up here.'
                )); ?>
            </p>
        <?php endif; ?>
    </div>
    <?php
}
add_action('woocommerce_account_wallet_endpoint', 'baraa_pharmacy_wallet_endpoint_content');

/* ═══════════════════════════════════════════════════════════
   BRANCHES & DELIVERY ZONES
   ───────────────────────────────────────────────────────────
   The pharmacy runs several branches across Alexandria and every
   branch covers the areas around it at its own flat delivery fee.
   Branches are edited in WooCommerce → الفروع والتوصيل and read
   everywhere through baraa_pharmacy_branches(): the footer lists
   them, the checkout offers their areas, and the delivery charge
   becomes the price of the chosen area instead of RouteMile's
   distance-based quote.
   ═══════════════════════════════════════════════════════════ */

/**
 * Bilingual storefront string. The theme switches language through
 * baraa_pharmacy_is_english_visitor() instead of .mo files, so new copy
 * follows the same rule.
 */
function baraa_pharmacy_text($arabic, $english)
{
    return baraa_pharmacy_is_english_visitor() ? $english : $arabic;
}

/**
 * The branches the store runs before the settings page is saved for the
 * first time — the address the footer used to hard-code.
 */
function baraa_pharmacy_default_branches()
{
    return array(
        array(
            'id'       => 'agami',
            'name'     => 'فرع العجمي',
            'address'  => 'الدرايسة — أمام مدرسة العجمي النموذجية',
            'address2' => 'أبو يوسف — أمام مسجد الصابرين',
            'map'      => 'https://www.google.com/maps?q=31.1021,29.77585',
            'enabled'  => 'yes',
            'zones'    => array(),
        ),
    );
}

function baraa_pharmacy_branches()
{
    $stored = get_option('baraa_branches', null);

    return is_array($stored) ? $stored : baraa_pharmacy_default_branches();
}

function baraa_pharmacy_branch_is_enabled($branch)
{
    return isset($branch['enabled']) && 'yes' === $branch['enabled'];
}

function baraa_pharmacy_enabled_branches()
{
    $enabled = array();

    foreach (baraa_pharmacy_branches() as $branch) {
        if (baraa_pharmacy_branch_is_enabled($branch)) {
            $enabled[] = $branch;
        }
    }

    return $enabled;
}

/**
 * Stable key for one delivery area. It hashes the area name, so editing the
 * price never moves a customer's saved choice — only renaming the area does.
 */
function baraa_pharmacy_zone_key($branch_id, $zone_name)
{
    return $branch_id . '--' . substr(md5((string) $zone_name), 0, 8);
}

/**
 * Every area of every enabled branch, keyed by its zone key.
 *
 * @return array<string,array>
 */
function baraa_pharmacy_all_zones()
{
    $zones = array();

    foreach (baraa_pharmacy_enabled_branches() as $branch) {
        if (empty($branch['zones']) || !is_array($branch['zones'])) {
            continue;
        }

        foreach ($branch['zones'] as $zone) {
            $name = isset($zone['name']) ? trim((string) $zone['name']) : '';

            if ('' === $name) {
                continue;
            }

            $key = baraa_pharmacy_zone_key($branch['id'], $name);

            $zones[$key] = array(
                'key'       => $key,
                'name'      => $name,
                'price'     => isset($zone['price']) ? (float) $zone['price'] : 0.0,
                'branch_id' => $branch['id'],
                'branch'    => $branch['name'],
            );
        }
    }

    return $zones;
}

function baraa_pharmacy_zone($key)
{
    if (!is_string($key) || '' === $key) {
        return null;
    }

    $zones = baraa_pharmacy_all_zones();

    return isset($zones[$key]) ? $zones[$key] : null;
}

/**
 * The area the customer picked, kept in the WC session so the delivery charge
 * survives every cart and checkout recalculation.
 */
function baraa_pharmacy_selected_zone_key()
{
    if (!function_exists('WC') || !WC()->session) {
        return '';
    }

    $key = WC()->session->get('baraa_zone');

    return baraa_pharmacy_zone($key) ? $key : '';
}

/**
 * Read the area out of the current request. The classic checkout posts its
 * fields normally; its AJAX order-review refresh sends them as one serialized
 * "post_data" string instead.
 */
function baraa_pharmacy_posted_zone_key()
{
    if (isset($_POST['baraa_zone'])) {
        return sanitize_text_field(wp_unslash($_POST['baraa_zone']));
    }

    if (!empty($_POST['post_data'])) {
        $parsed = array();
        parse_str((string) wp_unslash($_POST['post_data']), $parsed);

        if (isset($parsed['baraa_zone'])) {
            return sanitize_text_field($parsed['baraa_zone']);
        }
    }

    return null;
}

function baraa_pharmacy_sync_zone_session()
{
    if (is_admin() || !function_exists('WC') || !WC()->session) {
        return;
    }

    $posted = baraa_pharmacy_posted_zone_key();

    if (null === $posted) {
        return;
    }

    $key = baraa_pharmacy_zone($posted) ? $posted : '';

    if (WC()->session->get('baraa_zone') !== $key) {
        WC()->session->set('baraa_zone', $key);
    }
}
/*
 * Both paths that recalculate the cart need the area in the session first:
 * the AJAX order review (WC_AJAX::update_order_review) and the final "place
 * order" request, where process_checkout() fires this action ahead of
 * update_session().
 */
add_action('woocommerce_checkout_update_order_review', 'baraa_pharmacy_sync_zone_session', 5);
add_action('woocommerce_checkout_process', 'baraa_pharmacy_sync_zone_session', 1);

/**
 * Stamp the chosen area onto the shipping package. WooCommerce hashes the
 * package to decide whether its cached rates are still valid, so this is what
 * forces a recalculation the moment the customer switches area.
 */
function baraa_pharmacy_stamp_zone_on_packages($packages)
{
    $key = baraa_pharmacy_selected_zone_key();

    if ('' === $key) {
        return $packages;
    }

    foreach ($packages as $i => $package) {
        $packages[$i]['baraa_zone'] = $key;
    }

    return $packages;
}
add_filter('woocommerce_cart_shipping_packages', 'baraa_pharmacy_stamp_zone_on_packages', 20);

/**
 * Replace the distance-based RouteMile quote with the flat price of the area
 * the customer picked. The shipping method itself stays in place, so its
 * opening-hours and minimum-order checks keep running.
 */
function baraa_pharmacy_apply_zone_shipping_cost($rates, $package)
{
    if (empty($package['baraa_zone'])) {
        return $rates;
    }

    $zone = baraa_pharmacy_zone($package['baraa_zone']);

    if (!$zone) {
        return $rates;
    }

    $cost = (float) $zone['price'];

    foreach ($rates as $rate) {
        if (method_exists($rate, 'get_method_id') && 0 === strpos($rate->get_method_id(), 'routemile_delivery')) {
            $rate->set_cost($cost);
            $rate->set_taxes(WC_Tax::calc_shipping_taxes($cost, WC_Tax::get_shipping_tax_rates()));
        }
    }

    return $rates;
}
add_filter('woocommerce_package_rates', 'baraa_pharmacy_apply_zone_shipping_cost', 20, 2);

/**
 * The "delivery area" picker on the checkout, right under RouteMile's map so
 * the customer picks the pin first and the area that covers it second.
 *
 * The wrapper carries WooCommerce's own `address-field` /
 * `update_totals_on_change` classes because that is what the checkout JS
 * watches to refresh the order review when the selection changes.
 */
function baraa_pharmacy_render_zone_field()
{
    if (!function_exists('WC') || !WC()->checkout) {
        return;
    }

    $zones = baraa_pharmacy_all_zones();

    if (empty($zones)) {
        return;
    }

    $grouped = array();

    foreach ($zones as $zone) {
        $grouped[$zone['branch']][] = $zone;
    }

    $selected = baraa_pharmacy_selected_zone_key();
    ?>
    <div class="baraa-zone-card bg-surface-container-lowest rounded-xl shadow-sm p-space-lg mb-space-lg">
        <div class="flex items-start gap-space-sm mb-space-sm">
            <span class="material-symbols-outlined text-primary text-[22px]" aria-hidden="true">delivery_dining</span>
            <div>
                <h3 class="font-title-lg text-title-lg font-bold text-on-surface">
                    <?php echo esc_html(baraa_pharmacy_text('منطقة التوصيل', 'Delivery area')); ?>
                </h3>
                <p class="font-body-sm text-body-sm text-on-surface-variant">
                    <?php echo esc_html(baraa_pharmacy_text('لو عنوانك داخل مناطق التغطية اختر منطقتك وسيُحسب التوصيل على أساسها. لو برّه المناطق دي، سيبها فاضية وهيُحسب السعر بالمسافة.', 'If your address is inside a covered area, pick it and the fee follows. If it is outside, leave it empty and the fee is calculated by distance.')); ?>
                </p>
            </div>
        </div>

        <p class="form-row form-row-wide address-field update_totals_on_change baraa-zone-row" id="baraa_zone_field">
            <label class="font-label-md text-label-md text-on-surface font-semibold" for="baraa_zone">
                <?php echo esc_html(baraa_pharmacy_text('المنطقة (اختياري)', 'Area (optional)')); ?>
            </label>
            <select name="baraa_zone" id="baraa_zone" class="select">
                <option value=""><?php echo esc_html(baraa_pharmacy_text('برّه مناطق التغطية (يُحسب بالمسافة)', 'Outside covered areas (calculated by distance)')); ?></option>
                <?php foreach ($grouped as $branch_name => $branch_zones) : ?>
                    <optgroup label="<?php echo esc_attr($branch_name); ?>">
                        <?php foreach ($branch_zones as $zone) : ?>
                            <option value="<?php echo esc_attr($zone['key']); ?>" <?php selected($selected, $zone['key']); ?>>
                                <?php
                                echo esc_html(
                                    $zone['name'] . ' — ' . html_entity_decode(wp_strip_all_tags(wc_price($zone['price'])), ENT_QUOTES, 'UTF-8')
                                );
                                ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
        </p>
    </div>
    <?php
}
add_action('woocommerce_before_checkout_billing_form', 'baraa_pharmacy_render_zone_field', 20);

/*
 * اختيار المنطقة اختياري بالكامل: لو العنوان داخل منطقة تغطية يختارها العميل
 * ويُحسب التوصيل بسعرها الثابت، ولو برّه كل المناطق يسيبها فاضية فيتولى
 * RouteMile حساب السعر بالمسافة. لذا لا توجد أي رسالة إلزام.
 */

function baraa_pharmacy_save_order_zone($order)
{
    $zone = baraa_pharmacy_zone(baraa_pharmacy_selected_zone_key());

    if (!$zone) {
        return;
    }

    $order->update_meta_data('_baraa_delivery_zone', $zone['name']);
    $order->update_meta_data('_baraa_delivery_branch', $zone['branch']);
}
add_action('woocommerce_checkout_create_order', 'baraa_pharmacy_save_order_zone', 20);

/**
 * Show the chosen area on the order screen, so the branch that fulfils the
 * order knows where it goes.
 */
function baraa_pharmacy_order_zone_admin($order)
{
    $zone = $order->get_meta('_baraa_delivery_zone');

    if ('' === $zone) {
        return;
    }

    $branch = $order->get_meta('_baraa_delivery_branch');
    ?>
    <div class="address">
        <p>
            <strong><?php esc_html_e('منطقة التوصيل:', 'baraa-pharmacy'); ?></strong>
            <?php echo esc_html($zone); ?>
            <?php echo $branch ? ' (' . esc_html($branch) . ')' : ''; ?>
        </p>
    </div>
    <?php
}
add_action('woocommerce_admin_order_data_after_shipping_address', 'baraa_pharmacy_order_zone_admin');

/* ── wp-admin: branches and their delivery areas ─────────────── */

function baraa_pharmacy_branches_admin_menu()
{
    add_submenu_page(
        'woocommerce',
        'الفروع ومناطق التوصيل',
        'الفروع والتوصيل',
        'manage_woocommerce',
        'baraa-branches',
        'baraa_pharmacy_branches_settings_page'
    );
}
add_action('admin_menu', 'baraa_pharmacy_branches_admin_menu');

function baraa_pharmacy_branches_register_setting()
{
    register_setting('baraa_branches_group', 'baraa_branches', array(
        'type'              => 'array',
        'sanitize_callback' => 'baraa_pharmacy_sanitize_branches',
    ));
}
add_action('admin_init', 'baraa_pharmacy_branches_register_setting');

function baraa_pharmacy_sanitize_branches($value)
{
    if (!is_array($value)) {
        return array();
    }

    $branches = array();

    foreach ($value as $index => $branch) {
        if (!is_array($branch)) {
            continue;
        }

        $name     = isset($branch['name']) ? sanitize_text_field((string) $branch['name']) : '';
        $address  = isset($branch['address']) ? sanitize_text_field((string) $branch['address']) : '';
        $address2 = isset($branch['address2']) ? sanitize_text_field((string) $branch['address2']) : '';
        $map      = isset($branch['map']) ? esc_url_raw(trim((string) $branch['map'])) : '';
        $id       = isset($branch['id']) ? sanitize_key((string) $branch['id']) : '';

        $zones = array();

        if (!empty($branch['zones']) && is_array($branch['zones'])) {
            foreach ($branch['zones'] as $zone) {
                if (!is_array($zone)) {
                    continue;
                }

                $zone_name = isset($zone['name']) ? sanitize_text_field((string) $zone['name']) : '';

                if ('' === $zone_name) {
                    continue;
                }

                $zones[] = array(
                    'name'  => $zone_name,
                    'price' => isset($zone['price']) ? max(0, (float) $zone['price']) : 0.0,
                );
            }
        }

        // The empty row of a freshly added branch is not a branch yet.
        if ('' === $name && '' === $address && empty($zones)) {
            continue;
        }

        if ('' === $name) {
            $name = 'فرع';
        }

        if ('' === $id) {
            $id = 'branch-' . substr(md5($name . '|' . $index), 0, 8);
        }

        $branches[] = array(
            'id'       => $id,
            'name'     => $name,
            'address'  => $address,
            'address2' => $address2,
            'map'      => $map,
            'enabled'  => empty($branch['enabled']) ? 'no' : 'yes',
            'zones'    => $zones,
        );
    }

    return $branches;
}

/**
 * Alexandria districts and the neighbourhoods inside each one, so the branch
 * editor offers ready pickers instead of typing every area by hand. The keys
 * follow the city's administrative districts; the same data feeds the select
 * options here and the cascading map in the settings script.
 *
 * @return array<string,array{label:string,areas:array<int,string>}>
 */
function baraa_pharmacy_alexandria_districts()
{
    return array(
        'east'      => array(
            'label' => 'حي شرق',
            'areas' => array('سموحة', 'سيدي جابر', 'رشدي', 'كفر عبده', 'ستانلي', 'بولكلي', 'جليم', 'زيزينيا', 'سان ستيفانو', 'جناكليس', 'لوران'),
        ),
        'central'   => array(
            'label' => 'حي وسط',
            'areas' => array('الشاطبي', 'الأزاريطة', 'محرم بك', 'كامب شيزار', 'الإبراهيمية', 'الحضرة', 'كوم الدكة', 'محطة مصر'),
        ),
        'west'      => array(
            'label' => 'حي غرب',
            'areas' => array('القباري', 'المكس', 'الورديان', 'كرموز', 'غيط العنب', 'مينا البصل'),
        ),
        'montaza_1' => array(
            'label' => 'المنتزه أول',
            'areas' => array('سيدي بشر', 'ميامي', 'العصافرة', 'خورشيد'),
        ),
        'montaza_2' => array(
            'label' => 'المنتزه ثان',
            'areas' => array('المعمورة', 'أبو قير', 'طوسون', 'المنتزه', 'الملاحة'),
        ),
        'gomrok'    => array(
            'label' => 'الجمرك',
            'areas' => array('الأنفوشي', 'بحري', 'المنشية', 'رأس التين'),
        ),
        'agami'     => array(
            'label' => 'العجمي',
            'areas' => array('البيطاش', 'الهانوفيل', 'أبو يوسف', 'الحديد والصلب', 'الدخيلة', '6 أكتوبر','كيلو 21'),
        ),
        'amreya_1'  => array(
            'label' => 'العامرية أول',
            'areas' => array('العامرية البلد', 'مرغم', 'عبد القادر'),
        ),
        'amreya_2'  => array(
            'label' => 'العامرية ثان',
            'areas' => array('كينج مريوط', 'الهوارية', 'قرى البنجر'),
        ),
    );
}

/**
 * One delivery area row: pick the district, then the area inside it, then the
 * fee. The area select keeps the branch's zone field name so the saved value
 * is the neighbourhood the footer and checkout already know.
 */
function baraa_pharmacy_render_zone_row($branch_index, $zone_index, $zone)
{
    $name  = isset($zone['name']) ? (string) $zone['name'] : '';
    $price = isset($zone['price']) && '' !== $zone['price'] ? (string) $zone['price'] : '';

    $base = 'baraa_branches[' . $branch_index . '][zones][' . $zone_index . ']';
    ?>
    <tr data-area="<?php echo esc_attr($name); ?>">
        <td>
            <select class="alex-district-select">
                <option value="">اختر الحي</option>
                <?php foreach (baraa_pharmacy_alexandria_districts() as $key => $district) : ?>
                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($district['label']); ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <select class="alex-subarea-select" name="<?php echo esc_attr($base); ?>[name]">
                <?php if ('' !== $name) : ?>
                    <option value="<?php echo esc_attr($name); ?>" selected><?php echo esc_html($name); ?></option>
                <?php else : ?>
                    <option value="">اختر المنطقة</option>
                <?php endif; ?>
            </select>
        </td>
        <td>
            <input type="number" min="0" step="0.01" class="small-text" name="<?php echo esc_attr($base); ?>[price]" value="<?php echo esc_attr($price); ?>" placeholder="0" />
        </td>
        <td>
            <button type="button" class="button baraa-remove-zone">حذف</button>
        </td>
    </tr>
    <?php
}

function baraa_pharmacy_render_branch_block($index, $branch, $placeholder_row = false)
{
    $name     = isset($branch['name']) ? (string) $branch['name'] : '';
    $address  = isset($branch['address']) ? (string) $branch['address'] : '';
    $address2 = isset($branch['address2']) ? (string) $branch['address2'] : '';
    $map      = isset($branch['map']) ? (string) $branch['map'] : '';
    $id       = isset($branch['id']) ? (string) $branch['id'] : '';
    $enabled  = !isset($branch['enabled']) || 'no' !== $branch['enabled'];

    $zones = !empty($branch['zones']) && is_array($branch['zones']) ? $branch['zones'] : array();

    $base = 'baraa_branches[' . $index . ']';
    ?>
    <div class="baraa-branch postbox" data-branch-index="<?php echo esc_attr($index); ?>">
        <h2 class="hndle" style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;margin:0;">
            <span>فرع</span>
            <button type="button" class="button baraa-remove-branch">حذف الفرع</button>
        </h2>

        <div class="inside">
            <input type="hidden" name="<?php echo esc_attr($base); ?>[id]" value="<?php echo esc_attr($id); ?>" />

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label>اسم الفرع</label></th>
                    <td><input type="text" class="regular-text" name="<?php echo esc_attr($base); ?>[name]" value="<?php echo esc_attr($name); ?>" placeholder="مثال: فرع العجمي" /></td>
                </tr>
                <tr>
                    <th scope="row"><label>العنوان</label></th>
                    <td><input type="text" class="large-text" name="<?php echo esc_attr($base); ?>[address]" value="<?php echo esc_attr($address); ?>" placeholder="مثال: الدرايسة — أمام مدرسة العجمي النموذجية" /></td>
                </tr>
                <tr>
                    <th scope="row"><label>سطر عنوان إضافي</label></th>
                    <td>
                        <input type="text" class="large-text" name="<?php echo esc_attr($base); ?>[address2]" value="<?php echo esc_attr($address2); ?>" />
                        <p class="description">اختياري — يظهر تحت السطر الأول في الفوتر.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label>رابط الخريطة</label></th>
                    <td>
                        <input type="url" class="large-text" name="<?php echo esc_attr($base); ?>[map]" value="<?php echo esc_attr($map); ?>" placeholder="https://www.google.com/maps?q=..." />
                        <p class="description">اختياري — يظهر كزر «عرض على الخريطة» أسفل عنوان الفرع.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">الظهور</th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr($base); ?>[enabled]" value="1" <?php checked($enabled); ?> />
                            عرض الفرع ومناطقه في الموقع
                        </label>
                    </td>
                </tr>
            </table>

            <h3>مناطق التوصيل وأسعارها</h3>
            <p class="description">اختر الحي ثم المنطقة، واكتب سعر التوصيل الذي يدفعه العميل للوصول إليها من هذا الفرع.</p>

            <table class="widefat striped baraa-zones">
                <thead>
                    <tr>
                        <th>الحي</th>
                        <th>المنطقة</th>
                        <th>سعر التوصيل</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (empty($zones)) {
                        baraa_pharmacy_render_zone_row($index, $placeholder_row ? '__Z__' : 0, array());
                    } else {
                        foreach ($zones as $z => $zone) {
                            baraa_pharmacy_render_zone_row($index, $placeholder_row ? '__Z__' : $z, $zone);
                        }
                    }
                    ?>
                </tbody>
            </table>

            <p><button type="button" class="button baraa-add-zone">+ إضافة منطقة</button></p>
        </div>
    </div>
    <?php
}

function baraa_pharmacy_branches_settings_page()
{
    if (!current_user_can('manage_woocommerce')) {
        return;
    }

    $branches = baraa_pharmacy_branches();

    // Feeds the cascading district → area picker in the script below.
    $areas_map = wp_list_pluck(baraa_pharmacy_alexandria_districts(), 'areas');

    $branch_seq = count($branches);
    $zone_seq   = 0;

    foreach ($branches as $branch) {
        $zone_seq += !empty($branch['zones']) && is_array($branch['zones']) ? count($branch['zones']) : 1;
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('الفروع ومناطق التوصيل', 'baraa-pharmacy'); ?></h1>
        <p style="max-width:760px">
            أضف كل فرع من فروع الصيدلية، واكتب تحته المناطق التي يوصّل إليها مع سعر التوصيل لكل منطقة.
            المنطقة التي يختارها العميل في صفحة إتمام الطلب هي التي تحدد سعر التوصيل،
            وتظهر الفروع المفعّلة تلقائيًا في فوتر الموقع.
        </p>

        <form method="post" action="options.php">
            <?php settings_fields('baraa_branches_group'); ?>

            <div id="baraa-branches-list" data-branch-seq="<?php echo esc_attr($branch_seq); ?>" data-zone-seq="<?php echo esc_attr($zone_seq); ?>">
                <?php
                foreach ($branches as $i => $branch) {
                    baraa_pharmacy_render_branch_block($i, $branch);
                }
                ?>
            </div>

            <p>
                <button type="button" class="button button-secondary" id="baraa-add-branch">+ إضافة فرع جديد</button>
            </p>

            <?php submit_button(); ?>
        </form>

        <!-- Templates, kept outside the form so their inputs are never submitted -->
        <div id="baraa-branch-template" hidden>
            <?php baraa_pharmacy_render_branch_block('__B__', array(), true); ?>
        </div>

        <div id="baraa-zone-template" hidden>
            <table><tbody><?php baraa_pharmacy_render_zone_row('__B__', '__Z__', array()); ?></tbody></table>
        </div>
    </div>

    <script>
    ( function () {
        var list = document.getElementById( 'baraa-branches-list' );
        if ( ! list ) {
            return;
        }

        var branchTpl = document.getElementById( 'baraa-branch-template' );
        var zoneTpl   = document.getElementById( 'baraa-zone-template' );
        var branchSeq = parseInt( list.getAttribute( 'data-branch-seq' ), 10 ) || 0;
        var zoneSeq   = parseInt( list.getAttribute( 'data-zone-seq' ), 10 ) || 0;

        function rename( node, branchIndex, zoneIndex ) {
            node.querySelectorAll( '[name]' ).forEach( function ( input ) {
                input.name = input.name
                    .replace( /__B__/g, branchIndex )
                    .replace( /__Z__/g, zoneIndex );
            } );
        }

        document.getElementById( 'baraa-add-branch' ).addEventListener( 'click', function () {
            var block = branchTpl.querySelector( '.baraa-branch' ).cloneNode( true );
            var index = branchSeq++;

            block.setAttribute( 'data-branch-index', index );
            rename( block, index, zoneSeq++ );
            list.appendChild( block );
        } );

        list.addEventListener( 'click', function ( event ) {
            var target = event.target;

            if ( target.classList.contains( 'baraa-add-zone' ) ) {
                event.preventDefault();
                var block = target.closest( '.baraa-branch' );
                var row   = zoneTpl.querySelector( 'tr' ).cloneNode( true );

                rename( row, block.getAttribute( 'data-branch-index' ), zoneSeq++ );
                block.querySelector( '.baraa-zones tbody' ).appendChild( row );
                return;
            }

            if ( target.classList.contains( 'baraa-remove-zone' ) ) {
                event.preventDefault();
                target.closest( 'tr' ).remove();
                return;
            }

            if ( target.classList.contains( 'baraa-remove-branch' ) ) {
                event.preventDefault();

                if ( window.confirm( 'حذف هذا الفرع وكل مناطقه؟' ) ) {
                    target.closest( '.baraa-branch' ).remove();
                }
            }
        } );
    } )();

    ( function ( $ ) {
        var alexMap = <?php echo wp_json_encode( $areas_map, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>;

        // يملأ قائمة المناطق بمناطق الحي المختار فقط.
        function fillSubareas( $row, district, selected ) {
            var $sub = $row.find( '.alex-subarea-select' );

            $sub.html( '<option value="">اختر المنطقة</option>' );

            if ( district && alexMap[ district ] ) {
                $.each( alexMap[ district ], function ( i, area ) {
                    $sub.append( $( '<option>' ).val( area ).text( area ) );
                } );
            }

            if ( selected ) {
                $sub.val( selected );
            }
        }

        $( function () {
            // تغيير الحي يعيد تحميل مناطق هذا الحي فقط.
            $( '#baraa-branches-list' ).on( 'change', '.alex-district-select', function () {
                fillSubareas( $( this ).closest( 'tr' ), $( this ).val(), '' );
            } );

            // الصفوف المحفوظة: نضبط الحي تلقائيًا انطلاقًا من اسم المنطقة.
            $( '#baraa-branches-list .baraa-zones tr' ).each( function () {
                var $row = $( this );
                var area = $.trim( $row.attr( 'data-area' ) || '' );

                if ( ! area ) {
                    return;
                }

                var district = null;

                $.each( alexMap, function ( key, areas ) {
                    if ( $.inArray( area, areas ) !== -1 ) {
                        district = key;
                        return false;
                    }
                } );

                if ( district ) {
                    $row.find( '.alex-district-select' ).val( district );
                    fillSubareas( $row, district, area );
                }
            } );
        } );
    } )( jQuery );
    </script>
    <?php
}

/* ─────────────────────────────────────────────────────────────────────────
 * واجهة الصفحة الرئيسية (Hero)
 *
 * نصوص أول قسم في الصفحة الرئيسية + صورة اختيارية، كلها من الباك إند.
 * القاعدة: أي خانة تسيبها فاضية يرجع القالب للنص الافتراضي بتاعها، ولو ما
 * اخترتش صورة يفضل الهيرو بعمود واحد بنفس تصميمه الحالي بالحرف.
 * ───────────────────────────────────────────────────────────────────────── */

function baraa_pharmacy_hero_fields()
{
    return array('badge', 'line1', 'line2', 'line3', 'desc');
}

function baraa_pharmacy_hero_settings()
{
    $stored = get_option('baraa_home_hero', array());

    if (!is_array($stored)) {
        $stored = array();
    }

    $defaults = array('image_id' => 0);

    foreach (baraa_pharmacy_hero_fields() as $field) {
        $defaults[$field . '_ar'] = '';
        $defaults[$field . '_en'] = '';
    }

    return wp_parse_args($stored, $defaults);
}

/**
 * النص المخصص لخانة واحدة، أو '' لو المسؤول ما كتبش حاجة — وفي الحالة دي
 * القالب يعرض النص الافتراضي بنفس ترجمته القديمة.
 */
function baraa_pharmacy_hero_text($field)
{
    $settings = baraa_pharmacy_hero_settings();
    $key      = $field . (baraa_pharmacy_is_english_visitor() ? '_en' : '_ar');

    return isset($settings[$key]) ? trim((string) $settings[$key]) : '';
}

/**
 * صورة الهيرو المختارة، أو null لو ما فيش صورة (فالتصميم يفضل زي ما هو).
 */
function baraa_pharmacy_hero_image()
{
    $settings = baraa_pharmacy_hero_settings();
    $id       = isset($settings['image_id']) ? (int) $settings['image_id'] : 0;

    if ($id <= 0) {
        return null;
    }

    $url = wp_get_attachment_image_url($id, 'large');

    if (!$url) {
        return null;
    }

    $alt = (string) get_post_meta($id, '_wp_attachment_image_alt', true);

    return array(
        'url' => $url,
        'alt' => '' !== $alt ? $alt : (string) get_the_title($id),
    );
}

function baraa_pharmacy_hero_admin_menu()
{
    add_submenu_page(
        'woocommerce',
        'واجهة الصفحة الرئيسية',
        'الصفحة الرئيسية',
        'manage_woocommerce',
        'baraa-hero',
        'baraa_pharmacy_hero_settings_page'
    );
}
add_action('admin_menu', 'baraa_pharmacy_hero_admin_menu');

function baraa_pharmacy_hero_register_setting()
{
    register_setting('baraa_hero_group', 'baraa_home_hero', array(
        'type'              => 'array',
        'sanitize_callback' => 'baraa_pharmacy_sanitize_hero',
    ));
}
add_action('admin_init', 'baraa_pharmacy_hero_register_setting');

function baraa_pharmacy_sanitize_hero($value)
{
    if (!is_array($value)) {
        return array();
    }

    $clean = array('image_id' => isset($value['image_id']) ? absint($value['image_id']) : 0);

    foreach (baraa_pharmacy_hero_fields() as $field) {
        foreach (array('_ar', '_en') as $suffix) {
            $key         = $field . $suffix;
            $raw         = isset($value[$key]) ? (string) $value[$key] : '';
            $clean[$key] = 'desc' === $field
                ? sanitize_textarea_field($raw)
                : sanitize_text_field($raw);
        }
    }

    return $clean;
}

function baraa_pharmacy_hero_settings_page()
{
    if (!current_user_can('manage_woocommerce')) {
        return;
    }

    wp_enqueue_media();

    $settings  = baraa_pharmacy_hero_settings();
    $image_id  = (int) $settings['image_id'];
    $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';

    $rows = array(
        'badge' => array('label' => 'نص الشارة الصغيرة فوق العنوان', 'textarea' => false),
        'line1' => array('label' => 'العنوان — السطر الأول', 'textarea' => false),
        'line2' => array('label' => 'العنوان — السطر المميز (بلون أساسي)', 'textarea' => false),
        'line3' => array('label' => 'العنوان — السطر الأخير', 'textarea' => false),
        'desc'  => array('label' => 'الوصف تحت العنوان', 'textarea' => true),
    );
    ?>
    <div class="wrap">
        <h1>واجهة الصفحة الرئيسية</h1>
        <p style="max-width:760px">
            عدّل نصوص أول قسم في الصفحة الرئيسية (الهيرو) واختر صورة له.
            أي خانة تسيبها فاضية هيرجع لها النص الافتراضي زي ما هو، ولو ما اخترتش صورة
            هيفضل الهيرو كما هو دلوقتي بنفس تصميمه وبدون صورة.
        </p>

        <form method="post" action="options.php">
            <?php settings_fields('baraa_hero_group'); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">صورة الهيرو</th>
                    <td>
                        <input type="hidden" id="baraa-hero-image-id" name="baraa_home_hero[image_id]" value="<?php echo esc_attr($image_id); ?>">
                        <div id="baraa-hero-image-preview" style="margin-bottom:8px">
                            <?php if ($image_url) : ?>
                                <img src="<?php echo esc_url($image_url); ?>" alt="" style="max-width:240px;height:auto;border-radius:8px;display:block">
                            <?php endif; ?>
                        </div>
                        <button type="button" class="button" id="baraa-hero-image-pick">اختيار صورة</button>
                        <button type="button" class="button" id="baraa-hero-image-clear" <?php echo $image_url ? '' : 'style="display:none"'; ?>>إزالة الصورة</button>
                        <p class="description">تُعرض بجانب النصوص. من غير صورة يبقى القسم بسيط زي ما هو دلوقتي.</p>
                    </td>
                </tr>

                <?php foreach ($rows as $field => $row) : ?>
                    <tr>
                        <th scope="row"><?php echo esc_html($row['label']); ?></th>
                        <td>
                            <?php if ($row['textarea']) : ?>
                                <textarea class="large-text" rows="3" dir="rtl" name="baraa_home_hero[<?php echo esc_attr($field); ?>_ar]" placeholder="بالعربي — اتركه فاضي للنص الافتراضي"><?php echo esc_textarea($settings[$field . '_ar']); ?></textarea>
                                <textarea class="large-text" rows="3" dir="ltr" name="baraa_home_hero[<?php echo esc_attr($field); ?>_en]" placeholder="English (optional)"><?php echo esc_textarea($settings[$field . '_en']); ?></textarea>
                            <?php else : ?>
                                <input type="text" class="large-text" dir="rtl" name="baraa_home_hero[<?php echo esc_attr($field); ?>_ar]" value="<?php echo esc_attr($settings[$field . '_ar']); ?>" placeholder="بالعربي — اتركه فاضي للنص الافتراضي">
                                <input type="text" class="large-text" dir="ltr" name="baraa_home_hero[<?php echo esc_attr($field); ?>_en]" value="<?php echo esc_attr($settings[$field . '_en']); ?>" placeholder="English (optional)">
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>

    <script>
    ( function () {
        var pick    = document.getElementById( 'baraa-hero-image-pick' );
        var clear   = document.getElementById( 'baraa-hero-image-clear' );
        var input   = document.getElementById( 'baraa-hero-image-id' );
        var preview = document.getElementById( 'baraa-hero-image-preview' );
        var frame   = null;

        if ( ! pick || ! input || ! clear || ! preview || ! window.wp || ! wp.media ) {
            return;
        }

        pick.addEventListener( 'click', function () {
            if ( ! frame ) {
                frame = wp.media( {
                    title:    'اختر صورة الهيرو',
                    button:   { text: 'استخدم الصورة' },
                    multiple: false
                } );

                frame.on( 'select', function () {
                    var attachment = frame.state().get( 'selection' ).first().toJSON();
                    var url = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;

                    input.value = attachment.id;
                    preview.innerHTML = '<img src="' + url + '" alt="" style="max-width:240px;height:auto;border-radius:8px;display:block">';
                    clear.style.display = '';
                } );
            }

            frame.open();
        } );

        clear.addEventListener( 'click', function () {
            input.value = '';
            preview.innerHTML = '';
            clear.style.display = 'none';
        } );
    } )();
    </script>
    <?php
}

/* ─────────────────────────────────────────────────────────────────────────
 * محرّك العروض (الحملات)
 *
 * حملة = فترة من الوقت لها خصم واسم ولون وصورة. القواعد المتفق عليها:
 *   • الخصم على المنتجات فقط — الشحن ما يتخصمش منه أبدًا.
 *   • منتجات الروشتة (Rx) مستثناة تمامًا من كل عرض.
 *   • الخصم يتراكم مع خصم محفظة العميل، مع سقف اختياري للخصم الكلي.
 *   • الشريط العلوي + ألوان الموقع + بلاطة العروض في الرئيسية، كلها من
 *     بيانات الحملة، وبدون حملة شغالة ما فيش أي تغيير في الشكل.
 * ───────────────────────────────────────────────────────────────────────── */

function baraa_pharmacy_offer_default_fields()
{
    return array(
        'name'          => '',
        'enabled'       => 'no',
        'starts'        => '',
        'ends'          => '',
        'discount_type' => 'percent',
        'amount'        => 0,
        'cap'           => 0,
        'scope'         => 'all',
        'terms'         => array(),
        'products'      => array(),
        'ribbon'        => 'yes',
        'ribbon_text'   => '',
        'popup'         => 'yes',
        'skin'          => 'no',
        'color1'        => '#ba1a1a',
        'color2'        => '#93000a',
        'image_id'      => 0,
        'hero_title'    => '',
        'hero_text'     => '',
    );
}

function baraa_pharmacy_offers()
{
    $stored = get_option('baraa_offers', null);

    return is_array($stored) ? $stored : array();
}

/**
 * "YYYY-MM-DDTHH:MM" القادم من خانة datetime-local ← "YYYY-MM-DD HH:MM:SS"
 * بصيغة توقيت الموقع نفسها اللي current_time() بترجعها، عشان المقارنة تبقى
 * نصية سليمة من غير أي التباس بين UTC وتوقيت الموقع.
 */
function baraa_pharmacy_offer_datetime($value)
{
    $value = trim(str_replace('T', ' ', (string) $value));

    if ('' === $value) {
        return '';
    }

    return 16 === strlen($value) ? $value . ':00' : $value;
}

/**
 * حالة الحملة: off (مقفولة) / ended (خلصت) / upcoming (لسه) / live (شغالة).
 */
function baraa_pharmacy_offer_phase($offer, $now = '')
{
    if (empty($offer['enabled']) || 'yes' !== $offer['enabled']) {
        return 'off';
    }

    $now    = '' === $now ? current_time('mysql') : $now;
    $starts = baraa_pharmacy_offer_datetime(isset($offer['starts']) ? $offer['starts'] : '');
    $ends   = baraa_pharmacy_offer_datetime(isset($offer['ends']) ? $offer['ends'] : '');

    if ('' !== $ends && $now > $ends) {
        return 'ended';
    }

    if ('' !== $starts && $now < $starts) {
        return 'upcoming';
    }

    return 'live';
}

/**
 * أول حملة شغالة دلوقتي (بالترتيب اللي ظاهر في صفحة العروض).
 */
function baraa_pharmacy_active_offer()
{
    static $cached = null;

    if (null !== $cached) {
        return $cached;
    }

    $cached = null;

    foreach (baraa_pharmacy_offers() as $offer) {
        if ('live' === baraa_pharmacy_offer_phase($offer)) {
            $cached = $offer;
            break;
        }
    }

    return $cached;
}

/**
 * أقرب حملة لسه ما بدأتش، لعرض "يبدأ بعد..." في الشريط العلوي.
 */
function baraa_pharmacy_next_offer()
{
    $best = null;

    foreach (baraa_pharmacy_offers() as $offer) {
        if ('upcoming' !== baraa_pharmacy_offer_phase($offer)) {
            continue;
        }

        if (null === $best || baraa_pharmacy_offer_datetime($offer['starts']) < baraa_pharmacy_offer_datetime($best['starts'])) {
            $best = $offer;
        }
    }

    return $best;
}

/**
 * منتج يتطلب روشتة طبية — يُستثنى من كل العروض.
 */
function baraa_pharmacy_product_requires_rx($product_id)
{
    $product_id = (int) $product_id;

    if ($product_id <= 0) {
        return false;
    }

    if ('yes' === get_post_meta($product_id, '_requires_prescription', true)) {
        return true;
    }

    return has_term('rx', 'product_tag', $product_id) || has_term('روشتة', 'product_tag', $product_id);
}

/**
 * هل المنتج داخل نطاق الحملة؟ (مع استثناء الروشتة دائمًا)
 */
function baraa_pharmacy_offer_covers_product($offer, $product_id)
{
    $product_id = (int) $product_id;

    if ($product_id <= 0 || baraa_pharmacy_product_requires_rx($product_id)) {
        return false;
    }

    $scope = isset($offer['scope']) ? $offer['scope'] : 'all';

    if ('categories' === $scope) {
        if (empty($offer['terms'])) {
            return false;
        }

        return has_term(array_map('intval', (array) $offer['terms']), 'product_cat', $product_id);
    }

    if ('products' === $scope) {
        if (empty($offer['products'])) {
            return false;
        }

        return in_array($product_id, array_map('intval', (array) $offer['products']), true);
    }

    return true;
}

/**
 * نص خصم الحملة كما يظهر في السلة والفاتورة.
 */
function baraa_pharmacy_offer_fee_label($offer)
{
    return baraa_pharmacy_text('خصم عرض', 'Offer discount')
        . ' ' . $offer['name']
        . ' (−' . baraa_pharmacy_offer_amount_label($offer) . ')';
}

function baraa_pharmacy_offer_amount_label($offer)
{
    if ('fixed' === $offer['discount_type']) {
        return html_entity_decode(wp_strip_all_tags(wc_price((float) $offer['amount'])), ENT_QUOTES, 'UTF-8');
    }

    return baraa_pharmacy_wallet_percent_label((float) $offer['amount']);
}

/**
 * قيمة خصم المحفظة المتوقعة للسلة الحالية، محسوبة بنفس معادلة دالة المحفظة.
 * مستخدمة فقط لحساب سقف الخصم الكلي.
 */
function baraa_pharmacy_wallet_amount_for_cart($cart)
{
    if (!is_user_logged_in() || !$cart instanceof WC_Cart) {
        return 0.0;
    }

    $percent = baraa_pharmacy_wallet_user_percent();

    if ($percent <= 0) {
        return 0.0;
    }

    // العرض بيُمنح مرة واحدة فقط، فلو اتحرق مايحجزش مكان في سقف الحملة.
    if (baraa_pharmacy_wallet_discount_used(get_current_user_id())) {
        return 0.0;
    }

    $base = (float) $cart->get_subtotal()
        - (float) $cart->get_discount_total()
        + (float) $cart->get_shipping_total();

    if ($base <= 0) {
        return 0.0;
    }

    return round($base * $percent / 100, wc_get_price_decimals());
}

/**
 * خصم الحملة على المنتجات الداخلة في نطاقها فقط (بدون الشحن).
 *
 * الترتيب مقصود: خصم العرض يُحسب أولًا على إجمالي المنتجات، ثم تتحسب نسبة
 * المحفظة على (المنتجات بعد خصم العرض + الشحن) فتتراكم النتيجتين — إلا لو
 * المسؤول حط سقف للخصم الكلي فساعتها يُقلَّص خصم العرض ليدخل تحت السقف.
 */
function baraa_pharmacy_apply_offer_discount($cart)
{
    if (is_admin() && !wp_doing_ajax()) {
        return;
    }

    if (!$cart instanceof WC_Cart || $cart->is_empty()) {
        return;
    }

    $offer = baraa_pharmacy_active_offer();

    if (!$offer || (float) $offer['amount'] <= 0) {
        return;
    }

    $eligible = 0.0;

    foreach ($cart->get_cart() as $item) {
        $variation_id = isset($item['variation_id']) ? (int) $item['variation_id'] : 0;
        $product_id   = isset($item['product_id']) ? (int) $item['product_id'] : 0;

        $covered = baraa_pharmacy_offer_covers_product($offer, $variation_id)
            || baraa_pharmacy_offer_covers_product($offer, $product_id);

        if (!$covered) {
            continue;
        }

        $eligible += (float) $item['line_subtotal'];
    }

    if ($eligible <= 0) {
        return;
    }

    $amount = 'fixed' === $offer['discount_type']
        ? min((float) $offer['amount'], $eligible)
        : $eligible * (float) $offer['amount'] / 100;

    // سقف الخصم الكلي (اختياري): نسبة من إجمالي المنتجات، وبعد حساب المحفظة.
    $cap = isset($offer['cap']) ? (float) $offer['cap'] : 0.0;

    if ($cap > 0) {
        $room = ((float) $cart->get_subtotal() * $cap / 100) - baraa_pharmacy_wallet_amount_for_cart($cart);
        $room = max(0.0, $room);
        $amount = min($amount, $room);
    }

    $amount = round($amount, wc_get_price_decimals());

    if ($amount <= 0) {
        return;
    }

    $cart->add_fee(baraa_pharmacy_offer_fee_label($offer), -1 * $amount, false);
}
add_action('woocommerce_cart_calculate_fees', 'baraa_pharmacy_apply_offer_discount', 9);

/**
 * منتجات بلاطة الحملة في الصفحة الرئيسية.
 */
function baraa_pharmacy_offer_products($offer, $limit = 4)
{
    if (!$offer) {
        return array();
    }

    $scope = isset($offer['scope']) ? $offer['scope'] : 'all';
    $args  = array(
        'status'  => 'publish',
        'limit'   => max($limit * 3, 12),
        'orderby' => 'popularity',
        'order'   => 'DESC',
    );

    if ('products' === $scope && !empty($offer['products'])) {
        $args['include'] = array_map('intval', (array) $offer['products']);
        $args['limit']   = max($limit * 3, 12);
        $args['orderby'] = 'post__in';
    } elseif ('categories' === $scope && !empty($offer['terms'])) {
        $slugs = array();

        foreach ((array) $offer['terms'] as $term_id) {
            $term = get_term((int) $term_id, 'product_cat');

            if ($term && !is_wp_error($term)) {
                $slugs[] = $term->slug;
            }
        }

        if ($slugs) {
            $args['category'] = $slugs;
        }
    }

    $found    = wc_get_products($args);
    $products = array();

    foreach ($found as $product) {
        if (!baraa_pharmacy_offer_covers_product($offer, $product->get_id())) {
            continue;
        }

        $products[] = $product;

        if (count($products) >= $limit) {
            break;
        }
    }

    return $products;
}

/**
 * بيانات الشريط العلوي: حملة شغالة، أو حملة قادمة نعرض "يبدأ بعد...".
 *
 * @return array{offer:array,phase:string,seconds:int}|null
 */
function baraa_pharmacy_offer_ribbon_data()
{
    $offer = baraa_pharmacy_active_offer();

    if ($offer && !empty($offer['ribbon']) && 'yes' === $offer['ribbon']) {
        $ends = baraa_pharmacy_offer_datetime($offer['ends']);

        return array(
            'offer'   => $offer,
            'phase'   => 'live',
            'seconds' => '' !== $ends ? max(0, strtotime($ends) - strtotime(current_time('mysql'))) : 0,
        );
    }

    $next = baraa_pharmacy_next_offer();

    if ($next && !empty($next['ribbon']) && 'yes' === $next['ribbon']) {
        $starts = baraa_pharmacy_offer_datetime($next['starts']);

        return array(
            'offer'   => $next,
            'phase'   => 'upcoming',
            'seconds' => '' !== $starts ? max(0, strtotime($starts) - strtotime(current_time('mysql'))) : 0,
        );
    }

    return null;
}

function baraa_pharmacy_render_offer_ribbon()
{
    $data = baraa_pharmacy_offer_ribbon_data();

    if (!$data) {
        return;
    }

    $offer = $data['offer'];
    $color1 = !empty($offer['color1']) ? $offer['color1'] : '#ba1a1a';
    $color2 = !empty($offer['color2']) ? $offer['color2'] : '#93000a';
    $label  = !empty($offer['ribbon_text']) ? $offer['ribbon_text'] : $offer['name'];
    $shop   = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
    $url    = add_query_arg('on_sale', '1', $shop);
    ?>
    <div class="w-full" style="background-image:linear-gradient(90deg,<?php echo esc_attr($color1); ?>,<?php echo esc_attr($color2); ?>)">
        <div class="w-full px-margin py-1.5 flex flex-wrap items-center justify-center gap-x-space-md gap-y-1 text-center text-white">
            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">local_fire_department</span>
            <span class="font-label-md text-label-md font-bold"><?php echo esc_html($label); ?></span>
            <?php if ($data['seconds'] > 0) : ?>
                <span
                    class="font-label-sm text-label-sm tabular-nums"
                    data-baraa-countdown
                    data-seconds="<?php echo esc_attr($data['seconds']); ?>"
                    data-done="<?php echo esc_attr(baraa_pharmacy_text('انتهى العرض', 'Offer ended')); ?>"
                ></span>
            <?php endif; ?>
            <a
                href="<?php echo esc_url($url); ?>"
                class="font-label-sm text-label-sm underline underline-offset-2 hover:no-underline"
            ><?php echo esc_html(baraa_pharmacy_text('اشتري العرض', 'Shop the offer')); ?></a>
        </div>
    </div>
    <?php
}

/**
 * العدّاد التنازلي: يعتمد على عدد الثواني المحسوب في PHP، فيبقى صحيحًا مهما
 * كانت منطقة العميل الزمنية. يُطبع مرة واحدة في الفوتر.
 */
function baraa_pharmacy_render_countdown_script()
{
    ?>
    <script id="baraa-countdown-script">
    ( function () {
        var nodes = document.querySelectorAll( '[data-baraa-countdown]' );

        if ( ! nodes.length ) {
            return;
        }

        var dayLabel   = <?php echo wp_json_encode(baraa_pharmacy_text('يوم', 'd')); ?>;
        var hourLabel  = <?php echo wp_json_encode(baraa_pharmacy_text('س', 'h')); ?>;
        var leftLabel  = <?php echo wp_json_encode(baraa_pharmacy_text('ينتهي خلال', 'Ends in')); ?>;

        function pad( n ) {
            return ( n < 10 ? '0' : '' ) + n;
        }

        function render() {
            nodes.forEach( function ( node ) {
                var left = parseInt( node.getAttribute( 'data-seconds' ), 10 ) || 0;

                if ( left <= 0 ) {
                    node.textContent = node.getAttribute( 'data-done' ) || '';
                    return;
                }

                var days  = Math.floor( left / 86400 );
                var hours = Math.floor( ( left % 86400 ) / 3600 );
                var mins  = Math.floor( ( left % 3600 ) / 60 );
                var secs  = left % 60;
                var body  = pad( hours ) + ':' + pad( mins ) + ':' + pad( secs );

                if ( days > 0 ) {
                    body = days + dayLabel + ' ' + body;
                }

                node.textContent = leftLabel + ': ' + body;
                node.setAttribute( 'data-seconds', left - 1 );
            } );
        }

        render();
        setInterval( render, 1000 );
    } )();
    </script>
    <?php
}
add_action('wp_footer', 'baraa_pharmacy_render_countdown_script', 99);

/**
 * ألوان سكين الموقع من الحملة الشغالة: نفس ألوان الهوية الأساسية في
 * إعدادات Tailwind، فالمتغير يتغير في كل الموقع بدون أي CSS إضافي.
 *
 * @return array{on:bool,primary:string,primary_container:string}
 */
function baraa_pharmacy_offer_skin()
{
    $offer = baraa_pharmacy_active_offer();

    if (!$offer || empty($offer['skin']) || 'yes' !== $offer['skin']) {
        return array('on' => false, 'primary' => '', 'primary_container' => '');
    }

    return array(
        'on'                => true,
        'primary'           => !empty($offer['color1']) ? $offer['color1'] : '#ba1a1a',
        'primary_container' => !empty($offer['color2']) ? $offer['color2'] : '#93000a',
    );
}

/* ─────────────────────────────────────────────────────────────────────────
 * صفحة العروض والمناسبات في الباك إند
 *
 * كل عرض عبارة عن صف كامل: الاسم، الفترة، الخصم، النطاق، الشريط العلوي،
 * ألوان الموقع، بانر البلاطة ونصوصها. أول عرض «شغال» في القائمة بالترتيب
 * هو اللي يُطبَّق فعلًا، والبقية تبدأ لوحدها عند دخول وقتها.
 * ───────────────────────────────────────────────────────────────────────── */

function baraa_pharmacy_offers_admin_menu()
{
    add_submenu_page(
        'woocommerce',
        'العروض والمناسبات',
        'العروض والمناسبات',
        'manage_woocommerce',
        'baraa-offers',
        'baraa_pharmacy_offers_settings_page'
    );
}
add_action('admin_menu', 'baraa_pharmacy_offers_admin_menu');

function baraa_pharmacy_offers_register_setting()
{
    register_setting('baraa_offers_group', 'baraa_offers', array(
        'type'              => 'array',
        'sanitize_callback' => 'baraa_pharmacy_sanitize_offers',
    ));
}
add_action('admin_init', 'baraa_pharmacy_offers_register_setting');

/**
 * قائمة التصنيفات مرة واحدة، عشان صفحة العروض ما تعملش نفس الاستعلام لكل حملة.
 */
function baraa_pharmacy_offer_category_options()
{
    static $options = null;

    if (null !== $options) {
        return $options;
    }

    $options = array();
    $terms   = get_terms(array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
    ));

    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            $options[(int) $term->term_id] = $term->name;
        }
    }

    return $options;
}

function baraa_pharmacy_sanitize_offers($value)
{
    if (!is_array($value)) {
        return array();
    }

    $offers   = array();
    $defaults = baraa_pharmacy_offer_default_fields();

    foreach ($value as $row) {
        if (!is_array($row)) {
            continue;
        }

        $name   = isset($row['name']) ? sanitize_text_field((string) $row['name']) : '';
        $amount = isset($row['amount']) ? max(0, (float) $row['amount']) : 0.0;

        // الصف الجديد اللي لسه فاضي مش عرض لسه.
        if ('' === $name && $amount <= 0) {
            continue;
        }

        $scope = isset($row['scope']) ? (string) $row['scope'] : 'all';

        if (!in_array($scope, array('all', 'categories', 'products'), true)) {
            $scope = 'all';
        }

        $ids = function ($list) {
            $clean = array();

            foreach ((array) $list as $id) {
                $id = absint($id);

                if ($id > 0 && !in_array($id, $clean, true)) {
                    $clean[] = $id;
                }
            }

            return $clean;
        };

        $color1 = isset($row['color1']) ? sanitize_hex_color((string) $row['color1']) : '';
        $color2 = isset($row['color2']) ? sanitize_hex_color((string) $row['color2']) : '';

        $offers[] = array(
            'name'          => '' !== $name ? $name : 'عرض',
            'enabled'       => empty($row['enabled']) ? 'no' : 'yes',
            'starts'        => isset($row['starts']) ? sanitize_text_field((string) $row['starts']) : '',
            'ends'          => isset($row['ends']) ? sanitize_text_field((string) $row['ends']) : '',
            'discount_type' => (isset($row['discount_type']) && 'fixed' === $row['discount_type']) ? 'fixed' : 'percent',
            'amount'        => $amount,
            'cap'           => isset($row['cap']) ? max(0, min(100, (float) $row['cap'])) : 0.0,
            'scope'         => $scope,
            'terms'         => $ids(isset($row['terms']) ? $row['terms'] : array()),
            'products'      => $ids(isset($row['products']) ? $row['products'] : array()),
            'ribbon'        => empty($row['ribbon']) ? 'no' : 'yes',
            'ribbon_text'   => isset($row['ribbon_text']) ? sanitize_text_field((string) $row['ribbon_text']) : '',
            'popup'         => empty($row['popup']) ? 'no' : 'yes',
            'skin'          => empty($row['skin']) ? 'no' : 'yes',
            'color1'        => $color1 ? $color1 : $defaults['color1'],
            'color2'        => $color2 ? $color2 : $defaults['color2'],
            'image_id'      => isset($row['image_id']) ? absint($row['image_id']) : 0,
            'hero_title'    => isset($row['hero_title']) ? sanitize_text_field((string) $row['hero_title']) : '',
            'hero_text'     => isset($row['hero_text']) ? sanitize_textarea_field((string) $row['hero_text']) : '',
        );
    }

    return $offers;
}

/**
 * قيمة خانة datetime-local من المخزَّن، سواء كان محفوظًا بصيغة الخانة
 * نفسها (YYYY-MM-DDTHH:MM) أو بصيغة قاعدة البيانات.
 */
function baraa_pharmacy_offer_input_value($value)
{
    $value = baraa_pharmacy_offer_datetime($value);

    return '' === $value ? '' : str_replace(' ', 'T', substr($value, 0, 16));
}

function baraa_pharmacy_render_offer_block($index, $offer = array())
{
    $offer = wp_parse_args(is_array($offer) ? $offer : array(), baraa_pharmacy_offer_default_fields());
    $base  = 'baraa_offers[' . $index . ']';

    $image_id  = (int) $offer['image_id'];
    $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';
    $terms     = array_map('intval', (array) $offer['terms']);
    $products  = array_map('intval', (array) $offer['products']);
    ?>
    <div class="baraa-offer" data-offer-index="<?php echo esc_attr($index); ?>" style="border:1px solid #dcdcde;background:#fff;border-radius:8px;padding:4px 16px 8px;margin-bottom:16px">

        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;border-bottom:1px solid #f0f0f1;padding:12px 0;margin-bottom:4px">
            <h2 style="margin:0;font-size:14px">
                عرض: <span class="baraa-offer-number"><?php echo esc_html(is_numeric($index) ? ((int) $index + 1) : ''); ?></span>
                <span class="baraa-offer-title-preview" style="color:#646970;font-weight:400;margin-inline-start:6px"><?php echo esc_html($offer['name']); ?></span>
            </h2>
            <label style="margin-inline-start:auto;display:flex;align-items:center;gap:6px">
                <input type="checkbox" name="<?php echo esc_attr($base); ?>[enabled]" value="yes" <?php checked('yes', $offer['enabled']); ?>>
                <strong>مفعّل</strong>
            </label>
            <button type="button" class="button baraa-remove-offer">حذف العرض</button>
        </div>

        <table class="form-table" role="presentation">

            <tr>
                <th scope="row"><label>اسم العرض</label></th>
                <td>
                    <input type="text" class="regular-text baraa-offer-name" name="<?php echo esc_attr($base); ?>[name]" value="<?php echo esc_attr($offer['name']); ?>" placeholder="عرض اليوم / الجمعة البيضاء / الجمعة السوداء">
                    <p class="description">الاسم يظهر في الشريط العلوي وفي سطر الخصم بالسلة والفاتورة.</p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label>فترة العرض</label></th>
                <td>
                    <label>يبدأ: <input type="datetime-local" name="<?php echo esc_attr($base); ?>[starts]" value="<?php echo esc_attr(baraa_pharmacy_offer_input_value($offer['starts'])); ?>"></label>
                    &nbsp;&nbsp;
                    <label>ينتهي: <input type="datetime-local" name="<?php echo esc_attr($base); ?>[ends]" value="<?php echo esc_attr(baraa_pharmacy_offer_input_value($offer['ends'])); ?>"></label>
                    <p class="description">سيبه فاضي = يبدأ حالًا. خلّي «ينتهي» فاضي = يعمل من غير عدّاد تنازلي.</p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label>الخصم</label></th>
                <td>
                    <select name="<?php echo esc_attr($base); ?>[discount_type]">
                        <option value="percent" <?php selected('percent', $offer['discount_type']); ?>>نسبة %</option>
                        <option value="fixed" <?php selected('fixed', $offer['discount_type']); ?>>مبلغ ثابت</option>
                    </select>
                    <input type="number" step="0.01" min="0" style="width:110px" name="<?php echo esc_attr($base); ?>[amount]" value="<?php echo esc_attr($offer['amount']); ?>">

                    &nbsp;&nbsp;الحد الأقصى للخصم الكلي:
                    <input type="number" step="0.01" min="0" max="100" style="width:90px" name="<?php echo esc_attr($base); ?>[cap]" value="<?php echo esc_attr($offer['cap']); ?>">
                    <span>%</span>
                    <p class="description">الخصم على المنتجات فقط، والشحن ما يتخصمش منه. الحد الأقصى يحمي هامش الربح لو خصم العرض تراكم مع خصم محفظة العميل — سيبه صفر يعني بلا سقف.</p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label>نطاق الخصم</label></th>
                <td>
                    <select class="baraa-offer-scope" name="<?php echo esc_attr($base); ?>[scope]">
                        <option value="all" <?php selected('all', $offer['scope']); ?>>كل المنتجات</option>
                        <option value="categories" <?php selected('categories', $offer['scope']); ?>>تصنيفات محددة</option>
                        <option value="products" <?php selected('products', $offer['scope']); ?>>منتجات محددة</option>
                    </select>

                    <div class="baraa-offer-scope-categories" style="margin-top:10px">
                        <select multiple size="6" style="min-width:320px" name="<?php echo esc_attr($base); ?>[terms][]">
                            <?php foreach (baraa_pharmacy_offer_category_options() as $term_id => $term_name) : ?>
                                <option value="<?php echo esc_attr($term_id); ?>" <?php selected(in_array($term_id, $terms, true)); ?>><?php echo esc_html($term_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">اختار تصنيف واحد أو أكتر بـ Ctrl. تُطبَّق على كل منتجات التصنيف ما عدا منتجات الروشتة.</p>
                    </div>

                    <div class="baraa-offer-scope-products" style="margin-top:10px">
                        <select class="wc-product-search" multiple="multiple" style="width:100%;max-width:520px" data-placeholder="ابحث عن منتج بالاسم..." name="<?php echo esc_attr($base); ?>[products][]">
                            <?php
                            foreach ($products as $product_id) {
                                $product = wc_get_product($product_id);

                                if ($product) {
                                    printf(
                                        '<option value="%d" selected>%s</option>',
                                        (int) $product_id,
                                        esc_html(wp_strip_all_tags($product->get_formatted_name()))
                                    );
                                }
                            }
                            ?>
                        </select>
                        <p class="description">ابحث بالاسم واختار المنتجات الداخلة في العرض.</p>
                    </div>

                    <p class="description">منتجات الروشتة (Rx) مستثناة تلقائيًا من كل العروض.</p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label>الشريط العلوي</label></th>
                <td>
                    <label><input type="checkbox" name="<?php echo esc_attr($base); ?>[ribbon]" value="yes" <?php checked('yes', $offer['ribbon']); ?>> إظهار شريط ملون فوق الهيدر مع عدّاد</label>
                    <br><br>
                    <input type="text" class="regular-text" name="<?php echo esc_attr($base); ?>[ribbon_text]" value="<?php echo esc_attr($offer['ribbon_text']); ?>" placeholder="نص الشريط — سيبه فاضي يظهر اسم العرض">
                </td>
            </tr>

            <tr>
                <th scope="row"><label>نافذة الترحيب</label></th>
                <td>
                    <label><input type="checkbox" name="<?php echo esc_attr($base); ?>[popup]" value="yes" <?php checked('yes', $offer['popup']); ?>> إظهار نافذة بالعرض مرة واحدة لكل زائر</label>
                    <p class="description">
                        النافذة تظهر بعد لحظة من فتح الموقع، وفيها صورة العرض وعدّاده وزرار الشراء.
                        ما تظهرش تاني لنفس الزائر في نفس العرض، وبتتخطى صفحات السلة والدفع والحساب.
                        العنوان والنص والصورة بتاعتها هي نفس بيانات البلاطة اللي فوق.
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label>ألوان الموقع</label></th>
                <td>
                    <label><input type="checkbox" name="<?php echo esc_attr($base); ?>[skin]" value="yes" <?php checked('yes', $offer['skin']); ?>> تغيير ألوان الموقع كامل مؤقتًا مع هذا العرض</label>
                    <br><br>
                    <label>اللون الأساسي <input type="color" name="<?php echo esc_attr($base); ?>[color1]" value="<?php echo esc_attr($offer['color1']); ?>"></label>
                    &nbsp;&nbsp;
                    <label>اللون الثانوي <input type="color" name="<?php echo esc_attr($base); ?>[color2]" value="<?php echo esc_attr($offer['color2']); ?>"></label>
                    <p class="description">الألوان دي هي نفسها المستخدمة في الشريط العلوي. من غير تفعيل السكين يبقى الشريط بس هو اللي ياخد اللون.</p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label>بانر العرض</label></th>
                <td>
                    <div class="baraa-offer-image-preview" style="margin-bottom:8px">
                        <?php if ($image_url) : ?>
                            <img src="<?php echo esc_url($image_url); ?>" alt="" style="max-width:260px;height:auto;border-radius:8px;display:block">
                        <?php endif; ?>
                    </div>
                    <input type="hidden" class="baraa-offer-image-id" name="<?php echo esc_attr($base); ?>[image_id]" value="<?php echo esc_attr($image_id); ?>">
                    <button type="button" class="button baraa-offer-image-pick">اختيار صورة</button>
                    <button type="button" class="button baraa-offer-image-clear" <?php echo $image_url ? '' : 'style="display:none"'; ?>>إزالة</button>
                    <p class="description">تظهر في بلاطة «عروض اليوم» بالصفحة الرئيسية. بدون صورة تبقى البلاطة بشكلها الحالي.</p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label>بلاطة الرئيسية</label></th>
                <td>
                    <input type="text" class="large-text" dir="rtl" name="<?php echo esc_attr($base); ?>[hero_title]" value="<?php echo esc_attr($offer['hero_title']); ?>" placeholder="عنوان البلاطة — سيبه فاضي للعنوان الحالي">
                    <textarea class="large-text" rows="2" dir="rtl" name="<?php echo esc_attr($base); ?>[hero_text]" placeholder="نص البلاطة — سيبه فاضي للنص الحالي"><?php echo esc_textarea($offer['hero_text']); ?></textarea>
                </td>
            </tr>

        </table>
    </div>
    <?php
}

function baraa_pharmacy_offers_settings_page()
{
    if (!current_user_can('manage_woocommerce')) {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_script('wc-enhanced-select');
    wp_enqueue_style('woocommerce_admin_styles');

    $offers  = baraa_pharmacy_offers();
    $next    = count($offers);
    $now     = current_time('mysql');
    ?>
    <div class="wrap">
        <h1>العروض والمناسبات</h1>
        <p style="max-width:820px">
            أنشئ عرض اليوم أو الجمعة البيضاء أو الجمعة السوداء وحدّد بدايته ونهايته،
            والنظام يفعّله ويفسّله لوحده في وقته. أول عرض «مفعّل» وواقعه في فترته هو اللي يُطبَّق
            (الترتيب من فوق لتحت)، ولو في أكتر من عرض في نفس الوقت يبقى الأول فقط.
        </p>
        <p style="max-width:820px">
            الخصم يُحسب على المنتجات فقط (الشحن مستثنى)، ومنتجات الروشتة مستثناة تمامًا،
            والخصم يتراكم مع كاش باك المحفظة إلا لو حدّدت «الحد الأقصى للخصم الكلي».
        </p>

        <form method="post" action="options.php">
            <?php settings_fields('baraa_offers_group'); ?>

            <div id="baraa-offers-list" data-offer-seq="<?php echo esc_attr($next); ?>">
                <?php
                foreach ($offers as $i => $offer) {
                    baraa_pharmacy_render_offer_block($i, $offer);
                    $phase = baraa_pharmacy_offer_phase($offer, $now);

                    $labels = array(
                        'live'     => 'شغال دلوقتي',
                        'upcoming' => 'هيبدأ في وقته',
                        'ended'    => 'خلص',
                        'off'      => 'مقفول',
                    );
                    printf(
                        '<p style="margin:-12px 0 20px;color:%s">↳ الحالة: <strong>%s</strong></p>',
                        'live' === $phase ? '#008a20' : '#646970',
                        esc_html($labels[$phase])
                    );
                }
                ?>
            </div>

            <p>
                <button type="button" class="button button-secondary" id="baraa-add-offer">+ إضافة عرض جديد</button>
            </p>

            <?php submit_button('حفظ العروض'); ?>
        </form>

        <!-- القالب بره الفورم عشان خاناته ما تتبعتش مع الحفظ -->
        <div id="baraa-offer-template" hidden>
            <?php baraa_pharmacy_render_offer_block('__O__', array()); ?>
        </div>
    </div>

    <script>
    ( function () {
        var list = document.getElementById( 'baraa-offers-list' );
        var tpl  = document.getElementById( 'baraa-offer-template' );

        if ( ! list || ! tpl ) {
            return;
        }

        var seq = parseInt( list.getAttribute( 'data-offer-seq' ), 10 ) || 0;

        function renumber() {
            list.querySelectorAll( '.baraa-offer' ).forEach( function ( block, i ) {
                block.querySelector( '.baraa-offer-number' ).textContent = i + 1;
            } );
        }

        // إظهار حقول النطاق المناسبة للاختيار الحالي.
        function syncScope( block ) {
            var scope = block.querySelector( '.baraa-offer-scope' ).value;

            block.querySelector( '.baraa-offer-scope-categories' ).style.display = ( scope === 'categories' ) ? '' : 'none';
            block.querySelector( '.baraa-offer-scope-products' ).style.display  = ( scope === 'products' )   ? '' : 'none';
        }

        function initBlock( block ) {
            syncScope( block );

            if ( window.jQuery && jQuery.fn.select2 ) {
                jQuery( document.body ).trigger( 'wc-enhanced-select-init' );
            }
        }

        list.querySelectorAll( '.baraa-offer' ).forEach( initBlock );

        document.getElementById( 'baraa-add-offer' ).addEventListener( 'click', function () {
            var block = tpl.querySelector( '.baraa-offer' ).cloneNode( true );

            block.querySelectorAll( '[name]' ).forEach( function ( input ) {
                input.name = input.name.replace( /__O__/g, seq );
            } );

            block.setAttribute( 'data-offer-index', seq );
            seq++;

            list.appendChild( block );
            initBlock( block );
            renumber();
        } );

        // منتقي صورة واحد بيتحرك للعرض اللي اتداس على زراره.
        var frame = null;
        var target = null;

        list.addEventListener( 'click', function ( event ) {
            var button = event.target;

            if ( button.classList.contains( 'baraa-offer-image-pick' ) ) {
                event.preventDefault();
                target = button.closest( '.baraa-offer' );

                if ( ! frame ) {
                    frame = wp.media( {
                        title:    'اختر صورة العرض',
                        button:   { text: 'استخدم الصورة' },
                        multiple: false
                    } );

                    frame.on( 'select', function () {
                        var attachment = frame.state().get( 'selection' ).first().toJSON();
                        var url = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
                        var current = target || list.querySelector( '.baraa-offer' );

                        if ( ! current ) {
                            return;
                        }

                        current.querySelector( '.baraa-offer-image-id' ).value = attachment.id;
                        current.querySelector( '.baraa-offer-image-preview' ).innerHTML =
                            '<img src="' + url + '" alt="" style="max-width:260px;height:auto;border-radius:8px;display:block">';
                        current.querySelector( '.baraa-offer-image-clear' ).style.display = '';
                    } );
                }

                frame.open();
                return;
            }

            if ( button.classList.contains( 'baraa-offer-image-clear' ) ) {
                event.preventDefault();
                var block = button.closest( '.baraa-offer' );

                block.querySelector( '.baraa-offer-image-id' ).value = '';
                block.querySelector( '.baraa-offer-image-preview' ).innerHTML = '';
                button.style.display = 'none';
                return;
            }

            if ( button.classList.contains( 'baraa-remove-offer' ) ) {
                event.preventDefault();

                if ( window.confirm( 'حذف هذا العرض؟' ) ) {
                    button.closest( '.baraa-offer' ).remove();
                    renumber();
                }
            }
        } );

        list.addEventListener( 'change', function ( event ) {
            if ( event.target.classList.contains( 'baraa-offer-scope' ) ) {
                syncScope( event.target.closest( '.baraa-offer' ) );
            }

            if ( event.target.classList.contains( 'baraa-offer-name' ) ) {
                event.target.closest( '.baraa-offer' ).querySelector( '.baraa-offer-title-preview' ).textContent = event.target.value;
            }
        } );

        renumber();
    } )();
    </script>
    <?php
}

/* ─────────────────────────────────────────────────────────────────────────
 * نافذة عرض الحملة
 *
 * تظهر مرة واحدة لكل زائر (localStorage مفتاحه اسم العرض ووقت نهايته)، وبتتخطى
 * صفحات السلة والدفع والحساب عشان ما تعطّلش الشراء. العنوان والنص والصورة
 * والعدّاد كلهم من نفس بيانات الحملة، ومن غير حملة ما فيش أي حاجة تظهر.
 * ───────────────────────────────────────────────────────────────────────── */

function baraa_pharmacy_offer_popup_data()
{
    $offer = baraa_pharmacy_active_offer();

    if (!$offer || empty($offer['popup']) || 'yes' !== $offer['popup']) {
        return null;
    }

    if (function_exists('is_cart') && (is_cart() || is_checkout() || is_account_page())) {
        return null;
    }

    $ends    = baraa_pharmacy_offer_datetime($offer['ends']);
    $seconds = '' !== $ends ? max(0, strtotime($ends) - strtotime(current_time('mysql'))) : 0;
    $image   = (int) $offer['image_id'] > 0 ? (string) wp_get_attachment_image_url((int) $offer['image_id'], 'large') : '';
    $shop    = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');

    return array(
        'key'     => 'baraa-offer-popup:' . $offer['name'] . '|' . $offer['ends'],
        'name'    => $offer['name'],
        'title'   => '' !== $offer['hero_title'] ? $offer['hero_title'] : $offer['name'],
        'text'    => $offer['hero_text'],
        'amount'  => (float) $offer['amount'] > 0 ? baraa_pharmacy_offer_amount_label($offer) : '',
        'image'   => $image,
        'seconds' => $seconds,
        'color1'  => !empty($offer['color1']) ? $offer['color1'] : '#ba1a1a',
        'color2'  => !empty($offer['color2']) ? $offer['color2'] : '#93000a',
        'url'     => add_query_arg('on_sale', '1', $shop),
    );
}

function baraa_pharmacy_render_offer_popup()
{
    $data = baraa_pharmacy_offer_popup_data();

    if (!$data) {
        return;
    }
    ?>
    <div
        id="baraa-offer-popup"
        class="fixed inset-0 z-[70] hidden items-center justify-center p-space-md"
        role="dialog"
        aria-modal="true"
        aria-label="<?php echo esc_attr($data['name']); ?>"
    >
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" data-baraa-popup-close></div>

        <div class="relative w-full max-w-md max-h-[90vh] overflow-y-auto rounded-xl bg-surface-container-lowest shadow-2xl">

            <!-- شريط العنوان بلون الحملة -->
            <div class="flex items-center justify-between gap-space-sm px-space-md py-space-sm text-white" style="background-image:linear-gradient(90deg,<?php echo esc_attr($data['color1']); ?>,<?php echo esc_attr($data['color2']); ?>)">
                <span class="flex items-center gap-1.5 font-label-md text-label-md font-bold">
                    <span class="material-symbols-outlined text-[18px]" aria-hidden="true">local_fire_department</span>
                    <?php echo esc_html($data['name']); ?>
                </span>
                <button
                    type="button"
                    class="w-8 h-8 rounded-full hover:bg-white/20 flex items-center justify-center transition-colors"
                    aria-label="<?php esc_attr_e('إغلاق', 'baraa-pharmacy'); ?>"
                    data-baraa-popup-close
                >
                    <span class="material-symbols-outlined text-[20px]" aria-hidden="true">close</span>
                </button>
            </div>

            <?php if ('' !== $data['image']) : ?>
                <img
                    src="<?php echo esc_url($data['image']); ?>"
                    alt="<?php echo esc_attr($data['name']); ?>"
                    class="w-full h-auto max-h-[240px] object-cover"
                    loading="lazy"
                    decoding="async"
                >
            <?php endif; ?>

            <div class="p-space-lg space-y-space-md text-center">
                <?php if ('' !== $data['amount']) : ?>
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-error-container text-on-error-container font-label-md text-label-md font-bold">
                        <?php echo esc_html(baraa_pharmacy_text('خصم ', 'Save ') . $data['amount']); ?>
                    </span>
                <?php endif; ?>

                <h2 class="font-headline-md text-headline-md text-on-surface font-bold"><?php echo esc_html($data['title']); ?></h2>

                <?php if ('' !== $data['text']) : ?>
                    <p class="font-body-md text-body-md text-on-surface-variant"><?php echo esc_html($data['text']); ?></p>
                <?php endif; ?>

                <?php if ($data['seconds'] > 0) : ?>
                    <p
                        class="font-title-md text-title-md text-primary font-bold tabular-nums"
                        data-baraa-countdown
                        data-seconds="<?php echo esc_attr($data['seconds']); ?>"
                        data-done="<?php echo esc_attr(baraa_pharmacy_text('انتهى العرض', 'Offer ended')); ?>"
                    ></p>
                <?php endif; ?>

                <a
                    href="<?php echo esc_url($data['url']); ?>"
                    class="w-full inline-flex items-center justify-center gap-2 px-space-lg py-3 rounded-lg bg-primary text-on-primary font-label-md text-label-md font-bold hover:bg-primary-container transition-colors"
                    data-baraa-popup-close
                >
                    <span class="material-symbols-outlined text-[18px]" aria-hidden="true">shopping_bag</span>
                    <span><?php echo esc_html(baraa_pharmacy_text('اشتري العرض', 'Shop the offer')); ?></span>
                </a>
            </div>
        </div>
    </div>

    <script>
    ( function () {
        var popup = document.getElementById( 'baraa-offer-popup' );

        if ( ! popup ) {
            return;
        }

        var key = <?php echo wp_json_encode($data['key']); ?>;

        function stored() {
            try {
                return window.localStorage.getItem( key );
            } catch ( e ) {
                return null;
            }
        }

        if ( stored() ) {
            return;
        }

        function close() {
            popup.classList.add( 'hidden' );
            popup.classList.remove( 'flex' );

            try {
                window.localStorage.setItem( key, '1' );
            } catch ( e ) {}
        }

        setTimeout( function () {
            popup.classList.remove( 'hidden' );
            popup.classList.add( 'flex' );
        }, 1200 );

        popup.addEventListener( 'click', function ( event ) {
            if ( event.target.closest( '[data-baraa-popup-close]' ) ) {
                close();
            }
        } );

        document.addEventListener( 'keydown', function ( event ) {
            if ( 'Escape' === event.key ) {
                close();
            }
        } );
    } )();
    </script>
    <?php
}
add_action('wp_footer', 'baraa_pharmacy_render_offer_popup', 98);
