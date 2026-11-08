<?php
/**
 * Shop / Product Archive — Baraa Pharmacy
 * Matches the detailed pharmacy catalog design.
 */
if (!defined('ABSPATH')) {
    exit;
}

get_header();

/* ── WooCommerce loop setup ───────────────────────────────── */
woocommerce_product_loop_start(false);

$shop_title       = is_product_taxonomy() ? woocommerce_page_title(false) : __('جميع المنتجات', 'baraa-pharmacy');
$shop_description = is_product_taxonomy() ? term_description() : __('دليلك المعتمد للأدوية الأصلية المسجلة بهيئة الدواء المصرية (EDA). وفر جرعاتك الشهرية مع استشارة إكلينيكية فورية وتذكير تلقائي قبل نفاد العبوة.', 'baraa-pharmacy');
$filter_action    = wc_get_page_permalink('shop');

if (is_product_taxonomy()) {
    $current_term = get_queried_object();
    if ($current_term instanceof WP_Term) {
        $term_url = get_term_link($current_term);
        if (!is_wp_error($term_url)) {
            $filter_action = $term_url;
        }
    }
}

$min_price     = isset($_GET['min_price']) && is_scalar($_GET['min_price']) ? wc_format_decimal(wp_unslash($_GET['min_price'])) : '';
$max_price     = isset($_GET['max_price']) && is_scalar($_GET['max_price']) ? wc_format_decimal(wp_unslash($_GET['max_price'])) : '';
$current_order = isset($_GET['orderby']) && is_scalar($_GET['orderby']) ? sanitize_text_field(wp_unslash($_GET['orderby'])) : get_option('woocommerce_default_catalog_orderby', 'menu_order');

$sort_options = array(
    'menu_order'  => __('الأكثر مبيعاً وتكراراً', 'baraa-pharmacy'),
    'popularity'  => __('الأكثر شعبية', 'baraa-pharmacy'),
    'rating'      => __('الأعلى تقييماً', 'baraa-pharmacy'),
    'date'        => __('الأحدث أولاً', 'baraa-pharmacy'),
    'price'       => __('السعر: من الأقل للأعلى', 'baraa-pharmacy'),
    'price-desc'  => __('السعر: من الأعلى للأقل', 'baraa-pharmacy'),
);
if (!array_key_exists($current_order, $sort_options)) {
    $current_order = 'menu_order';
}

$total       = (int) wc_get_loop_prop('total');
$per_page    = max(1, (int) wc_get_loop_prop('per_page'));
$current_pg  = max(1, (int) wc_get_loop_prop('current_page'));
$first       = ($per_page * ($current_pg - 1)) + 1;
$last        = min($total, $per_page * $current_pg);

$all_cats = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => true, 'orderby' => 'name', 'order' => 'ASC'));
if (is_wp_error($all_cats)) {
    $all_cats = array();
}

do_action('woocommerce_before_main_content');
?>

<div class="flex flex-col w-full">

<!-- ═══════════════════════════════════════════════════════
     MAIN GRID — Sidebar (3 cols) + Catalog (9 cols)
════════════════════════════════════════════════════════ -->
<div class="w-full px-margin pb-space-xl">
<div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter items-start">

