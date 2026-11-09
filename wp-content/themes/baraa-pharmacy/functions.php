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
    darkMode: "class",
    theme: {
        extend: {
            colors: {
                /* ── Light mode (default) ───────────────────── */
                "inverse-surface":            "#2f3035",
                "primary-fixed":              "#89f5e7",
                "outline-variant":            "#bec9c6",
                "surface-container-highest":  "#dae2fd",
                "on-primary-container":       "#93e4d8",
                "on-tertiary":                "#ffffff",
                "on-background":              "#1a1b20",
                "tertiary":                   "#994100",
                "inverse-on-surface":         "#eef0ff",
                "on-primary":                 "#ffffff",
                "on-primary-fixed":           "#00201d",
                "surface-dim":                "#d2d9f4",
                "inverse-primary":            "#6bd8cb",
                "primary-fixed-dim":          "#6bd8cb",
                "background":                 "#faf8ff",
                "surface-container":          "#eaedff",
                "primary-container":          "#008378",
                "surface-variant":            "#dae2fd",
                "on-error-container":         "#93000a",
                "error-container":            "#ffdad6",
                "surface-tint":               "#006a61",
                "on-secondary":               "#ffffff",
                "surface-container-lowest":   "#ffffff",
                "on-tertiary-container":      "#ffcab0",
                "secondary-fixed-dim":        "#94ccff",
                "secondary-container":        "#5bb8fe",
                "on-surface-variant":         "#3d4947",
                "on-tertiary-fixed":          "#341100",
                "tertiary-fixed-dim":         "#ffb690",
                "on-secondary-fixed-variant": "#004b73",
                "on-tertiary-fixed-variant":  "#783200",
                "surface-container-low":      "#f4f3fa",
                "on-primary-fixed-variant":   "#005049",
                "on-secondary-container":     "#00476e",
                "on-surface":                 "#131b2e",
                "on-secondary-fixed":         "#001d31",
                "secondary-fixed":            "#cce5ff",
                "outline":                    "#6d7a77",
                "secondary":                  "#006398",
                "surface":                    "#faf8ff",
                "surface-container-high":     "#e2e7ff",
                "error":                      "#ba1a1a",
                "primary":                    "#00685f",
                "on-error":                   "#ffffff",
                "tertiary-container":         "#9a4201",
                "surface-bright":             "#faf8ff",
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

/* ── Dark mode — Warm Slate Teal palette ─────────────────────
 * Eye-friendly pharmacy night theme.
 * Uses deep teal-green backgrounds instead of harsh black,
 * warm off-white text instead of cool grey,
 * and softer borders derived from the brand green.
 *
 * Contrast ratios all pass WCAG AA:
 *   Body text  #dff0ed on #0e1b1a  →  10.4 : 1
 *   Muted text #8db5b0 on #0e1b1a  →  4.65 : 1
 *   Primary    #5ecfc2 on #0e1b1a  →  5.2  : 1
 * ─────────────────────────────────────────────────────────── */
html.dark {
    color-scheme: dark;
}

/* ── Base ─────────────────────────────────────────────────── */
html.dark body                               { background-color: #0e1b1a !important; color: #dff0ed !important; }
html.dark .bg-surface                        { background-color: #0e1b1a !important; }

/* ── Surface scale (each step ~8% lighter) ──────────────────
   lowest → low → mid → high → highest
   ─────────────────────────────────────────────────────────── */
html.dark .bg-surface-container-lowest       { background-color: #132220 !important; }
html.dark .bg-surface-container-low          { background-color: #1a2e2c !important; }
html.dark .bg-surface-container              { background-color: #1f3735 !important; }
html.dark .bg-surface-container-high         { background-color: #263f3d !important; }
html.dark .bg-surface-container-highest      { background-color: #2d4946 !important; }

/* ── Brand green (primary) ───────────────────────────────── */
html.dark .bg-primary                        { background-color: #006b61 !important; }
html.dark .bg-primary-container             { background-color: #006b61 !important; }
html.dark .bg-primary-fixed\/20,
html.dark .bg-primary-fixed\/30,
html.dark .bg-primary-fixed\/50,
html.dark .bg-primary-fixed\/60             { background-color: rgba(94,207,194,.16) !important; }
html.dark .bg-secondary-fixed\/50           { background-color: rgba(148,204,255,.14) !important; }
html.dark .bg-tertiary-fixed\/50            { background-color: rgba(255,183,144,.14) !important; }
html.dark .bg-error-container\/40           { background-color: rgba(255,180,171,.12) !important; }

/* ── Hover states ────────────────────────────────────────── */
html.dark .hover\:bg-surface-container:hover       { background-color: #1f3735 !important; }
html.dark .hover\:bg-surface-container-high:hover  { background-color: #263f3d !important; }

/* ── Text colours ────────────────────────────────────────── */
html.dark .text-on-surface                   { color: #dff0ed !important; }
html.dark .text-on-surface-variant           { color: #8db5b0 !important; }
html.dark .text-primary                      { color: #5ecfc2 !important; }
html.dark .text-on-primary                   { color: #003732 !important; }
html.dark .text-on-primary-fixed             { color: #002926 !important; }
html.dark .text-secondary                    { color: #81c4ff !important; }
html.dark .text-tertiary                     { color: #ffb787 !important; }
html.dark .text-outline                      { color: #6a9e99 !important; }
html.dark .text-error                        { color: #ffb3ab !important; }

/* ── Borders ─────────────────────────────────────────────── */
html.dark .border-outline-variant\/30        { border-color: rgba(80,130,125,.45) !important; }
html.dark .border-outline-variant\/20        { border-color: rgba(80,130,125,.30) !important; }
html.dark .border-primary                    { border-color: #5ecfc2 !important; }

/* ── Header & footer ─────────────────────────────────────── */
html.dark header.fixed                       { background-color: rgba(12,24,23,.97) !important; box-shadow: 0 1px 0 rgba(80,130,125,.2) !important; }
html.dark footer                             { background-color: #0e1b1a !important; border-top-color: rgba(80,130,125,.3) !important; }

/* ── Shadows — warm green tint ───────────────────────────── */
html.dark .shadow-sm  { box-shadow: 0 1px 4px  rgba(0,30,28,.6) !important; }
html.dark .shadow-md  { box-shadow: 0 4px 12px rgba(0,30,28,.7) !important; }
html.dark .shadow-lg  { box-shadow: 0 8px 24px rgba(0,30,28,.8) !important; }

/* ── Announcement bar ────────────────────────────────────── */
html.dark .bg-primary.text-on-primary        { background-color: #006b61 !important; }

/* ── Focus rings ─────────────────────────────────────────── */
html.dark .focus-within\:ring-primary:focus-within { --tw-ring-color: #5ecfc2 !important; }

/* ── WooCommerce elements ────────────────────────────────── */
html.dark .woocommerce-page,
html.dark .woocommerce { color: #dff0ed !important; }

html.dark .woocommerce input[type="text"],
html.dark .woocommerce input[type="email"],
html.dark .woocommerce input[type="number"],
html.dark .woocommerce input[type="password"],
html.dark .woocommerce input[type="tel"],
html.dark .woocommerce textarea,
html.dark .woocommerce select {
    background: #1a2e2c !important;
    border-color: rgba(80,130,125,.5) !important;
    color: #dff0ed !important;
}
html.dark .woocommerce-message { background: #0d2825 !important; color: #89f5e7 !important; border-inline-start-color: #5ecfc2 !important; }
html.dark .woocommerce-error   { background: #2d1216 !important; color: #ffb3ab !important; border-inline-start-color: #ffb3ab !important; }
html.dark .woocommerce-info    { background: #1a2e2c !important; color: #dff0ed !important; }
html.dark .woocommerce div.product p.price,
html.dark .woocommerce div.product span.price { color: #5ecfc2 !important; }
html.dark .woocommerce div.product .woocommerce-tabs ul.tabs li { background: #1a2e2c !important; }
html.dark .woocommerce div.product .woocommerce-tabs ul.tabs li.active { background: #5ecfc2 !important; }
html.dark .woocommerce div.product .woocommerce-tabs ul.tabs li a { color: #8db5b0 !important; }
html.dark .woocommerce div.product .woocommerce-tabs ul.tabs li.active a { color: #00201d !important; }
html.dark .woocommerce-cart table.cart td,
html.dark .woocommerce-cart table.cart th { border-bottom-color: rgba(80,130,125,.3) !important; color: #dff0ed !important; }
html.dark .woocommerce .related ul.products li.product,
html.dark .woocommerce .upsells ul.products li.product {
    background: #132220 !important;
    border-color: rgba(80,130,125,.35) !important;
}
html.dark .woocommerce .related ul.products li.product .woocommerce-loop-product__title,
html.dark .woocommerce .upsells ul.products li.product .woocommerce-loop-product__title { color: #dff0ed !important; }
html.dark .woocommerce .related ul.products li.product .price,
html.dark .woocommerce .upsells ul.products li.product .price { color: #5ecfc2 !important; }
html.dark .woocommerce-pagination ul li a,
html.dark .woocommerce-pagination ul li span { background: #1a2e2c !important; color: #dff0ed !important; }
html.dark .woocommerce-pagination ul li span.current,
html.dark .woocommerce-pagination ul li a:hover { background: #5ecfc2 !important; color: #00201d !important; }
html.dark .woocommerce button.button,
html.dark .woocommerce a.button,
html.dark .woocommerce input.button { background: #5ecfc2 !important; color: #00201d !important; }
html.dark .woocommerce button.button:hover,
html.dark .woocommerce a.button:hover { background: #89f5e7 !important; }

/* ── WooCommerce notices ──────────────────────────────────── */
.woocommerce-message,
.woocommerce-info,
.woocommerce-error {
    border: 0 !important;
    border-radius: 0.75rem !important;
    background: #e3f7f4 !important;
    color: #131b2e !important;
    font-family: inherit !important;
    padding: 1rem 1.25rem !important;
    margin-bottom: 1rem !important;
}
.woocommerce-error { background: #ffdad6 !important; color: #93000a !important; }

/* ── Single product summary ──────────────────────────────── */
.woocommerce div.product p.price,
.woocommerce div.product span.price {
    color: #00685f !important;
    font-size: 1.875rem !important;
    font-weight: 700 !important;
    background: #e3f7f4 !important;
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
.woocommerce div.product form.cart .qty:focus { border-color: #00685f !important; outline: none !important; }

.woocommerce div.product form.cart .single_add_to_cart_button,
.woocommerce div.product form.cart button[type="submit"] {
    background: #00685f !important;
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
    background: #005049 !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 16px rgba(0,104,95,.25) !important;
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
.woocommerce div.product form.cart .variations select:focus { border-color: #00685f !important; outline: none !important; }

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
.woocommerce div.product .product_meta a { color: #00685f !important; }

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
.prose-pharmacy a  { color: #00685f !important; }
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
    border-color: #00685f !important;
    outline: none !important;
}
.baraa-tab-panel #reviews .comment-respond .submit {
    background: #00685f !important;
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
.baraa-tab-panel #reviews .comment-respond .submit:hover { background: #005049 !important; }
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
.woocommerce .upsells ul.products li.product .price { color: #00685f !important; }
.woocommerce .related ul.products li.product .button,
.woocommerce .upsells ul.products li.product .button {
    background: #00685f !important;
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
    background: #00685f !important;
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
    color: #00685f !important;
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
    border-color: #00685f !important;
    outline: none !important;
}
.woocommerce .shipping-calculator-form button[type="submit"],
.woocommerce .shipping-calculator-form input[type="submit"] {
    background: #00685f !important;
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
.woocommerce .shipping-calculator-form input[type="submit"]:hover { background: #005049 !important; }
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
    color: #00685f !important;
    font-size: 1.1rem !important;
    font-weight: 700 !important;
    padding: 0.875rem 0.75rem !important;
}
.woocommerce .cart_totals .shop_table .order-total th { border-radius: 0 0.5rem 0.5rem 0 !important; }
.woocommerce .cart_totals .shop_table .order-total td { border-radius: 0.5rem 0 0 0.5rem !important; }
.woocommerce .cart_totals .shop_table .cart-discount td,
.woocommerce .cart_totals .shop_table .cart-discount th { color: #00685f !important; }
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
    background: #00685f !important;
    color: #fff !important;
    font-family: inherit !important;
    font-size: 1.0625rem !important;
    font-weight: 700 !important;
    border: 0 !important;
    cursor: pointer !important;
    transition: background-color 0.2s ease, transform 0.15s ease, box-shadow 0.2s ease !important;
    text-align: center !important;
    box-shadow: 0 4px 14px rgba(0,104,95,.25) !important;
    margin: 0 !important;
}
.woocommerce .wc-proceed-to-checkout .checkout-button:hover,
.woocommerce a.checkout-button:hover {
    background: #005049 !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 20px rgba(0,104,95,.35) !important;
    color: #fff !important;
}

/* Shipping calculator inside cart */
.woocommerce .shipping-calculator-button { color: #00685f !important; font-size: 0.8125rem !important; }
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
.woocommerce-cart .woocommerce-message { background: #e3f7f4 !important; border-inline-start: 3px solid #00685f !important; color: #131b2e !important; border-top: 0 !important; }
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
    border-color: #00685f !important;
    outline: none !important;
    box-shadow: 0 0 0 3px rgba(0, 104, 95, 0.15) !important;
}

/* ── Buttons ─────────────────────────────────────────────── */
.woocommerce button.button,
.woocommerce a.button,
.woocommerce input.button,
.woocommerce .checkout-button {
    background: #00685f !important;
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
.woocommerce .checkout-button:hover { background: #005049 !important; color: #fff !important; }
.woocommerce button.button.alt { background: #00685f !important; }
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
.woocommerce-checkout .woocommerce-form-login-toggle a.showlogin { color: #00685f !important; font-weight: 700 !important; }
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
    border-color: #00685f !important; outline: none !important;
    box-shadow: 0 0 0 3px rgba(0,104,95,.15) !important;
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
    width: 16px !important; height: 16px !important; margin: 0 !important; accent-color: #00685f !important;
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
    font-size: .6875rem !important; font-weight: 700 !important; color: #00685f !important;
    background: #e3f7f4 !important; border-radius: 9999px !important; padding: 1px 7px !important;
}
.woocommerce-checkout .baraa-review-rx {
    display: inline-flex !important; align-items: center !important; gap: 3px !important;
    font-size: .6875rem !important; font-weight: 700 !important;
    color: #00201d !important; background: #89f5e7 !important;
    border-radius: 9999px !important; padding: 1px 8px !important;
}
.woocommerce-checkout .baraa-review-table td.product-total {
    text-align: end !important; white-space: nowrap !important;
    font-weight: 700 !important; font-size: .875rem !important; color: #00685f !important;
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
    background: #f4f3fa !important; color: #00685f !important;
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
    background: #e9f8f5 !important; border-color: #00685f !important;
}
.woocommerce-checkout #payment ul.wc_payment_methods li label {
    display: inline-flex !important; align-items: center !important; gap: .5rem !important;
    font-family: inherit !important; font-size: .875rem !important; font-weight: 600 !important;
    color: #131b2e !important; margin: 0 !important; cursor: pointer !important;
}
.woocommerce-checkout #payment ul.wc_payment_methods li input.input-radio {
    width: 17px !important; height: 17px !important; margin: 0 !important;
    flex-shrink: 0 !important; accent-color: #00685f !important;
}
.woocommerce-checkout #payment ul.wc_payment_methods li label img.kashier-icon {
    height: 20px !important; width: auto !important; display: inline-block !important;
    vertical-align: middle !important; margin-inline-start: 2px !important;
}
.woocommerce-checkout #payment .payment_box {
    background: rgba(0,104,95,.06) !important; border-radius: .5rem !important;
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
    background: #00685f !important; color: #ffffff !important;
    border: 0 !important; border-radius: .75rem !important;
    font-family: inherit !important; font-size: 1.0625rem !important; font-weight: 700 !important;
    padding: .875rem 1.25rem !important; cursor: pointer !important;
    box-shadow: 0 4px 14px rgba(0,104,95,.25) !important;
    transition: background-color .2s ease, transform .15s ease, box-shadow .2s ease !important;
}
.woocommerce-checkout #place_order:hover {
    background: #005049 !important; transform: translateY(-2px) !important;
    box-shadow: 0 6px 20px rgba(0,104,95,.35) !important;
}
.woocommerce-checkout #place_order:disabled { opacity: .6 !important; cursor: not-allowed !important; transform: none !important; }

/* ── Terms & privacy ─────────────────────────────────────── */
.woocommerce-checkout .woocommerce-terms-and-conditions-wrapper { margin-bottom: .875rem !important; }
.woocommerce-checkout .woocommerce-privacy-policy-text p {
    font-size: .75rem !important; line-height: 1.7 !important; color: #6d7a77 !important; margin: 0 !important;
}
.woocommerce-checkout .woocommerce-privacy-policy-text a { color: #00685f !important; }
.woocommerce-checkout .woocommerce-terms-and-conditions-wrapper label,
.woocommerce-checkout .woocommerce-terms-and-conditions-wrapper .form-row label {
    font-size: .8125rem !important; color: #3d4947 !important; font-weight: 500 !important;
}

/* ── Mobile ──────────────────────────────────────────────── */
@media (max-width: 640px) {
    .woocommerce-checkout .woocommerce-billing-fields__field-wrapper,
    .woocommerce-checkout .woocommerce-shipping-fields__field-wrapper,
    .woocommerce-checkout .woocommerce-additional-fields__field-wrapper { grid-template-columns: 1fr !important; }
    .woocommerce-checkout #customer_details .form-row-first,
    .woocommerce-checkout #customer_details .form-row-last { grid-column: 1 / -1 !important; }
    .woocommerce-checkout .woocommerce-notices-wrapper { padding: 0 1rem 1rem; }
}

/* ── Map location picker ─────────────────────────────────── */
.woocommerce-checkout .baraa-map-card { position: relative; z-index: 0; }
.woocommerce-checkout .baraa-map-canvas {
    width: 100% !important; height: 320px !important;
    border-radius: .75rem !important; overflow: hidden !important;
    background: #f4f3fa !important; z-index: 0 !important;
}
.woocommerce-checkout .baraa-map-canvas .leaflet-control-attribution { font-size: 10px !important; }
.woocommerce-checkout .baraa-map-canvas .leaflet-bar a { color: #131b2e !important; }
.woocommerce-checkout .baraa-map-status { font-size: .75rem !important; color: #6d7a77 !important; min-height: 1rem !important; }
.woocommerce-checkout .baraa-order-map { margin: .75rem 0 0 !important; font-size: .875rem !important; }
.woocommerce-checkout .baraa-order-map a { color: #00685f !important; font-weight: 700 !important; }
@media (max-width: 640px) {
    .woocommerce-checkout .baraa-map-canvas { height: 240px !important; }
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
html.dark .woocommerce-checkout .baraa-map-canvas { background: #1a2e2c !important; }
html.dark .woocommerce-checkout .baraa-map-status { color: #8db5b0 !important; }
html.dark .woocommerce-checkout .baraa-order-map a { color: #5ecfc2 !important; }
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
    background-image: linear-gradient(120deg, #00685f 0%, #008378 55%, #006398 100%) !important;
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
    background: #f4f3fa !important; color: #00685f !important;
    font-weight: 800 !important; font-size: 1rem !important;
    padding-top: .875rem !important; padding-bottom: .875rem !important;
}
.baraa-thankyou-thumb { background: #f4f3fa !important; }
.baraa-thankyou-name { color: #131b2e !important; }
.baraa-thankyou-total,
.baraa-thankyou-discount { color: #00685f !important; }
.baraa-thankyou-maplink { color: #00685f !important; }
.baraa-thankyou-maplink:hover { color: #005049 !important; }
.baraa-thankyou-gateway p { margin: 0 !important; }

/* ── Buttons ─────────────────────────────────────────────── */
.baraa-thankyou-btn-primary {
    background: #00685f !important; color: #ffffff !important;
    box-shadow: 0 4px 14px rgba(0,104,95,.25) !important;
    transition: background-color .2s ease, transform .15s ease !important;
}
.baraa-thankyou-btn-primary:hover { background: #005049 !important; color: #ffffff !important; transform: translateY(-2px) !important; }
.baraa-thankyou-btn-ghost {
    background: #eaedff !important; color: #00685f !important;
    transition: background-color .2s ease !important;
}
.baraa-thankyou-btn-ghost:hover { background: #dae2fd !important; color: #005049 !important; }

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
html.dark .baraa-thankyou-maplink { color: #5ecfc2 !important; }
html.dark .baraa-thankyou-maplink:hover { color: #89f5e7 !important; }
html.dark .baraa-thankyou-btn-primary { background: #5ecfc2 !important; color: #002926 !important; }
html.dark .baraa-thankyou-btn-primary:hover { background: #89f5e7 !important; }
html.dark .baraa-thankyou-btn-ghost { background: #1a2e2c !important; color: #5ecfc2 !important; }
html.dark .baraa-thankyou-btn-ghost:hover { background: #1f3735 !important; color: #89f5e7 !important; }
</style>
    <?php
}
add_action('wp_head', 'baraa_pharmacy_thankyou_style_overrides', 101);

/**
 * Translate the COD gateway's saved description/instructions.
 *
 * These strings live in the gateway settings option, so the gettext filter
 * never reaches them — the thank-you page prints them verbatim.
 */
function baraa_pharmacy_arabic_cod_settings($value)
{
    if (is_admin() && !wp_doing_ajax()) {
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
 * Drop the billing fields we no longer need (state, postcode, email) plus
 * their shipping counterparts, so the "ship to a different address" block
 * stays consistent.
 */
function baraa_pharmacy_simplify_checkout_fields($fields)
{
    unset($fields['billing']['billing_state'], $fields['billing']['billing_postcode'], $fields['billing']['billing_email']);
    unset($fields['shipping']['shipping_state'], $fields['shipping']['shipping_postcode']);

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
 * Load Leaflet (OpenStreetMap) and the delivery location picker on checkout.
 */
function baraa_pharmacy_enqueue_checkout_map()
{
    if (!function_exists('is_checkout') || !is_checkout() || (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-received'))) {
        return;
    }

    wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4');
    wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true);

    $map_script = get_template_directory() . '/assets/js/checkout-map.js';
    $version    = file_exists($map_script) ? (string) filemtime($map_script) : '1.0.0';

    wp_enqueue_script(
        'baraa-pharmacy-checkout-map',
        get_template_directory_uri() . '/assets/js/checkout-map.js',
        array('leaflet'),
        $version,
        true
    );

    wp_localize_script('baraa-pharmacy-checkout-map', 'baraaMap', array(
        'defaultCenter' => array(31.2001, 29.9187),
        'strings'       => array(
            'locating'     => __('جارٍ تحديد موقعك...', 'baraa-pharmacy'),
            'located'      => __('تم تحديد موقعك الحالي.', 'baraa-pharmacy'),
            'geoError'     => __('تعذّر الوصول إلى موقعك، حدّد الموقع يدويًا على الخريطة.', 'baraa-pharmacy'),
            'reverseError' => __('تعذّر جلب العنوان تلقائيًا، يمكنك كتابته يدويًا.', 'baraa-pharmacy'),
            'searchEmpty'  => __('اكتب اسم المنطقة أو الشارع للبحث.', 'baraa-pharmacy'),
            'searchNone'   => __('لم يتم العثور على نتائج، جرّب كلمة أخرى.', 'baraa-pharmacy'),
            'searching'    => __('جارٍ البحث...', 'baraa-pharmacy'),
        ),
    ));
}
add_action('wp_enqueue_scripts', 'baraa_pharmacy_enqueue_checkout_map', 30);

/**
 * Persist the pinned coordinates and reverse-geocoded address on the order.
 */
function baraa_pharmacy_save_map_location($order, $data)
{
    $lat     = isset($_POST['baraa_map_lat']) ? sanitize_text_field(wp_unslash($_POST['baraa_map_lat'])) : '';
    $lng     = isset($_POST['baraa_map_lng']) ? sanitize_text_field(wp_unslash($_POST['baraa_map_lng'])) : '';
    $address = isset($_POST['baraa_map_address']) ? sanitize_text_field(wp_unslash($_POST['baraa_map_address'])) : '';

    if ($lat && $lng) {
        $order->update_meta_data('_baraa_map_lat', $lat);
        $order->update_meta_data('_baraa_map_lng', $lng);
    }

    if ($address) {
        $order->update_meta_data('_baraa_map_address', $address);
    }
}
add_action('woocommerce_checkout_create_order', 'baraa_pharmacy_save_map_location', 10, 2);

/**
 * Show the pinned delivery location inside the order admin screen.
 */
function baraa_pharmacy_map_admin_display($order)
{
    $lat     = $order->get_meta('_baraa_map_lat');
    $lng     = $order->get_meta('_baraa_map_lng');
    $address = $order->get_meta('_baraa_map_address');

    if (!$lat || !$lng) {
        return;
    }

    $map_url = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($lat . ',' . $lng);

    echo '<div class="baraa-admin-map" style="clear:both;margin-top:12px;">';
    echo '<p style="margin:0 0 4px;"><strong>' . esc_html__('موقع التوصيل على الخريطة', 'baraa-pharmacy') . '</strong></p>';
    if ($address) {
        echo '<p style="margin:0 0 6px;">' . esc_html($address) . '</p>';
    }
    echo '<p style="margin:0;"><a href="' . esc_url($map_url) . '" target="_blank" rel="noopener">' . esc_html__('عرض الموقع على خرائط Google', 'baraa-pharmacy') . '</a></p>';
    echo '</div>';
}
add_action('woocommerce_admin_order_data_after_shipping_address', 'baraa_pharmacy_map_admin_display');

/**
 * Confirm the pinned delivery location on the "order received" page.
 */
function baraa_pharmacy_map_thankyou_display($order)
{
    $lat = $order->get_meta('_baraa_map_lat');
    $lng = $order->get_meta('_baraa_map_lng');

    if (!$lat || !$lng) {
        return;
    }

    $map_url = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($lat . ',' . $lng);

    printf(
        '<p class="baraa-order-map">%1$s <a href="%2$s" target="_blank" rel="noopener">%3$s</a></p>',
        esc_html__('موقع التوصيل المُحدَّد:', 'baraa-pharmacy'),
        esc_url($map_url),
        esc_html__('عرض على الخريطة', 'baraa-pharmacy')
    );
}
add_action('woocommerce_order_details_after_order_table', 'baraa_pharmacy_map_thankyou_display');


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

    if (!in_array($domain, array('woocommerce', 'default', 'woocommerce-gateway-kashier', 'routemile-for-woocommerce'), true)) {
        return $translated;
    }

    static $map = array(
        // Checkout sections
        'Billing details'                             => 'بيانات الفاتورة',
        'Additional information'                      => 'معلومات إضافية',
        'Ship to a different address?'                => 'الشحن إلى عنوان مختلف؟',
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
    );

    return isset($map[$text]) ? $map[$text] : $translated;
}
add_filter('gettext', 'baraa_pharmacy_arabic_woocommerce_strings', 20, 3);

/**
 * Plural forms (`_n`) used by WooCommerce on the storefront — the plain
 * gettext filter cannot reach these, they go through ngettext.
 */
function baraa_pharmacy_arabic_woocommerce_plurals($translation, $single, $plural, $number, $domain)
{
    if (is_admin() && !wp_doing_ajax()) {
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
 * (e.g. Kashier saves "Card" / "Mobile Wallet" from its settings default), so
 * they never pass through gettext. Translate them on the storefront here.
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
    );

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
    );

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
    if (is_admin() || is_feed()) {
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
    if (is_admin()) {
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
    if (!isset($states['EG'])) {
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
    if (is_admin() && !wp_doing_ajax()) {
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
    if (is_admin() && !wp_doing_ajax()) {
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
    if (is_admin() && !wp_doing_ajax()) {
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
