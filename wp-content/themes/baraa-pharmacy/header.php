<?php
if (!defined('ABSPATH')) {
    exit;
}

$shop_url         = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
$cart_url         = function_exists('wc_get_cart_url')       ? wc_get_cart_url()              : $shop_url;
$account_url      = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
$woocommerce      = function_exists('WC') ? WC() : null;
$cart_count       = $woocommerce && $woocommerce->cart ? $woocommerce->cart->get_cart_contents_count() : 0;
$cart_total       = $woocommerce && $woocommerce->cart ? $woocommerce->cart->get_cart_total() : '';
$current_locale   = get_locale();
$cart_total_plain = wp_strip_all_tags($cart_total);
$is_arabic        = in_array($current_locale, array('ar', 'ar_AR'));

// شريط عرض الحملة: بيتطبع جوه الهيدر الثابت، وسيبيسر الهيدر بيزيد بمقدار
// طوله عشان ما يغطّاش أول المحتوى. من غير حملة ما فيش أي تغيير في الهيدر.
$offer_ribbon = function_exists('baraa_pharmacy_offer_ribbon_data') ? baraa_pharmacy_offer_ribbon_data() : null;

// شريط أعلى الهيدر (Top Bar)
$show_top_bar = true;

// ── Dark / light mode ────────────────────────────────────────────────────
// Disabled dark mode: site is strictly light mode with #117A65 & #16A085 palette
$is_dark      = false;
$theme_cookie = 'light';
// ─────────────────────────────────────────────────────────────────────────
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <?php wp_head(); ?>
    <!-- Ensure light mode always -->
    <script>
    (function(){
        document.documentElement.classList.remove('dark');
        if (document.cookie.indexOf('baraa_theme=dark') !== -1) {
            document.cookie = 'baraa_theme=light; path=/; max-age=31536000; SameSite=Lax';
        }
    })();
    </script>
</head>
<body <?php body_class('bg-surface font-body-md text-on-surface antialiased transition-colors duration-200'); ?>>
<?php wp_body_open(); ?>

<header class="fixed top-0 w-full z-50 bg-[#16A085] shadow-[0_2px_12px_rgba(0,0,0,0.08)]">

