<?php
if (!defined('ABSPATH')) {
    exit;
}

$shop_url         = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
$cart_url         = function_exists('wc_get_cart_url')       ? wc_get_cart_url()              : $shop_url;
$woocommerce      = function_exists('WC') ? WC() : null;
$cart_count       = $woocommerce && $woocommerce->cart ? $woocommerce->cart->get_cart_contents_count() : 0;
$cart_total       = $woocommerce && $woocommerce->cart ? $woocommerce->cart->get_cart_total() : '';
$current_locale   = get_locale();
$cart_total_plain = wp_strip_all_tags($cart_total);

// ── Dark / light mode ────────────────────────────────────────────────────
// Read cookie set by JS. Validate: only 'dark' or 'light' are accepted.
$saved_theme  = isset($_COOKIE['baraa_theme']) && is_string($_COOKIE['baraa_theme'])
    ? sanitize_key(wp_unslash($_COOKIE['baraa_theme']))
    : '';
$is_dark      = $saved_theme === 'dark';
$theme_cookie = $is_dark ? 'dark' : 'light';
// ─────────────────────────────────────────────────────────────────────────
?>
<!doctype html>
<html <?php language_attributes(); ?> class="<?php echo $is_dark ? 'dark' : ''; ?>">
<head>
    <meta charset="<?php bloginfo('charset'); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <?php wp_head(); ?>
    <!-- Anti-flash: apply dark class before first paint if cookie says dark -->
    <script>
    (function(){
        var c = document.cookie.match(/baraa_theme=([^;]+)/);
        if (c && c[1] === 'dark') {
            document.documentElement.classList.add('dark');
        } else if (c && c[1] === 'light') {
            document.documentElement.classList.remove('dark');
        }
    })();
    </script>
</head>
<body <?php body_class('bg-surface font-body-md text-on-surface antialiased transition-colors duration-200'); ?>>
<?php wp_body_open(); ?>