<!-- ══ SIDEBAR ══════════════════════════════════════════ -->
<aside class="lg:col-span-3 space-y-space-md lg:sticky lg:top-48" aria-label="<?php esc_attr_e('فلاتر المنتجات', 'baraa-pharmacy'); ?>">
<div class="bg-surface-container-lowest rounded-xl shadow-sm p-space-md space-y-space-lg">

    <!-- Header -->
    <div class="flex items-center justify-between pb-space-sm border-b border-outline-variant/30">
        <div class="flex items-center gap-2 text-on-surface font-title-lg text-title-lg font-bold">
            <span class="material-symbols-outlined text-primary text-[22px]" aria-hidden="true">tune</span>
            <span><?php esc_html_e('تصفية الأدوية', 'baraa-pharmacy'); ?></span>
        </div>
        <a href="<?php echo esc_url($filter_action); ?>" class="font-label-sm text-label-sm text-secondary hover:underline flex items-center gap-0.5">
            <span class="material-symbols-outlined text-[14px]" aria-hidden="true">refresh</span>
            <?php esc_html_e('إعادة ضبط', 'baraa-pharmacy'); ?>
        </a>
    </div>

    <!-- Rx / OTC toggle -->
    <div class="space-y-space-sm">
        <p class="font-title-md text-title-md font-bold text-on-surface"><?php esc_html_e('نوع الصرف الدوائي', 'baraa-pharmacy'); ?></p>
        <div class="grid grid-cols-2 gap-space-xs">
            <?php
            $rx_types = array(
                array('icon' => 'prescriptions', 'label' => 'روشتة Rx',  'class' => 'text-error',    'val' => 'rx'),
                array('icon' => 'check_circle',  'label' => 'متاح OTC',  'class' => 'text-primary',  'val' => 'otc'),
            );
            foreach ($rx_types as $rt) : ?>
                <label class="flex items-center gap-2 p-2 rounded-lg bg-surface-container-low cursor-pointer hover:bg-surface-container transition-colors">
                    <input class="w-4 h-4 accent-primary rounded cursor-pointer" type="checkbox" name="rx_type" value="<?php echo esc_attr($rt['val']); ?>" checked />
                    <span class="font-label-md text-label-md font-bold <?php echo esc_attr($rt['class']); ?> flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px]" aria-hidden="true"><?php echo esc_html($rt['icon']); ?></span>
                        <?php echo esc_html($rt['label']); ?>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Categories -->
    <div class="space-y-space-sm">
        <div class="flex items-center justify-between">
            <p class="font-title-md text-title-md font-bold text-on-surface"><?php esc_html_e('التصنيفات', 'baraa-pharmacy'); ?></p>
            <span class="font-label-sm text-label-sm text-outline"><?php echo esc_html(number_format_i18n(count($all_cats))); ?></span>
        </div>
        <nav class="flex flex-col gap-1" aria-label="<?php esc_attr_e('تصنيفات المنتجات', 'baraa-pharmacy'); ?>">
            <a
                class="flex items-center justify-between px-3 py-2 rounded-lg font-label-md text-label-md transition-colors <?php echo (is_shop() && !is_product_taxonomy()) ? 'bg-primary text-on-primary font-bold' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface'; ?>"
                href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"
            >
                <span><?php esc_html_e('جميع المنتجات', 'baraa-pharmacy'); ?></span>
            </a>
            <?php foreach ($all_cats as $cat) :
                $cat_url = get_term_link($cat);
                if (is_wp_error($cat_url)) continue;
                $is_cur  = is_product_category($cat->slug);
            ?>
                <a
                    class="flex items-center justify-between px-3 py-2 rounded-lg font-label-md text-label-md transition-colors <?php echo $is_cur ? 'bg-primary text-on-primary font-bold' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface'; ?>"
                    href="<?php echo esc_url($cat_url); ?>"
                    <?php echo $is_cur ? 'aria-current="page"' : ''; ?>
                >
                    <span><?php echo esc_html($cat->name); ?></span>
                    <span class="px-2 py-0.5 rounded-full font-label-sm text-[10px] <?php echo $is_cur ? 'bg-primary-fixed text-on-primary-fixed' : 'bg-surface-container text-on-surface-variant'; ?>">
                        <?php echo esc_html(number_format_i18n($cat->count)); ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <!-- Dosage form pills -->
    <div class="space-y-space-sm">
        <p class="font-title-md text-title-md font-bold text-on-surface"><?php esc_html_e('الشكل الصيدلاني', 'baraa-pharmacy'); ?></p>
        <div class="flex flex-wrap gap-1.5">
            <?php
            $forms = array(
                array('label' => __('أقراص (Tablets)', 'baraa-pharmacy'), 'active' => true),
                array('label' => __('كبسولات', 'baraa-pharmacy'),          'active' => false),
                array('label' => __('حقن', 'baraa-pharmacy'),              'active' => false),
                array('label' => __('شراب ونقط', 'baraa-pharmacy'),        'active' => false),
                array('label' => __('فوار وأكياس', 'baraa-pharmacy'),      'active' => false),
            );
            foreach ($forms as $f) : ?>
                <button type="button" class="px-3 py-1.5 rounded-lg font-label-md text-label-md transition-colors <?php echo $f['active'] ? 'bg-primary text-on-primary font-semibold' : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-high'; ?>">
                    <?php echo esc_html($f['label']); ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Price filter -->
    <div class="space-y-space-sm">
        <div class="flex items-center justify-between">
            <p class="font-title-md text-title-md font-bold text-on-surface"><?php esc_html_e('نطاق السعر (ج.م)', 'baraa-pharmacy'); ?></p>
            <span class="font-label-md text-label-md font-bold text-primary" id="baraa-price-output">
                <?php echo $max_price ? esc_html(__('حتى', 'baraa-pharmacy') . ' ' . $max_price . ' ج.م') : esc_html__('جميع الأسعار', 'baraa-pharmacy'); ?>
            </span>
        </div>
        <form method="get" action="<?php echo esc_url($filter_action); ?>">
            <?php wc_query_string_form_fields(null, array('min_price', 'max_price', 'paged'), '', false); ?>
            <input
                class="w-full accent-primary h-2 bg-surface-container-highest rounded-lg cursor-pointer"
                type="range"
                id="baraa-price-slider"
                name="max_price"
                min="10" max="2000" step="10"
                value="<?php echo esc_attr($max_price ?: 2000); ?>"
                oninput="document.getElementById('baraa-price-output').textContent = '<?php echo esc_js(__('حتى', 'baraa-pharmacy')); ?> ' + this.value + ' ج.م'"
            />
            <div class="flex items-center justify-between font-label-sm text-label-sm text-outline mt-1">
                <span>10 ج.م</span>
                <span>2000 ج.م</span>
            </div>
            <button type="submit" class="w-full mt-space-sm py-2 rounded-lg bg-primary text-on-primary font-label-md text-label-md font-bold hover:bg-primary-container transition-colors flex items-center justify-center gap-1.5">
                <span class="material-symbols-outlined text-[16px]" aria-hidden="true">filter_list</span>
                <?php esc_html_e('تطبيق الفلتر', 'baraa-pharmacy'); ?>
            </button>
            <?php if ($min_price || $max_price) : ?>
                <a href="<?php echo esc_url($filter_action); ?>" class="block text-center font-label-sm text-label-sm text-outline hover:text-error transition-colors mt-1">
                    <?php esc_html_e('مسح الفلتر', 'baraa-pharmacy'); ?>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Cold chain badge -->
    <div class="p-space-sm rounded-lg bg-surface-container text-on-surface space-y-1 text-right">
        <div class="flex items-center gap-1.5 text-primary font-bold font-title-md text-title-md">
            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">ac_unit</span>
            <span><?php esc_html_e('سلسلة تبريد دوائي معتمدة', 'baraa-pharmacy'); ?></span>
        </div>
        <p class="font-body-sm text-body-sm text-on-surface-variant">
            <?php esc_html_e('تضمن صيدليات براءة الحفظ المثالي بدرجات 2-8 مئوية للأدوية الحساسة داخل حقائب مبردة معتمدة.', 'baraa-pharmacy'); ?>
        </p>
    </div>