<!-- ── Top Bar (inside header) ─────────────────────────────────────────────────── -->
<?php if ($show_top_bar) : ?>
<div id="top-bar" class="w-full bg-[#0E6655] text-white text-xs py-3 border-b border-white/10">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <div class="flex flex-col md:flex-row items-center justify-between gap-2">
            <!-- Left: Welcome message -->
            <div class="flex items-center gap-4">
                <span class="font-medium"><?php echo $is_arabic ? 'أهلا بكم في الموقع الرسمي لصيدليات براءة' : 'Welcome to the official website of Baraa Pharmacies'; ?></span>
            </div>

            <!-- Center: Hotline -->
            <div class="flex items-center gap-2">
                <?php
                $hotline_number = get_option('baraa_pharmacy_hotline_number', '16840');
                ?>
                <a href="tel:<?php echo esc_attr($hotline_number); ?>" class="flex items-center gap-1.5 bg-white/10 hover:bg-white/20 px-3 py-1.5 rounded-full transition-colors">
                    <span class="material-symbols-outlined text-[14px]">phone</span>
                    <span class="font-bold">Hotline 24/7 : <?php echo esc_html($hotline_number); ?></span>
                </a>
            </div>

            <!-- Right: Social icons -->
            <div class="flex items-center gap-3">
                <!-- Social icons -->
                <div class="flex items-center gap-2">
                    <a href="https://www.facebook.com/" target="_blank" rel="noopener noreferrer" class="text-white/80 hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <a href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer" class="text-white/80 hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                    </a>
                    <a href="https://www.tiktok.com/" target="_blank" rel="noopener noreferrer" class="text-white/80 hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                    </a>
                    <a href="mailto:info@baraa-pharmacy.com" class="text-white/80 hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                    </a>
                    <a href="https://www.linkedin.com/" target="_blank" rel="noopener noreferrer" class="text-white/80 hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                    </a>
                    <a href="https://www.youtube.com/" target="_blank" rel="noopener noreferrer" class="text-white/80 hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg>
                    </a>
                    <a href="https://t.me/" target="_blank" rel="noopener noreferrer" class="text-white/80 hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

    <?php if ($offer_ribbon) : ?>
        <?php baraa_pharmacy_render_offer_ribbon(); ?>
    <?php endif; ?>

    <!-- ── Main header row ────────────────────────────────────── -->
    <!-- Mobile: brand+actions top row, search below. Desktop: single row. -->
    <div class="w-full px-margin py-2.5 flex flex-wrap items-center justify-between gap-x-space-lg gap-y-2 bg-[#16A085] text-white lg:h-24 lg:flex-nowrap lg:py-0">

        <!-- Brand -->
        <a class="flex items-center gap-space-sm flex-shrink-0 order-1" href="<?php echo esc_url(home_url('/')); ?>">
            <?php
            $custom_logo_id = get_theme_mod('custom_logo');
            $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
            if (has_custom_logo()) :
            ?>
                <div class="w-14 h-14 lg:w-16 lg:h-16 rounded-xl overflow-hidden bg-white/95 p-1 ring-1 ring-white/20 flex items-center justify-center flex-shrink-0 shadow-sm">
                    <img src="<?php echo esc_url($logo[0]); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" class="w-full h-full object-contain">
                </div>
            <?php else : ?>
                <div class="w-14 h-14 lg:w-16 lg:h-16 rounded-xl bg-white text-[#16A085] flex items-center justify-center flex-shrink-0 shadow-sm">
                    <svg viewBox="0 0 40 40" width="32" height="32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <rect x="16" y="8"  width="8" height="24" rx="2" fill="currentColor" opacity="0.95"/>
                        <rect x="8"  y="16" width="24" height="8"  rx="2" fill="currentColor" opacity="0.95"/>
                    </svg>
                </div>
            <?php endif; ?>
            <div class="flex flex-col">
                <span class="font-title-lg text-title-lg text-white leading-tight font-bold text-[16px] lg:text-[19px] tracking-wide"><?php esc_html_e('صيدليات براءة', 'baraa-pharmacy'); ?></span>
                <span class="font-label-sm text-label-sm text-white/85 tracking-wider hidden sm:block">Baraa Pharmacy</span>
            </div>
        </a>

        <!-- Search bar — full width on mobile (order-3), flex-1 on desktop (order-2) -->
        <div class="w-full order-3 lg:order-2 lg:flex-1 lg:max-w-2xl">
            <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                <input type="hidden" name="post_type" value="product" />
                <div class="relative flex items-center rounded-lg bg-white shadow-sm ring-1 ring-black/5 focus-within:ring-2 focus-within:ring-white">
                    <label class="sr-only" for="baraa-header-search"><?php esc_html_e('ابحث عن منتج', 'baraa-pharmacy'); ?></label>
                    <input
                        id="baraa-header-search"
                        class="w-full bg-transparent px-space-md py-2.5 text-gray-800 placeholder:text-gray-400 font-body-md text-body-md focus:outline-none min-w-0"
                        type="search"
                        name="s"
                        value="<?php echo esc_attr(get_search_query()); ?>"
                        placeholder="<?php esc_attr_e('ابحث بالاسم العلمي، التجاري، المادة الفعالة أو الأعراض...', 'baraa-pharmacy'); ?>"
                    />
                    <div class="flex items-center gap-space-xs pl-space-sm pr-1 flex-shrink-0">
                        <button
                            class="bg-[#117A65] hover:bg-[#0E6655] text-white px-space-md py-2 rounded-lg font-label-md text-label-md font-bold flex items-center gap-1 shadow-sm transition-colors"
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
            <nav class="hidden lg:flex items-center gap-1 font-label-sm text-label-sm text-white/90" aria-label="<?php esc_attr_e('اختر اللغة', 'baraa-pharmacy'); ?>">
                <a
                    href="<?php echo esc_url(baraa_pharmacy_language_switch_url('ar')); ?>"
                    lang="ar" hreflang="ar"
                    class="px-2 py-1 rounded hover:bg-white/15 transition-colors <?php echo 'ar' === $current_locale ? 'text-white font-bold bg-white/20' : 'text-white/80'; ?>"
                >العربية</a>
                <span class="opacity-50 text-white" aria-hidden="true">|</span>
                <a
                    href="<?php echo esc_url(baraa_pharmacy_language_switch_url('en_US')); ?>"
                    lang="en" hreflang="en"
                    class="px-2 py-1 rounded hover:bg-white/15 transition-colors <?php echo 'en_US' === $current_locale ? 'text-white font-bold bg-white/20' : 'text-white/80'; ?>"
                >English</a>
            </nav>

            <!-- Pharmacist consultation — desktop only -->
            <a
                href="https://wa.me/201556859030"
                target="_blank" rel="noopener noreferrer"
                class="hidden lg:flex items-center gap-space-xs px-3 py-2 rounded-lg bg-white/15 hover:bg-white hover:text-[#16A085] text-white border border-white/25 font-label-md text-label-md transition-all shadow-sm font-semibold"
            >
                <span class="material-symbols-outlined text-[20px]">medical_services</span>
                <span><?php echo $is_arabic ? 'استشارة صيدلي' : 'Pharmacist Consultation'; ?></span>
            </a>

            <!-- My account -->
            <a
                class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white/15 hover:bg-white hover:text-[#16A085] text-white border border-white/25 font-label-md text-label-md transition-all shadow-sm font-semibold"
                href="<?php echo esc_url($account_url); ?>"
                aria-label="<?php echo $is_arabic ? 'حسابي' : 'My Account'; ?>"
            >
                <span class="material-symbols-outlined text-[20px] lg:text-[22px]" aria-hidden="true">person</span>
                <span class="hidden lg:inline text-xs font-semibold leading-none"><?php echo $is_arabic ? 'حسابي' : 'My Account'; ?></span>
            </a>

            <!-- Cart -->
            <a
                class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white/15 hover:bg-white hover:text-[#16A085] text-white border border-white/25 transition-all shadow-sm group"
                href="<?php echo esc_url($cart_url); ?>"
                aria-label="<?php echo esc_attr(sprintf($is_arabic ? 'السلة — %s منتج' : 'Cart — %s items', $cart_count)); ?>"
            >
                <div class="relative flex items-center">
                    <span class="material-symbols-outlined text-[20px] lg:text-[22px]">shopping_bag</span>
                    <span id="baraa-cart-count" class="absolute -top-2 -right-2 bg-red-600 text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold baraa-cart-count shadow" style="<?php echo $cart_count > 0 ? '' : 'display:none'; ?>">
                        <?php echo esc_html((string) $cart_count); ?>
                    </span>
                </div>
                <div class="flex flex-col text-right">
                    <span class="text-[11px] text-white/90 group-hover:text-inherit leading-none"><?php echo $is_arabic ? 'السلة' : 'Cart'; ?></span>
                    <span id="baraa-cart-total" class="text-xs font-bold leading-tight baraa-cart-total text-white group-hover:text-inherit hidden sm:block" style="<?php echo $cart_total_plain ? '' : 'display:none'; ?>"><?php echo esc_html($cart_total_plain); ?></span>
                </div>
            </a>

        </div>
    </div>

    <!-- ── Main navigation bar ───────────────────────────── -->
    <div class="h-12 w-full px-margin bg-[#117A65] flex items-center gap-space-md border-t border-white/15 text-white shadow-sm">
        <!-- Mobile menu button -->
        <button
            id="mobile-menu-toggle"
            class="lg:hidden flex items-center gap-1 px-2 py-1.5 rounded-lg hover:bg-white/20 transition-colors"
            aria-label="<?php echo $is_arabic ? 'القائمة' : 'Menu'; ?>"
        >
            <span class="material-symbols-outlined text-[20px]">menu</span>
        </button>

        <nav
            class="hidden lg:flex items-center gap-space-xs font-label-md text-label-md py-1 flex-1"
            aria-label="<?php echo $is_arabic ? 'التنقل الرئيسي' : 'Main Navigation'; ?>"
        >
            <!-- الرئيسية -->
            <a
                class="px-3 py-1.5 rounded-lg whitespace-nowrap transition-colors <?php echo is_front_page() ? 'bg-[#16A085] text-white font-bold shadow-sm ring-1 ring-white/30' : 'text-white/95 hover:bg-white/20 hover:text-white font-medium'; ?>"
                href="<?php echo esc_url(home_url('/')); ?>"
            ><?php echo $is_arabic ? 'الرئيسية' : 'Home'; ?></a>

            <!-- تسوق مع dropdown -->
            <div class="relative group" id="shop-dropdown-container">
                <a
                    href="<?php echo esc_url($shop_url); ?>"
                    class="px-3 py-1.5 rounded-lg whitespace-nowrap transition-colors text-white/95 hover:bg-white/20 hover:text-white font-medium flex items-center gap-1"
                    id="shop-dropdown-toggle"
                    aria-expanded="false"
                    aria-haspopup="true"
                >
                    <?php echo $is_arabic ? 'تسوق' : 'Shop'; ?>
                    <span class="material-symbols-outlined text-[16px]" id="shop-dropdown-icon">expand_more</span>
                </a>
                <!-- Dropdown menu -->
                <div id="shop-dropdown-menu" class="absolute right-0 top-full mt-1 bg-[#117A65] rounded-lg shadow-xl py-3 min-w-[800px] max-w-[1400px] hidden group-hover:block z-[100] border border-white/20 max-h-[70vh] overflow-y-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4 px-4" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                        <?php
                        // Get parent categories (parent = 0)
                        $parent_categories = get_terms(array(
                            'taxonomy'   => 'product_cat',
                            'hide_empty' => false,
                            'parent'     => 0,
                            'number'     => 0,
                            'orderby'    => 'name',
                            'order'      => 'ASC',
                        ));
                        
                        if (is_wp_error($parent_categories)) {
                            echo '<div class="col-span-full px-4 py-2 text-white">Error: ' . esc_html($parent_categories->get_error_message()) . '</div>';
                        } elseif (empty($parent_categories)) {
                            echo '<div class="col-span-full px-4 py-2 text-white/70">لا توجد فئات رئيسية - أنشئ فئات في WooCommerce > المنتجات > الفئات</div>';
                        } else {
                            // Display each category in its own column
                            foreach ($parent_categories as $parent_cat) {
                                $parent_url = get_term_link($parent_cat);
                                if (is_wp_error($parent_url)) {
                                    continue;
                                }
                                
                                // Get subcategories (children of this parent)
                                $subcategories = get_terms(array(
                                    'taxonomy'   => 'product_cat',
                                    'hide_empty' => false,
                                    'parent'     => $parent_cat->term_id,
                                    'number'     => 0,
                                ));
                                ?>
                                <div class="space-y-3">
                                    <div>
                                        <h4 class="font-title-md text-title-md font-bold text-white mb-1.5">
                                            <a href="<?php echo esc_url($parent_url); ?>" class="hover:text-white/80 transition-colors">
                                                <?php echo esc_html($parent_cat->name); ?>
                                            </a>
                                        </h4>
                                        
                                        <?php if (!empty($subcategories) && !is_wp_error($subcategories)) : ?>
                                            <div class="space-y-0.5">
                                                <?php foreach ($subcategories as $sub_cat) {
                                                    $sub_cat_url = get_term_link($sub_cat);
                                                    if (is_wp_error($sub_cat_url)) {
                                                        continue;
                                                    }
                                                    ?>
                                                    <a
                                                        class="block px-2 py-0.5 text-white/80 hover:text-white hover:bg-white/10 transition-colors font-label-sm text-label-sm flex items-center gap-1"
                                                        href="<?php echo esc_url($sub_cat_url); ?>"
                                                    >
                                                        <span class="material-symbols-outlined text-[12px]">chevron_left</span>
                                                        <?php echo esc_html($sub_cat->name); ?>
                                                    </a>
                                                <?php } ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- العروض -->
            <a
                class="px-3 py-1.5 rounded-lg whitespace-nowrap transition-colors text-white/95 hover:bg-white/20 hover:text-white font-medium"
                href="<?php echo esc_url(home_url('/deals')); ?>"
            ><?php echo $is_arabic ? 'العروض' : 'Deals'; ?></a>

            <!-- من نحن -->
            <a
                class="px-3 py-1.5 rounded-lg whitespace-nowrap transition-colors text-white/95 hover:bg-white/20 hover:text-white font-medium"
                href="<?php echo esc_url(home_url('/about')); ?>"
            ><?php echo $is_arabic ? 'من نحن' : 'About Us'; ?></a>
        </nav>

        <!-- Online prescription CTA -->
        <a
            class="flex items-center gap-1.5 px-space-md py-1.5 rounded-lg bg-[#16A085] text-white hover:bg-[#0E6655] border border-white/25 font-label-md text-label-md font-bold shadow-sm transition-all whitespace-nowrap flex-shrink-0"
            href="https://wa.me/201556859030"
            target="_blank" rel="noopener noreferrer"
        >
            <span class="material-symbols-outlined text-[16px]">receipt_long</span>
            <span><?php echo $is_arabic ? 'روشتة أونلاين' : 'Online Prescription'; ?></span>
        </a>
    </div>

</header>

<!-- Mobile Sidebar -->
<div id="mobile-sidebar" class="fixed inset-0 z-[200] hidden">
    <!-- Overlay -->
    <div id="sidebar-overlay" class="absolute inset-0 bg-black/50"></div>
    
    <!-- Sidebar content -->
    <div id="sidebar-content" class="absolute top-0 h-full w-80 bg-[#117A65] text-white shadow-2xl <?php echo is_rtl() ? 'right-0' : 'left-0'; ?> overflow-y-auto">
        <div class="p-4 space-y-4">
            <!-- Close button -->
            <div class="flex items-center justify-between mb-4">
                <span class="font-title-lg text-title-lg font-bold">
                    <?php 
                    $is_arabic = in_array($current_locale, array('ar', 'ar_AR'));
                    echo $is_arabic ? 'القائمة' : 'Menu';
                    ?>
                </span>
                <button id="sidebar-close" class="p-2 rounded-lg hover:bg-white/20 transition-colors" aria-label="<?php echo $is_arabic ? 'إغلاق' : 'Close'; ?>">
                    <span class="material-symbols-outlined text-[24px]">close</span>
                </button>
            </div>

            <!-- Language switch -->
            <nav class="flex items-center gap-2 px-4 py-2 border-b border-white/20 mb-4">
                <?php
                if ($is_arabic) :
                ?>
                    <span class="px-3 py-1.5 text-white/80 font-medium">العربية</span>
                    <span class="opacity-50">|</span>
                    <a href="<?php echo esc_url(baraa_pharmacy_language_switch_url('en_US')); ?>" lang="en" hreflang="en" class="px-3 py-1.5 rounded-lg hover:bg-white/20 transition-colors font-medium text-white/80">
                        English
                    </a>
                <?php else : ?>
                    <a href="<?php echo esc_url(baraa_pharmacy_language_switch_url('ar')); ?>" lang="ar" hreflang="ar" class="px-3 py-1.5 rounded-lg hover:bg-white/20 transition-colors font-medium text-white/80">
                        العربية
                    </a>
                    <span class="opacity-50">|</span>
                    <span class="px-3 py-1.5 text-white/80 font-medium">English</span>
                <?php endif; ?>
            </nav>

            <!-- Navigation links -->
            <nav class="space-y-2">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="block px-4 py-3 rounded-lg hover:bg-white/20 transition-colors font-medium">
                    <?php echo $is_arabic ? 'الرئيسية' : 'Home'; ?>
                </a>
                
                <!-- Shop with subcategories -->
                <div class="mobile-category-group">
                    <button class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-white/20 transition-colors font-medium" data-toggle="shop-submenu">
                        <?php echo $is_arabic ? 'تسوق' : 'Shop'; ?>
                        <span class="material-symbols-outlined text-[18px] sidebar-arrow">expand_more</span>
                    </button>
                    <div id="shop-submenu" class="hidden pl-4 mt-2 space-y-2">
                        <?php
                        $parent_categories = get_terms(array(
                            'taxonomy'   => 'product_cat',
                            'hide_empty' => false,
                            'parent'     => 0,
                            'number'     => 0,
                            'orderby'    => 'name',
                            'order'      => 'ASC',
                        ));
                        
                        if (!is_wp_error($parent_categories) && !empty($parent_categories)) {
                            foreach ($parent_categories as $parent_cat) {
                                $parent_url = get_term_link($parent_cat);
                                if (is_wp_error($parent_url)) {
                                    continue;
                                }
                                
                                $subcategories = get_terms(array(
                                    'taxonomy'   => 'product_cat',
                                    'hide_empty' => false,
                                    'parent'     => $parent_cat->term_id,
                                    'number'     => 0,
                                ));
                                ?>
                                <div class="mobile-subcategory-group">
                                    <button class="w-full flex items-center justify-between px-3 py-2 rounded-lg hover:bg-white/10 transition-colors text-sm" data-toggle="sub-<?php echo esc_attr($parent_cat->term_id); ?>">
                                        <?php echo esc_html($parent_cat->name); ?>
                                        <?php if (!empty($subcategories) && !is_wp_error($subcategories)) : ?>
                                            <span class="material-symbols-outlined text-[16px] sidebar-arrow">expand_more</span>
                                        <?php endif; ?>
                                    </button>
                                    <?php if (!empty($subcategories) && !is_wp_error($subcategories)) : ?>
                                        <div id="sub-<?php echo esc_attr($parent_cat->term_id); ?>" class="hidden pl-3 mt-1 space-y-1">
                                            <?php foreach ($subcategories as $sub_cat) {
                                                $sub_cat_url = get_term_link($sub_cat);
                                                if (is_wp_error($sub_cat_url)) {
                                                    continue;
                                                }
                                                ?>
                                                <a href="<?php echo esc_url($sub_cat_url); ?>" class="block px-3 py-1.5 rounded-lg hover:bg-white/10 transition-colors text-xs">
                                                    <?php echo esc_html($sub_cat->name); ?>
                                                </a>
                                            <?php } ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>
                </div>

                <a href="<?php echo esc_url(home_url('/deals')); ?>" class="block px-4 py-3 rounded-lg hover:bg-white/20 transition-colors font-medium">
                    <?php echo $is_arabic ? 'العروض' : 'Deals'; ?>
                </a>
                
                <a href="<?php echo esc_url(home_url('/about')); ?>" class="block px-4 py-3 rounded-lg hover:bg-white/20 transition-colors font-medium">
                    <?php echo $is_arabic ? 'من نحن' : 'About Us'; ?>
                </a>
            </nav>

            <!-- Online prescription CTA -->
            <a
                href="https://wa.me/201556859030"
                target="_blank"
                rel="noopener noreferrer"
                class="flex items-center gap-2 px-4 py-3 rounded-lg bg-[#16A085] hover:bg-[#0E6655] font-bold transition-colors"
            >
                <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                <span><?php echo $is_arabic ? 'روشتة أونلاين' : 'Online Prescription'; ?></span>
            </a>
        </div>
    </div>
</div>

<script>
(function() {
    const menuToggle = document.getElementById('mobile-menu-toggle');
    const sidebar = document.getElementById('mobile-sidebar');
    const sidebarClose = document.getElementById('sidebar-close');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    const sidebarContent = document.getElementById('sidebar-content');
    
    function openSidebar() {
        sidebar.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    
    function closeSidebar() {
        sidebar.classList.add('hidden');
        document.body.style.overflow = '';
    }
    
    if (menuToggle) {
        menuToggle.addEventListener('click', openSidebar);
    }
    
    if (sidebarClose) {
        sidebarClose.addEventListener('click', closeSidebar);
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
    }
    
    // Mobile submenu toggles
    const toggleButtons = document.querySelectorAll('[data-toggle]');
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-toggle');
            const target = document.getElementById(targetId);
            const arrow = this.querySelector('.sidebar-arrow');
            
            if (target) {
                target.classList.toggle('hidden');
                if (arrow) {
                    arrow.textContent = target.classList.contains('hidden') ? 'expand_more' : 'expand_less';
                }
            }
        });
    });
})();
</script>

<!-- Spacer below fixed header
     Top bar is now inside header (not separate)
     Mobile:  top-bar(~40) + main(~88) + nav(~48) = ~176px
     Desktop: top-bar(~40) + main(~96) + nav(~48) = ~184px
     ومع شريط العروض يزيد ~34px على الارتفاعين -->
<div class="<?php echo $offer_ribbon ? 'h-[210px] lg:h-[218px]' : ($show_top_bar ? 'h-[176px] lg:h-[184px]' : 'h-[136px] lg:h-[144px]'); ?>" aria-hidden="true"></div>