<header class="fixed top-0 w-full z-50 bg-surface/95 backdrop-blur-md shadow-[0_1px_8px_rgba(0,0,0,0.04)]">

    <!-- ── Top announcement bar ───────────────────────────────── -->
    <div class="bg-primary text-on-primary py-1.5 px-margin">
        <div class="w-full flex items-center justify-between font-label-sm text-label-sm">
            <div class="flex items-center gap-space-sm">
                <span class="material-symbols-outlined text-[15px]">local_shipping</span>
                <span><?php esc_html_e('توصيل سريع مجاني للطلبات فوق 350 ج.م في القاهرة والجيزة والمدن الرئيسية ⚡', 'baraa-pharmacy'); ?></span>
            </div>
            <div class="hidden md:flex items-center gap-space-md font-label-md">
                <span class="text-primary-fixed"><?php esc_html_e('صيدليات براءة المعتمدة', 'baraa-pharmacy'); ?></span>
            </div>
        </div>
    </div>

    <!-- ── Main header row ────────────────────────────────────── -->
    <!-- Mobile: brand+actions top row, search below. Desktop: single row. -->
    <div class="w-full px-margin py-2 flex flex-wrap items-center justify-between gap-x-space-lg gap-y-2 bg-surface-container-lowest lg:h-24 lg:flex-nowrap lg:py-0">

        <!-- Brand -->
        <a class="flex items-center gap-space-sm flex-shrink-0 order-1" href="<?php echo esc_url(home_url('/')); ?>">
            <?php
            $custom_logo_id = get_theme_mod('custom_logo');
            $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
            if (has_custom_logo()) :
            ?>
                <div class="w-14 h-14 lg:w-16 lg:h-16 flex items-center justify-center flex-shrink-0">
                    <img src="<?php echo esc_url($logo[0]); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" class="w-full h-full object-contain">
                </div>
            <?php else : ?>
                <div class="w-14 h-14 lg:w-16 lg:h-16 rounded-xl bg-primary flex items-center justify-center text-on-primary flex-shrink-0">
                    <svg viewBox="0 0 40 40" width="32" height="32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <rect x="16" y="8"  width="8" height="24" rx="2" fill="currentColor" opacity="0.95"/>
                        <rect x="8"  y="16" width="24" height="8"  rx="2" fill="currentColor" opacity="0.95"/>
                    </svg>
                </div>
            <?php endif; ?>
            <div class="flex flex-col">
                <span class="font-title-lg text-title-lg text-primary leading-tight font-bold text-[15px] lg:text-[18px]"><?php esc_html_e('صيدلية براءة', 'baraa-pharmacy'); ?></span>
                <span class="font-label-sm text-label-sm text-outline tracking-wider hidden sm:block">Baraa Pharmacy</span>
            </div>
        </a>

        <!-- Search bar — full width on mobile (order-3), flex-1 on desktop (order-2) -->
        <div class="w-full order-3 lg:order-2 lg:flex-1 lg:max-w-2xl">
            <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                <input type="hidden" name="post_type" value="product" />
                <div class="relative flex items-center rounded-lg bg-surface-container-low focus-within:ring-2 focus-within:ring-primary">
                    <label class="sr-only" for="baraa-header-search"><?php esc_html_e('ابحث عن منتج', 'baraa-pharmacy'); ?></label>
                    <input
                        id="baraa-header-search"
                        class="w-full bg-transparent px-space-md py-2.5 text-on-surface placeholder:text-outline font-body-md text-body-md focus:outline-none min-w-0"
                        type="search"
                        name="s"
                        value="<?php echo esc_attr(get_search_query()); ?>"
                        placeholder="<?php esc_attr_e('ابحث بالاسم العلمي، التجاري، المادة الفعالة أو الأعراض...', 'baraa-pharmacy'); ?>"
                    />
                    <div class="flex items-center gap-space-xs pl-space-sm flex-shrink-0">
                        <button
                            class="bg-primary text-on-primary px-space-md py-1.5 rounded-lg font-label-md text-label-md flex items-center gap-1 hover:bg-primary-container transition-colors"
                            type="submit"
                            aria-label="<?php esc_attr_e('بحث', 'baraa-pharmacy'); ?>"
                        >
                            <span class="material-symbols-outlined text-[18px]">search</span>
                            <span class="hidden sm:inline"><?php esc_html_e('بحث', 'baraa-pharmacy'); ?></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Actions — order-2 on mobile (right of brand), order-3 on desktop -->
        <div class="flex items-center gap-2 flex-shrink-0 order-2 lg:order-3 lg:gap-space-md">

            <!-- Language switch — desktop only -->
            <nav class="hidden lg:flex items-center gap-1 font-label-sm text-label-sm text-on-surface-variant" aria-label="<?php esc_attr_e('اختر اللغة', 'baraa-pharmacy'); ?>">
                <a
                    href="<?php echo esc_url(baraa_pharmacy_language_switch_url('ar')); ?>"
                    lang="ar" hreflang="ar"
                    class="px-2 py-1 rounded hover:text-primary transition-colors <?php echo 'ar' === $current_locale ? 'text-primary font-bold' : ''; ?>"
                >العربية</a>
                <span class="opacity-30" aria-hidden="true">|</span>
                <a
                    href="<?php echo esc_url(baraa_pharmacy_language_switch_url('en_US')); ?>"
                    lang="en" hreflang="en"
                    class="px-2 py-1 rounded hover:text-primary transition-colors <?php echo 'en_US' === $current_locale ? 'text-primary font-bold' : ''; ?>"
                >English</a>
            </nav>

            <!-- ── Dark / Light mode toggle ──────────────────── -->
            <button
                id="baraa-theme-toggle"
                type="button"
                class="flex items-center gap-1 p-2 rounded-lg bg-surface-container text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface transition-colors"
                aria-label="<?php echo $is_dark ? esc_attr__('تفعيل الوضع الفاتح', 'baraa-pharmacy') : esc_attr__('تفعيل الوضع الداكن', 'baraa-pharmacy'); ?>"
                aria-pressed="<?php echo $is_dark ? 'true' : 'false'; ?>"
                data-light-label="<?php esc_attr_e('تفعيل الوضع الفاتح', 'baraa-pharmacy'); ?>"
                data-dark-label="<?php esc_attr_e('تفعيل الوضع الداكن', 'baraa-pharmacy'); ?>"
            >
                <span id="baraa-theme-icon-sun" class="material-symbols-outlined text-[18px] <?php echo $is_dark ? '' : 'hidden'; ?>" aria-hidden="true">light_mode</span>
                <span id="baraa-theme-icon-moon" class="material-symbols-outlined text-[18px] <?php echo $is_dark ? 'hidden' : ''; ?>" aria-hidden="true">dark_mode</span>
                <span id="baraa-theme-label" class="hidden lg:inline text-[12px] font-label-md text-label-md">
                    <?php echo $is_dark ? esc_html__('فاتح', 'baraa-pharmacy') : esc_html__('داكن', 'baraa-pharmacy'); ?>
                </span>
            </button>

            <!-- Pharmacist consultation — desktop only -->
            <a
                href="https://wa.me/201556859030"
                target="_blank" rel="noopener noreferrer"
                class="hidden lg:flex items-center gap-space-xs px-space-sm py-2 rounded-lg bg-surface-container text-primary font-label-md text-label-md hover:bg-primary hover:text-on-primary transition-colors"
            >
                <span class="material-symbols-outlined text-[20px]">medical_services</span>
                <span><?php esc_html_e('استشارة صيدلي', 'baraa-pharmacy'); ?></span>
            </a>

            <!-- Cart -->
            <a
                class="flex items-center gap-1.5 px-2 py-1.5 rounded-lg bg-primary-fixed text-on-primary-fixed hover:bg-primary hover:text-on-primary transition-colors"
                href="<?php echo esc_url($cart_url); ?>"
                aria-label="<?php echo esc_attr(sprintf(__('السلة — %s منتج', 'baraa-pharmacy'), $cart_count)); ?>"
            >
                <div class="relative flex items-center">
                    <span class="material-symbols-outlined text-[20px] lg:text-[22px]">shopping_bag</span>
                    <?php if ($cart_count > 0) : ?>
                        <span class="absolute -top-1.5 -right-1.5 bg-error text-on-error text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold baraa-cart-count">
                            <?php echo esc_html((string) $cart_count); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="flex flex-col text-right">
                    <span class="text-[10px] opacity-80 leading-none"><?php esc_html_e('السلة', 'baraa-pharmacy'); ?></span>
                    <?php if ($cart_total_plain) : ?>
                        <span class="text-xs font-bold leading-tight baraa-cart-total hidden sm:block"><?php echo esc_html($cart_total_plain); ?></span>
                    <?php endif; ?>
                </div>
            </a>

        </div>
    </div>

    <!-- ── Category navigation bar ───────────────────────────── -->
    <div class="h-12 w-full px-margin bg-surface-container-low flex items-center gap-space-md">
        <nav
            class="flex items-center gap-space-xs font-label-md text-label-md overflow-x-auto py-1 flex-1"
            aria-label="<?php esc_attr_e('التنقل بين الأقسام', 'baraa-pharmacy'); ?>"
        >
            <a
                class="px-space-sm py-1.5 rounded-lg whitespace-nowrap transition-colors <?php echo is_shop() ? 'bg-primary text-on-primary font-bold' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface'; ?>"
                href="<?php echo esc_url($shop_url); ?>"
            ><?php esc_html_e('جميع الأقسام', 'baraa-pharmacy'); ?></a>

            <?php
            $nav_categories = get_terms(array(
                'taxonomy'   => 'product_cat',
                'hide_empty' => true,
                'parent'     => 0,
                'number'     => 7,
                'orderby'    => 'menu_order',
            ));
            if (!is_wp_error($nav_categories)) :
                foreach ($nav_categories as $nav_cat) :
                    $cat_url    = get_term_link($nav_cat);
                    $is_current = is_product_category($nav_cat->slug);
                    if (is_wp_error($cat_url)) {
                        continue;
                    }
                    ?>
                    <a
                        class="px-space-sm py-1.5 rounded-lg whitespace-nowrap transition-colors <?php echo $is_current ? 'bg-primary text-on-primary font-bold' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface'; ?>"
                        href="<?php echo esc_url($cat_url); ?>"
                        <?php echo $is_current ? 'aria-current="page"' : ''; ?>
                    ><?php echo esc_html($nav_cat->name); ?></a>
                    <?php
                endforeach;
            endif;
            ?>
        </nav>

        <!-- Online prescription CTA -->
        <a
            class="flex items-center gap-1.5 px-space-md py-1.5 rounded-lg bg-secondary text-on-secondary font-label-md text-label-md hover:opacity-90 transition-opacity whitespace-nowrap flex-shrink-0"
            href="https://wa.me/201556859030"
            target="_blank" rel="noopener noreferrer"
        >
            <span class="material-symbols-outlined text-[16px]">receipt_long</span>
            <span><?php esc_html_e('روشتة أونلاين', 'baraa-pharmacy'); ?></span>
        </a>
    </div>

</header>

<!-- Spacer below fixed header
     Mobile:  top-bar(~36) + main(~88) + nav(~48) = ~172px
     Desktop: top-bar(~36) + main(~96) + nav(~48) = ~180px -->
<div class="h-[172px] lg:h-[180px]" aria-hidden="true"></div>