</div>
</aside>

<!-- ══ MAIN CATALOG ══════════════════════════════════════ -->
<main class="lg:col-span-9 space-y-space-md" id="main">

    <?php do_action('woocommerce_before_shop_loop'); ?>

    <!-- Category header card -->
    <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm space-y-space-md">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-space-md">
            <div>
                <!-- Breadcrumb -->
                <nav class="flex items-center gap-1.5 text-outline font-label-sm text-label-sm mb-1 flex-wrap" aria-label="<?php esc_attr_e('مسار التنقل', 'baraa-pharmacy'); ?>">
                    <a class="hover:text-primary transition-colors" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('الرئيسية', 'baraa-pharmacy'); ?></a>
                    <span class="material-symbols-outlined text-[14px]" aria-hidden="true">arrow_left</span>
                    <?php if (is_product_taxonomy()) : ?>
                        <a class="hover:text-primary transition-colors" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"><?php esc_html_e('المتجر', 'baraa-pharmacy'); ?></a>
                        <span class="material-symbols-outlined text-[14px]" aria-hidden="true">arrow_left</span>
                    <?php endif; ?>
                    <span class="text-primary font-bold" aria-current="page"><?php echo esc_html($shop_title); ?></span>
                </nav>

                <h1 class="font-headline-lg text-headline-lg font-bold text-on-surface">
                    <?php echo esc_html($shop_title); ?>
                </h1>
                <?php if ($shop_description) : ?>
                    <div class="font-body-md text-body-md text-on-surface-variant max-w-2xl mt-1">
                        <?php echo wp_kses_post($shop_description); ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Total counter -->
            <?php if ($total > 0) : ?>
            <div class="flex items-center gap-2 self-start md:self-auto px-space-md py-2 rounded-xl bg-surface-container-low shrink-0">
                <span class="material-symbols-outlined text-primary text-[24px]" aria-hidden="true">medication_liquid</span>
                <div class="text-right">
                    <div class="font-headline-md text-headline-md font-bold text-primary leading-none">
                        <?php echo esc_html(number_format_i18n($total)); ?>
                    </div>
                    <div class="font-label-sm text-label-sm text-outline"><?php esc_html_e('صنف دوائي متوفر', 'baraa-pharmacy'); ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Safety advisory grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-space-sm pt-space-xs">
            <?php
            $advisories = array(
                array('icon' => 'verified_user',  'color' => 'text-primary',    'title' => __('أصلي 100% ومفحوص', 'baraa-pharmacy'),        'desc' => __('توريد مباشر من الوكلاء والشركات المصنعة مع باركود التتبع.', 'baraa-pharmacy')),
                array('icon' => 'thermostat',      'color' => 'text-secondary',   'title' => __('شروط حفظ صيدلانية', 'baraa-pharmacy'),       'desc' => __('تحت 25° مئوية بعيداً عن الرطوبة والشمس المباشرة لحفظ الفاعلية.', 'baraa-pharmacy')),
                array('icon' => 'contact_support', 'color' => 'text-tertiary',    'title' => __('مراجعة التفاعلات', 'baraa-pharmacy'),         'desc' => __('استشر صيدلي براءة قبل دمج أي مكمل غذائي مع أدويتك الدائمة.', 'baraa-pharmacy')),
            );
            foreach ($advisories as $a) : ?>
                <div class="flex items-start gap-2.5 p-3 rounded-lg bg-surface-container-low">
                    <span class="material-symbols-outlined <?php echo esc_attr($a['color']); ?> text-[20px] shrink-0 mt-0.5" aria-hidden="true"><?php echo esc_html($a['icon']); ?></span>
                    <div class="text-right">
                        <span class="font-title-md text-title-md font-bold block <?php echo esc_attr($a['color']); ?>"><?php echo esc_html($a['title']); ?></span>
                        <span class="font-body-sm text-body-sm text-on-surface-variant"><?php echo esc_html($a['desc']); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Toolbar: active filter chips + sort -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-space-sm bg-surface-container-lowest p-space-sm rounded-xl shadow-sm">

        <!-- Active filter chips + result count -->
        <div class="flex items-center gap-2 overflow-x-auto w-full sm:w-auto py-1 flex-wrap">
            <?php if ($total > 0) : ?>
                <span class="font-label-md text-label-md text-on-surface-variant shrink-0" role="status" aria-live="polite">
                    <?php
                    if ($total <= $per_page) {
                        printf(esc_html(_n('عرض %s منتج', 'عرض %s منتجات', $total, 'baraa-pharmacy')), esc_html(number_format_i18n($total)));
                    } else {
                        printf(esc_html__('عرض %1$s–%2$s من %3$s منتج', 'baraa-pharmacy'), esc_html(number_format_i18n($first)), esc_html(number_format_i18n($last)), esc_html(number_format_i18n($total)));
                    }
                    ?>
                </span>
            <?php endif; ?>

            <?php if (is_product_taxonomy()) : ?>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-surface-container font-label-sm text-label-sm text-on-surface">
                    <?php echo esc_html($shop_title); ?>
                    <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" aria-label="<?php esc_attr_e('مسح الفلتر', 'baraa-pharmacy'); ?>">
                        <span class="material-symbols-outlined text-[14px] hover:text-error cursor-pointer" aria-hidden="true">close</span>
                    </a>
                </span>
            <?php endif; ?>

            <?php if ($min_price || $max_price) : ?>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-surface-container font-label-sm text-label-sm text-on-surface">
                    <?php echo esc_html(__('حتى', 'baraa-pharmacy') . ' ' . ($max_price ?: '2000') . ' ج.م'); ?>
                    <a href="<?php echo esc_url($filter_action); ?>" aria-label="<?php esc_attr_e('مسح فلتر السعر', 'baraa-pharmacy'); ?>">
                        <span class="material-symbols-outlined text-[14px] hover:text-error cursor-pointer" aria-hidden="true">close</span>
                    </a>
                </span>
            <?php endif; ?>
        </div>

        <!-- Sort form -->
        <div class="flex items-center gap-space-sm w-full sm:w-auto justify-end shrink-0">
            <span class="font-label-md text-label-md text-on-surface-variant shrink-0"><?php esc_html_e('الترتيب حسب:', 'baraa-pharmacy'); ?></span>
            <form method="get" action="<?php echo esc_url($filter_action); ?>" class="relative">
                <?php wc_query_string_form_fields(null, array('orderby', 'submit', 'paged'), '', false); ?>
                <select
                    name="orderby"
                    id="baraa-shop-orderby"
                    class="appearance-none bg-surface-container-low text-on-surface font-label-md text-label-md font-semibold py-2 pr-3 pl-8 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary cursor-pointer"
                    onchange="this.form.submit()"
                >
                    <?php foreach ($sort_options as $val => $label) : ?>
                        <option value="<?php echo esc_attr($val); ?>" <?php selected($current_order, $val); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="material-symbols-outlined absolute left-2 top-2.5 pointer-events-none text-outline text-[18px]" aria-hidden="true">expand_more</span>
            </form>
        </div>
    </div>

    <?php if (have_posts()) : ?>

    <!-- Product grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-gutter">
        <?php
        while (have_posts()) :
            the_post();
            global $product;
            if (!($product instanceof WC_Product) || !$product->is_visible()) {
                continue;
            }

            $p_id       = $product->get_id();
            $p_name     = $product->get_name();
            $p_price    = $product->get_price_html();
            $p_link     = $product->get_permalink();
            $p_img      = $product->get_image('woocommerce_thumbnail', array('class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-300'));
            $p_cats     = wc_get_product_category_list($p_id, ', ');
            $p_short    = $product->get_short_description();
            $p_sku      = $product->get_sku();
            $p_on_sale  = $product->is_on_sale();
            $p_in_stock = $product->is_in_stock();
            $p_rating   = $product->get_average_rating();
            $p_review   = $product->get_review_count();
            $is_rx      = has_term('rx', 'product_tag', $p_id) || has_term('روشتة', 'product_tag', $p_id);
            $can_add    = $product->is_type('simple') && $product->is_purchasable() && $p_in_stock;
            $add_url    = $can_add ? $product->add_to_cart_url() : $p_link;

            // Extract first attribute (e.g. active ingredient) if available
            $attributes = $product->get_attributes();
            $active_ing = '';
            foreach ($attributes as $attr) {
                if ($attr instanceof WC_Product_Attribute) {
                    $active_ing = $attr->get_name() . ': ' . implode(', ', $attr->get_options());
                    break;
                }
            }
        ?>
        <div class="group bg-surface-container-lowest rounded-xl shadow-sm hover:shadow-md transition-all flex flex-col justify-between overflow-hidden">

            <div class="p-space-md space-y-space-sm">
                <!-- Image -->
                <div class="relative w-full aspect-square rounded-lg overflow-hidden bg-surface-container-low flex items-center justify-center">
                    <a href="<?php echo esc_url($p_link); ?>" tabindex="-1" aria-hidden="true">
                        <?php echo wp_kses_post($p_img); ?>
                    </a>

                    <!-- Rx / OTC badge -->
                    <?php if ($is_rx) : ?>
                        <span class="absolute top-2 right-2 px-2 py-0.5 rounded-full bg-error-container text-on-error-container font-label-sm text-label-sm font-bold flex items-center gap-1 shadow-sm">
                            <span class="material-symbols-outlined text-[12px]" aria-hidden="true">prescriptions</span>
                            Rx
                        </span>
                    <?php else : ?>
                        <span class="absolute top-2 right-2 px-2 py-0.5 rounded-full bg-primary-fixed text-on-primary-fixed font-label-sm text-label-sm font-bold flex items-center gap-1 shadow-sm">
                            <span class="material-symbols-outlined text-[12px]" aria-hidden="true">check_circle</span>
                            OTC
                        </span>
                    <?php endif; ?>

                    <!-- On sale badge -->
                    <?php if ($p_on_sale) : ?>
                        <span class="absolute bottom-2 right-2 px-2 py-0.5 rounded-full bg-tertiary-fixed text-on-tertiary-fixed font-label-sm text-label-sm font-bold shadow-sm">
                            <?php esc_html_e('عرض', 'baraa-pharmacy'); ?>
                        </span>
                    <?php endif; ?>

                    <!-- Wishlist button -->
                    <button type="button" class="absolute top-2 left-2 p-1 rounded-full bg-surface-container-lowest/80 text-outline hover:text-error transition-colors backdrop-blur-sm" aria-label="<?php echo esc_attr(sprintf(__('إضافة %s للمفضلة', 'baraa-pharmacy'), $p_name)); ?>">
                        <span class="material-symbols-outlined text-[18px]" aria-hidden="true">favorite</span>
                    </button>
                </div>

                <!-- Meta -->
                <div class="space-y-1 text-right">
                    <?php if ($p_cats) : ?>
                        <span class="font-label-sm text-label-sm text-primary font-bold block">
                            <?php echo wp_kses_post($p_cats); ?>
                        </span>
                    <?php endif; ?>
                    <h3 class="font-title-lg text-title-lg font-bold text-on-surface leading-tight">
                        <a class="hover:text-primary transition-colors" href="<?php echo esc_url($p_link); ?>"><?php echo esc_html($p_name); ?></a>
                    </h3>
                    <?php if ($active_ing) : ?>
                        <div class="font-body-sm text-body-sm text-on-surface-variant flex items-center gap-1 flex-wrap">
                            <span class="font-semibold text-on-surface"><?php esc_html_e('المادة الفعالة:', 'baraa-pharmacy'); ?></span>
                            <span><?php echo esc_html($active_ing); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($p_short) : ?>
                        <div class="font-label-sm text-label-sm text-outline line-clamp-1">
                            <?php echo wp_kses_post($p_short); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Rating -->
                <?php if ($p_rating > 0) : ?>
                    <div class="flex items-center gap-1.5">
                        <div class="flex items-center gap-0.5">
                            <?php for ($i = 1; $i <= 5; $i++) : ?>
                                <span class="material-symbols-outlined text-[14px] text-amber-400" style="font-variation-settings:'FILL' 1" aria-hidden="true">
                                    <?php echo $i <= round($p_rating) ? 'star' : 'star_outline'; ?>
                                </span>
                            <?php endfor; ?>
                        </div>
                        <span class="font-label-sm text-label-sm text-on-surface-variant">(<?php echo esc_html(number_format_i18n($p_review)); ?>)</span>
                    </div>
                <?php endif; ?>

                <!-- Storage tip (from product note attribute if exists) -->
                <div class="p-2 rounded bg-surface-container-low text-on-surface-variant font-label-sm text-label-sm flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-primary text-[16px]" aria-hidden="true">thermostat</span>
                    <span>
                        <?php echo $p_in_stock
                            ? esc_html__('متوفر — توصيل سريع خلال 45-60 دقيقة', 'baraa-pharmacy')
                            : esc_html__('غير متوفر حالياً في المخزون', 'baraa-pharmacy'); ?>
                    </span>
                </div>
            </div>

            <!-- Footer: price + CTA -->
            <div class="p-space-md pt-0 mt-auto space-y-space-sm border-t border-surface-container">
                <div class="flex items-center justify-between pt-2">
                    <div class="flex flex-col text-right">
                        <span class="font-headline-md text-headline-md font-bold text-primary">
                            <?php echo wp_kses_post($p_price); ?>
                        </span>
                        <span class="font-label-sm text-label-sm text-outline">
                            <?php esc_html_e('السعر الجبري الرسمي', 'baraa-pharmacy'); ?>
                        </span>
                    </div>
                </div>
                <a
                    href="<?php echo esc_url($add_url); ?>"
                    class="w-full flex items-center justify-center gap-2 py-2.5 rounded-lg bg-primary text-on-primary font-label-md text-label-md font-bold hover:bg-primary-container transition-colors shadow-sm"
                    <?php if ($can_add) : ?>
                        data-quantity="1"
                        data-product_id="<?php echo esc_attr($p_id); ?>"
                        data-product_sku="<?php echo esc_attr($product->get_sku()); ?>"
                    <?php endif; ?>
                    aria-label="<?php echo $can_add ? esc_attr(sprintf(__('إضافة %s للسلة', 'baraa-pharmacy'), $p_name)) : esc_attr(sprintf(__('عرض %s', 'baraa-pharmacy'), $p_name)); ?>"
                >
                    <span class="material-symbols-outlined text-[18px]" aria-hidden="true">add_shopping_cart</span>
                    <span>
                        <?php echo $can_add
                            ? esc_html__('إضافة للروشتة الشهرية', 'baraa-pharmacy')
                            : esc_html__('عرض المنتج', 'baraa-pharmacy'); ?>
                    </span>
                </a>
            </div>
        </div>

        <?php endwhile; ?>
    </div>

    <!-- Patient safety advisory box -->
    <div class="rounded-xl bg-surface-container-low p-space-lg shadow-sm space-y-space-md mt-space-lg">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-primary-container text-on-primary flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[22px]" aria-hidden="true">health_and_safety</span>
            </div>
            <div>
                <h4 class="font-title-lg text-title-lg font-bold text-on-surface">
                    <?php esc_html_e('إرشادات صيدليات براءة لسلامة المرضى ومستخدمي أدوية الأمراض المزمنة', 'baraa-pharmacy'); ?>
                </h4>
                <p class="font-body-sm text-body-sm text-on-surface-variant">
                    <?php esc_html_e('مراجعة طبية إكلينيكية معتمدة بالتعاون مع نقابة صيادلة مصر', 'baraa-pharmacy'); ?>
                </p>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-space-md text-right font-body-sm text-body-sm text-on-surface">
            <?php
            $tips = array(
                array('icon' => 'alarm_on',       'title' => __('الانتظام في مواعيد الجرعات', 'baraa-pharmacy'),         'desc' => __('يساعد ضبط منبه الهاتف أو منظم الجرعات الأسبوعي في الحفاظ على تركيز ثابت للدواء في مجرى الدم.', 'baraa-pharmacy')),
                array('icon' => 'sync_problem',   'title' => __('عدم التغيير دون استشارة الصيدلي', 'baraa-pharmacy'),    'desc' => __('لا تبدّل دواء الضغط أو السكر بالبدائل التجارية دون التأكد من تكافؤ المادة الفعالة الحيوية وموافقة الطبيب.', 'baraa-pharmacy')),
            );
            foreach ($tips as $tip) : ?>
                <div class="bg-surface-container-lowest p-space-md rounded-lg space-y-1">
                    <span class="font-label-md text-label-md font-bold text-primary flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px]" aria-hidden="true"><?php echo esc_html($tip['icon']); ?></span>
                        <?php echo esc_html($tip['title']); ?>
                    </span>
                    <p class="text-on-surface-variant"><?php echo esc_html($tip['desc']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Pagination -->
    <nav class="flex items-center justify-between bg-surface-container-lowest p-space-md rounded-xl shadow-sm" aria-label="<?php esc_attr_e('تصفح الصفحات', 'baraa-pharmacy'); ?>">
        <div class="font-label-md text-label-md text-on-surface-variant">
            <?php printf(
                esc_html__('عرض %1$s–%2$s من أصل %3$s منتج', 'baraa-pharmacy'),
                esc_html(number_format_i18n($first)),
                esc_html(number_format_i18n($last)),
                esc_html(number_format_i18n($total))
            ); ?>
        </div>
        <div class="baraa-wc-pagination">
            <?php woocommerce_pagination(); ?>
        </div>
    </nav>

    <?php else : ?>

    <!-- Empty state -->
    <div class="bg-surface-container-lowest rounded-xl shadow-sm p-space-xl text-center flex flex-col items-center gap-space-md">
        <div class="w-20 h-20 rounded-full bg-surface-container flex items-center justify-center">
            <span class="material-symbols-outlined text-outline text-[40px]" aria-hidden="true">search_off</span>
        </div>
        <?php do_action('woocommerce_no_products_found'); ?>
        <p class="font-body-md text-body-md text-on-surface-variant"><?php esc_html_e('لا توجد منتجات تطابق الفلاتر الحالية.', 'baraa-pharmacy'); ?></p>
        <a class="px-space-lg py-3 rounded-lg bg-primary text-on-primary font-label-md text-label-md font-bold hover:bg-primary-container transition-colors" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">
            <?php esc_html_e('عرض جميع المنتجات', 'baraa-pharmacy'); ?>
        </a>
    </div>

    <?php endif; ?>

    <?php do_action('woocommerce_after_shop_loop'); ?>

</main>
</div>
</div>

<!-- Toast notification -->
<div
    class="fixed bottom-6 right-6 z-50 transform translate-y-32 transition-transform duration-300 ease-out bg-surface-container-lowest text-on-surface p-space-md rounded-xl shadow-2xl flex items-center gap-3 border-r-4 border-primary max-w-sm pointer-events-none"
    id="baraa-shop-toast"
    aria-live="polite"
    aria-atomic="true"
>
    <div class="w-8 h-8 rounded-full bg-primary-fixed text-on-primary-fixed flex items-center justify-center shrink-0">
        <span class="material-symbols-outlined text-[18px]" aria-hidden="true">done</span>
    </div>
    <div class="text-right">
        <h5 class="font-title-md text-title-md font-bold text-on-surface" id="baraa-shop-toast-title"><?php esc_html_e('تمت الإضافة', 'baraa-pharmacy'); ?></h5>
        <p class="font-body-sm text-body-sm text-on-surface-variant" id="baraa-shop-toast-msg"><?php esc_html_e('تمت إضافة الصنف بنجاح للسلة.', 'baraa-pharmacy'); ?></p>
    </div>
</div>

<script>
(function () {
    /* Show toast when WooCommerce adds item to cart via AJAX */
    if (window.jQuery) {
        jQuery(document.body).on('added_to_cart', function (e, fragments, cart_hash, $btn) {
            var toast  = document.getElementById('baraa-shop-toast');
            var msgEl  = document.getElementById('baraa-shop-toast-msg');
            if (!toast) return;
            if ($btn && $btn.length) {
                var name = $btn.closest('.group').find('h3 a').text().trim();
                if (name && msgEl) msgEl.textContent = name;
            }
            toast.classList.remove('translate-y-32');
            toast.classList.add('translate-y-0');
            setTimeout(function () {
                toast.classList.remove('translate-y-0');
                toast.classList.add('translate-y-32');
            }, 3500);
        });
    }
})();
</script>

</div>

<?php
do_action('woocommerce_after_main_content');
get_footer();
?>
