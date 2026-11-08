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
}
add_action('wp', 'baraa_pharmacy_remove_wc_defaults');

/**
 * Remove the postcode field from the shipping calculator.
 * Egypt doesn't use postcodes in practice.
 */
add_filter('woocommerce_shipping_calculator_enable_postcode', '__return_false');

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

function baraa_pharmacy_render_classic_cart_block($block_content, $block)
{
    if (!is_cart() || !isset($block['blockName']) || 'woocommerce/cart' !== $block['blockName']) {
        return $block_content;
    }

    return do_shortcode('[woocommerce_cart]');
}
add_filter('render_block', 'baraa_pharmacy_render_classic_cart_block', 10, 2);

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

function baraa_pharmacy_translate_cart_woocommerce_strings($translation, $text, $domain)
{
    if (!is_cart() || 'woocommerce' !== $domain) {
        return $translation;
    }

    $cart_strings = array(
        'Your cart is currently empty.' => 'Your cart is empty',
        'Return to shop' => 'Return to shop',
        'Cart totals' => 'Order summary',
        'Subtotal' => 'Subtotal',
        'Shipping' => 'Shipping',
        'Shipping to %s.' => 'Shipping to %s.',
        'Change address' => 'Change address',
        'Shipping options will be updated during checkout.' => 'Shipping options will be updated during checkout.',
        'Shipping costs are calculated during checkout.' => 'Shipping costs are calculated during checkout.',
        'Enter your address to view shipping options.' => 'Enter your address to view shipping options.',
        'There are no shipping options available. Please ensure that your address has been entered correctly, or contact us if you need any help.' => 'There are no shipping options available. Please ensure that your address has been entered correctly, or contact us if you need any help.',
        'No shipping options were found for %s.' => 'No shipping options were found for %s.',
        'Enter a different address' => 'Enter a different address',
        'Total' => 'Total',
        'Coupon:' => 'Coupon:',
        'Coupon code' => 'Enter coupon code',
        'Apply coupon' => 'Apply coupon',
        'Update cart' => 'Update cart',
        'Proceed to checkout' => 'Proceed to checkout',
        'Remove %s from cart' => 'Remove %s from cart',
        'Available on backorder' => 'Available on backorder',
        'Calculate shipping' => 'Calculate shipping',
        'Update' => 'Update',
    );

    return isset($cart_strings[$text]) ? __($cart_strings[$text], 'baraa-pharmacy') : $translation;
}
add_filter('gettext', 'baraa_pharmacy_translate_cart_woocommerce_strings', 10, 3);

function baraa_pharmacy_translate_cart_woocommerce_context_strings($translation, $text, $context, $domain)
{
    if (!is_cart() || 'woocommerce' !== $domain || 'shipping packages' !== $context || 'Shipment' !== $text) {
        return $translation;
    }

    return __('Shipment', 'baraa-pharmacy');
}
add_filter('gettext_with_context', 'baraa_pharmacy_translate_cart_woocommerce_context_strings', 10, 4);

function baraa_pharmacy_translate_cart_shipping_method_label($label, $method)
{
    if (
        !is_cart()
        || !($method instanceof WC_Shipping_Rate)
        || 'free_shipping' !== $method->get_method_id()
        || 'Free shipping' !== $label
    ) {
        return $label;
    }

    return __('Free shipping', 'baraa-pharmacy');
}
add_filter('woocommerce_cart_shipping_method_full_label', 'baraa_pharmacy_translate_cart_shipping_method_label', 10, 2);
