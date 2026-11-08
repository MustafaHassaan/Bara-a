<?php
/**
 * Single Product Content — Baraa Pharmacy
 * Matches the detailed pharmacy design with all MD3 components.
 */
if (!defined('ABSPATH')) {
    exit;
}

global $product;

do_action('woocommerce_before_single_product');

if (post_password_required()) {
    echo get_the_password_form(); // phpcs:ignore
    return;
}

/* ── Helpers ─────────────────────────────────────────────────── */
$product_id   = $product->get_id();
$product_name = $product->get_name();
$sku          = $product->get_sku();
$in_stock     = $product->is_in_stock();
$is_rx        = has_term('rx', 'product_tag', $product_id)
             || has_term('روشتة', 'product_tag', $product_id);
$categories   = wc_get_product_category_list($product_id, ' › ');
$short_desc   = $product->get_short_description();
$description  = $product->get_description();

// Category terms with links for breadcrumb
$cat_terms = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'all'));
if (is_wp_error($cat_terms)) {
    $cat_terms = array();
}
// Sort: shallowest (parent=0) first
usort($cat_terms, function ($a, $b) { return $a->parent - $b->parent; });
$cat_trail = is_array($cat_terms) ? implode(' › ', wp_list_pluck($cat_terms, 'name')) : '';
?>

<article id="product-<?php the_ID(); ?>" <?php wc_product_class('', $product); ?>>

<!-- ═══════════════════════════════════════════════════════════
     BREADCRUMB
═══════════════════════════════════════════════════════════ -->
<nav class="flex items-center gap-2 text-label-sm font-label-sm text-on-surface-variant mb-space-md flex-wrap" aria-label="<?php esc_attr_e('مسار التنقل', 'baraa-pharmacy'); ?>">
    <a class="hover:text-primary transition-colors" href="<?php echo esc_url(home_url('/')); ?>">
        <?php esc_html_e('الرئيسية', 'baraa-pharmacy'); ?>
    </a>
    <?php if (!empty($cat_terms)) :
        foreach ($cat_terms as $cat_term) :
            if (!($cat_term instanceof WP_Term)) continue;
            $cat_url = get_term_link($cat_term);
    ?>
        <span class="material-symbols-outlined text-[14px] text-outline-variant" aria-hidden="true">chevron_left</span>
        <?php if (!is_wp_error($cat_url)) : ?>
            <a class="hover:text-primary transition-colors" href="<?php echo esc_url($cat_url); ?>">
                <?php echo esc_html($cat_term->name); ?>
            </a>
        <?php else : ?>
            <span class="text-on-surface-variant"><?php echo esc_html($cat_term->name); ?></span>
        <?php endif; ?>
    <?php endforeach; endif; ?>
    <span class="material-symbols-outlined text-[14px] text-outline-variant" aria-hidden="true">chevron_left</span>
    <span class="text-on-surface font-semibold" aria-current="page"><?php echo esc_html($product_name); ?></span>
</nav>

<!-- ═══════════════════════════════════════════════════════════
     MAIN LAYOUT — Gallery (right) + Summary (left)
═══════════════════════════════════════════════════════════ -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-gutter items-start">

    <!-- ── Gallery column ──────────────────────────────────────── -->
    <div class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">

        <!-- Gallery wrapper (WC hooks renders images here) -->
        <div class="p-space-md">
            <?php do_action('woocommerce_before_single_product_summary'); ?>
        </div>

        <!-- Image action pills -->
        <div class="flex items-center gap-space-sm px-space-md pb-space-md flex-wrap">
            <button type="button" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-surface-container text-on-surface-variant font-label-sm text-label-sm hover:bg-primary hover:text-on-primary transition-colors">
                <span class="material-symbols-outlined text-[16px]" aria-hidden="true">zoom_in</span>
                <?php esc_html_e('تكبير', 'baraa-pharmacy'); ?>
            </button>
            <?php
            $pills = array(
                array('icon' => 'inventory_2',   'label' => __('العلبة والباركود', 'baraa-pharmacy')),
                array('icon' => 'medication',    'label' => __('شريط الأقراص', 'baraa-pharmacy')),
                array('icon' => 'receipt_long',  'label' => __('النشرة المعتمدة', 'baraa-pharmacy')),
            );
            foreach ($pills as $p) : ?>
                <button type="button" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-surface-container text-on-surface-variant font-label-sm text-label-sm hover:bg-surface-container-high transition-colors">
                    <span class="material-symbols-outlined text-[16px]" aria-hidden="true"><?php echo esc_html($p['icon']); ?></span>
                    <?php echo esc_html($p['label']); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Storage protocol card -->
        <div class="mx-space-md mb-space-md rounded-xl bg-surface-container-low border border-outline-variant/30 p-space-md">
            <div class="flex items-center gap-2 mb-space-sm">
                <span class="material-symbols-outlined text-primary text-[20px]" aria-hidden="true">thermostat</span>
                <h3 class="font-title-md text-title-md text-on-surface font-bold">
                    <?php esc_html_e('بروتوكول التخزين والحفظ الصيدلاني', 'baraa-pharmacy'); ?>
                </h3>
            </div>
            <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed">
                <?php esc_html_e('يُحفظ تحت درجة حرارة أقل من 25° مئوية في بيئة جافة بعيداً عن أشعة الشمس المباشرة. يتم نقل الدواء في سيارات براءة المجهزة بنظام تبريد مراقب آلياً (Cold-Chain Logistics).', 'baraa-pharmacy'); ?>
            </p>
            <div class="flex items-center gap-2 mt-space-sm">
                <span class="material-symbols-outlined text-primary text-[16px]" aria-hidden="true">assignment_turned_in</span>
                <span class="font-label-sm text-label-sm text-on-surface-variant">
                    <?php esc_html_e('رقم التسجيل لدى هيئة الدواء المصرية', 'baraa-pharmacy'); ?>
                    <?php if ($sku) : ?><strong class="text-primary"> <?php echo esc_html($sku); ?></strong><?php endif; ?>
                </span>
                <button type="button" class="mr-auto flex items-center gap-1 text-primary font-label-sm text-label-sm hover:underline">
                    <span class="material-symbols-outlined text-[16px]" aria-hidden="true">qr_code_scanner</span>
                    <?php esc_html_e('تحقق من الباركود', 'baraa-pharmacy'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- ── Summary column ──────────────────────────────────────── -->
    <div class="bg-surface-container-lowest rounded-xl shadow-sm p-space-lg flex flex-col gap-space-md summary entry-summary">

        <!-- Trust badges row -->
        <div class="flex items-center gap-space-sm flex-wrap">
            <?php if (!empty($categories)) : ?>
                <span class="flex items-center gap-1 px-2.5 py-1 rounded-full bg-surface-container font-label-sm text-label-sm text-on-surface-variant">
                    <span class="material-symbols-outlined text-primary text-[14px]" aria-hidden="true">category</span>
                    <?php echo wp_kses_post($categories); ?>
                </span>
            <?php endif; ?>
            <?php if ($sku) : ?>
                <span class="flex items-center gap-1 px-2.5 py-1 rounded-full bg-primary-fixed/20 text-primary font-label-sm text-label-sm font-semibold">
                    <span class="material-symbols-outlined text-[14px]" style="font-variation-settings:'FILL' 1" aria-hidden="true">verified</span>
                    <?php printf(esc_html__('رمز الدواء الموحد: %s', 'baraa-pharmacy'), esc_html($sku)); ?>
                </span>
            <?php endif; ?>
            <span class="flex items-center gap-1 px-2.5 py-1 rounded-full bg-surface-container font-label-sm text-label-sm text-on-surface-variant">
                <span class="material-symbols-outlined text-primary text-[14px]" aria-hidden="true">local_pharmacy</span>
                <?php esc_html_e('تحديث التسعيرة الجبرية: يناير 2025', 'baraa-pharmacy'); ?>
            </span>
        </div>

        <!-- Rx / Authenticity badges -->
        <div class="flex items-center gap-space-sm flex-wrap">
            <?php if ($is_rx) : ?>
                <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-error-container/40 text-error font-label-md text-label-md font-bold">
                    <span class="material-symbols-outlined text-[16px]" aria-hidden="true">prescriptions</span>
                    <?php esc_html_e('Rx يتطلب روشتة معتمدة', 'baraa-pharmacy'); ?>
                </span>
            <?php else : ?>
                <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-primary-fixed/20 text-primary font-label-md text-label-md font-bold">
                    <span class="material-symbols-outlined text-[16px]" aria-hidden="true">check_circle</span>
                    <?php esc_html_e('بدون روشتة OTC', 'baraa-pharmacy'); ?>
                </span>
            <?php endif; ?>
            <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-secondary-fixed/30 text-secondary font-label-md text-label-md font-bold">
                <span class="material-symbols-outlined text-[16px]" aria-hidden="true">verified_user</span>
                <?php esc_html_e('أصلي 100% مستورد/محلي رسمي', 'baraa-pharmacy'); ?>
            </span>
        </div>

        <!-- Rating -->
        <div class="flex items-center gap-space-sm flex-wrap">
            <?php if ($product->get_rating_count() > 0) :
                $rating = $product->get_average_rating();
                $count  = $product->get_rating_count();
            ?>
                <div class="flex items-center gap-1">
                    <?php for ($i = 1; $i <= 5; $i++) :
                        if ($i <= floor($rating)) $icon = 'star';
                        elseif ($i - 0.5 <= $rating) $icon = 'star_half';
                        else $icon = 'star_outline';
                    ?>
                        <span class="material-symbols-outlined text-[18px] text-amber-400" style="font-variation-settings:'FILL' 1" aria-hidden="true"><?php echo esc_html($icon); ?></span>
                    <?php endfor; ?>
                    <strong class="font-title-md text-title-md text-on-surface mr-1"><?php echo esc_html(number_format($rating, 1)); ?></strong>
                </div>
                <a href="#reviews" class="font-body-sm text-body-sm text-on-surface-variant hover:text-primary transition-colors">
                    <?php printf(esc_html__('(%s تقييم موثق)', 'baraa-pharmacy'), esc_html(number_format_i18n($count))); ?>
                </a>
            <?php endif; ?>
        </div>

        <!-- Product title + WC summary hooks -->
        <?php do_action('woocommerce_single_product_summary'); ?>

        <!-- ── Stock + delivery status ──────────────────────── -->
        <div class="rounded-xl border <?php echo $in_stock ? 'border-primary/30 bg-primary-fixed/10' : 'border-error/30 bg-error-container/20'; ?> p-space-md">
            <div class="flex items-center gap-2 mb-2">
                <span class="w-2.5 h-2.5 rounded-full <?php echo $in_stock ? 'bg-primary' : 'bg-error'; ?> shrink-0" aria-hidden="true"></span>
                <span class="font-title-md text-title-md font-bold <?php echo $in_stock ? 'text-primary' : 'text-error'; ?>">
                    <?php echo $in_stock
                        ? esc_html__('متوفر بالمخزون', 'baraa-pharmacy')
                        : esc_html__('غير متوفر حالياً', 'baraa-pharmacy'); ?>
                </span>
            </div>
            <?php if ($in_stock) : ?>
                <p class="font-body-sm text-body-sm text-on-surface-variant flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-primary text-[16px]" aria-hidden="true">electric_bolt</span>
                    <?php esc_html_e('جاهز للتوصيل السريع (30-45 دقيقة)', 'baraa-pharmacy'); ?>
                </p>

                <!-- Branch availability -->
                <details class="mt-space-sm">
                    <summary class="flex items-center gap-2 cursor-pointer font-label-md text-label-md text-primary hover:underline list-none">
                        <span class="material-symbols-outlined text-[16px]" aria-hidden="true">storefront</span>
                        <?php esc_html_e('توفر الدواء في أقرب فروع صيدليات براءة إليك:', 'baraa-pharmacy'); ?>
                        <span class="material-symbols-outlined text-[16px]" aria-hidden="true">expand_more</span>
                    </summary>
                    <div class="mt-space-sm grid grid-cols-1 sm:grid-cols-2 gap-space-xs">
                        <?php
                        $branches = array(
                            array('name' => __('فرع التسعين (التجمع)', 'baraa-pharmacy'),        'status' => __('متوفر', 'baraa-pharmacy'),   'type' => 'ok'),
                            array('name' => __('فرع المعادي (النصر)', 'baraa-pharmacy'),         'status' => __('متوفر', 'baraa-pharmacy'),   'type' => 'ok'),
                            array('name' => __('فرع زايد (أركان)', 'baraa-pharmacy'),            'status' => __('متوفر', 'baraa-pharmacy'),   'type' => 'ok'),
                            array('name' => __('مصر الجديدة (تريومف)', 'baraa-pharmacy'),        'status' => '12 ' . __('عبوة', 'baraa-pharmacy'), 'type' => 'count'),
                            array('name' => __('المهندسين (جامعة الدول)', 'baraa-pharmacy'),     'status' => '8 '  . __('عبوات', 'baraa-pharmacy'), 'type' => 'count'),
                            array('name' => __('سموحة (الإسكندرية)', 'baraa-pharmacy'),          'status' => '15 ' . __('عبوة', 'baraa-pharmacy'), 'type' => 'count'),
                        );
                        foreach ($branches as $b) : ?>
                            <div class="flex items-center justify-between px-space-sm py-1.5 rounded-lg bg-surface-container-lowest border border-outline-variant/20">
                                <span class="font-label-sm text-label-sm text-on-surface"><?php echo esc_html($b['name']); ?></span>
                                <span class="font-label-sm text-label-sm font-bold <?php echo $b['type'] === 'ok' ? 'text-primary' : 'text-on-surface-variant'; ?>">
                                    <?php echo esc_html($b['status']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </details>
            <?php endif; ?>
        </div>

        <!-- ── Quantity + Add to cart (WC renders this) ──────── -->
        <div class="space-y-space-sm">
            <!-- WC cart form is rendered by do_action('woocommerce_single_product_summary') above.
                 The extra UI below wraps it visually. -->

            <!-- Pharmacy notice (Rx) -->
            <?php if ($is_rx) : ?>
                <div class="flex items-start gap-2 p-space-md rounded-xl bg-error-container/20 border border-error/20">
                    <span class="material-symbols-outlined text-error text-[20px] shrink-0 mt-0.5" aria-hidden="true">policy</span>
                    <p class="font-body-sm text-body-sm text-on-surface leading-relaxed">
                        <strong><?php esc_html_e('تنويه صيدلاني ملزم:', 'baraa-pharmacy'); ?></strong>
                        <?php esc_html_e('يخضع هذا الدواء لضوابط الصرف بالروشتة الطبية المعتمدة. سيقوم الصيدلي المختص بمراجعة تاريخ الروشتة وتطابق الجرعة أثناء تأكيد الطلب قبل مغادرة مندوب التوصيل.', 'baraa-pharmacy'); ?>
                    </p>
                </div>
            <?php endif; ?>

            <!-- Quick action row -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-space-sm">
                <button type="button" class="flex items-center justify-center gap-1.5 py-2.5 rounded-lg bg-surface-container text-on-surface-variant font-label-sm text-label-sm hover:bg-surface-container-high transition-colors">
                    <span class="material-symbols-outlined text-[18px] text-primary" aria-hidden="true">health_and_safety</span>
                    <?php esc_html_e('صرف بالتأمين الطبي', 'baraa-pharmacy'); ?>
                </button>
                <button type="button" class="flex items-center justify-center gap-1.5 py-2.5 rounded-lg bg-surface-container text-on-surface-variant font-label-sm text-label-sm hover:bg-surface-container-high transition-colors">
                    <span class="material-symbols-outlined text-[18px] text-secondary" aria-hidden="true">event_repeat</span>
                    <?php esc_html_e('جدولة تذكير الجرعة', 'baraa-pharmacy'); ?>
                </button>
                <button type="button" class="flex items-center justify-center gap-1.5 py-2.5 rounded-lg bg-surface-container text-on-surface-variant font-label-sm text-label-sm hover:bg-surface-container-high transition-colors">
                    <span class="material-symbols-outlined text-[18px] text-error" aria-hidden="true">favorite</span>
                    <?php esc_html_e('قائمة الأدوية الدورية', 'baraa-pharmacy'); ?>
                </button>
            </div>
        </div>

        <!-- ── Delivery / consultation / returns trust pills ─── -->
        <div class="space-y-space-sm">
            <?php
            $trust_items = array(
                array(
                    'icon'  => 'electric_moped',
                    'color' => 'text-primary',
                    'title' => __('توصيل صيدلي سريع خلال 45 دقيقة', 'baraa-pharmacy'),
                    'desc'  => __('حقائب نقل حرارية مبردة ومعتمدة لحماية جودة وفاعلية الدواء في شوارع القاهرة والجيزة.', 'baraa-pharmacy'),
                ),
                array(
                    'icon'  => 'clinical_notes',
                    'color' => 'text-secondary',
                    'title' => __('استشارة صيدلي إكلينيكي 24/7', 'baraa-pharmacy'),
                    'desc'  => __('تواصل مجاناً مع فريق الصيادلة للتأكد من مواعيد الجرعات والتفاعلات عبر الهاتف أو الدردشة.', 'baraa-pharmacy'),
                ),
                array(
                    'icon'  => 'published_with_changes',
                    'color' => 'text-tertiary',
                    'title' => __('استبدال طبقاً للقواعد الصحية', 'baraa-pharmacy'),
                    'desc'  => __('ضمان استبدال وإرجاع المستلزمات الطبية والأجهزة خلال 14 يوماً مع مراعاة سلامة تخزين الأدوية.', 'baraa-pharmacy'),
                ),
            );
            foreach ($trust_items as $t) : ?>
                <div class="flex items-start gap-space-sm p-space-sm rounded-xl bg-surface-container-low border border-outline-variant/20">
                    <span class="material-symbols-outlined <?php echo esc_attr($t['color']); ?> text-[22px] shrink-0 mt-0.5" aria-hidden="true"><?php echo esc_html($t['icon']); ?></span>
                    <div>
                        <p class="font-label-md text-label-md text-on-surface font-bold"><?php echo esc_html($t['title']); ?></p>
                        <p class="font-body-sm text-body-sm text-on-surface-variant mt-0.5"><?php echo esc_html($t['desc']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div><!-- /.summary -->
</div><!-- /.grid -->

<!-- ═══════════════════════════════════════════════════════════
     TABS — Usage, Side Effects, Interactions, Alternatives
═══════════════════════════════════════════════════════════ -->
<div class="mt-space-lg bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">

    <!-- Tab nav -->
    <div class="flex overflow-x-auto border-b border-outline-variant/30" role="tablist" aria-label="<?php esc_attr_e('تفاصيل المنتج', 'baraa-pharmacy'); ?>">
        <?php
        $tabs = array(
            'usage'         => array('icon' => 'format_list_bulleted', 'label' => __('دواعي الاستعمال والجرعات', 'baraa-pharmacy')),
            'pharmacology'  => array('icon' => 'science',              'label' => __('المادة الفعالة والآثار الجانبية', 'baraa-pharmacy')),
            'interactions'  => array('icon' => 'compare_arrows',       'label' => __('فاحص التداخلات الدوائية', 'baraa-pharmacy')),
            'alternatives'  => array('icon' => 'swap_horiz',           'label' => __('البدائل الدوائية المتكافئة', 'baraa-pharmacy')),
            'reviews'       => array('icon' => 'star',                 'label' => sprintf(
                /* translators: %s: review count */
                __('التقييمات (%s)', 'baraa-pharmacy'),
                $product->get_review_count()
            )),
        );
        foreach ($tabs as $key => $tab) : ?>
            <button
                class="baraa-tab-btn flex items-center gap-2 px-space-md py-3 whitespace-nowrap font-label-md text-label-md font-semibold transition-colors border-b-2 border-transparent hover:text-primary hover:border-primary text-on-surface-variant"
                data-tab="<?php echo esc_attr($key); ?>"
                role="tab"
                type="button"
            >
                <span class="material-symbols-outlined text-[18px]" aria-hidden="true"><?php echo esc_html($tab['icon']); ?></span>
                <?php echo esc_html($tab['label']); ?>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- Tab panels -->
    <div class="p-space-lg">

        <!-- Usage & Dosage — uses the real WooCommerce short description + full description -->
        <div class="baraa-tab-panel space-y-space-md" data-panel="usage">
            <div>
                <h3 class="font-title-lg text-title-lg text-on-surface font-bold flex items-center gap-2 mb-space-sm">
                    <span class="material-symbols-outlined text-primary text-[20px]" aria-hidden="true">health_and_safety</span>
                    <?php esc_html_e('دواعي الاستعمال الطبية الرئيسية', 'baraa-pharmacy'); ?>
                </h3>
                <?php if ($short_desc) : ?>
                    <div class="font-body-md text-body-md text-on-surface-variant leading-relaxed prose-pharmacy">
                        <?php echo wp_kses_post($short_desc); ?>
                    </div>
                <?php endif; ?>

                <?php if ($description) : ?>
                    <div class="font-body-md text-body-md text-on-surface-variant leading-relaxed mt-space-md prose-pharmacy">
                        <?php echo wp_kses_post($description); ?>
                    </div>
                <?php elseif (!$short_desc) : ?>
                    <p class="font-body-md text-body-md text-on-surface-variant"><?php esc_html_e('يُستخدم لعلاج الحالات المزمنة. راجع الصيدلي لمزيد من التفاصيل.', 'baraa-pharmacy'); ?></p>
                <?php endif; ?>
            </div>
            <div class="p-space-md rounded-xl bg-surface-container-low border border-outline-variant/30">
                <h4 class="font-label-md text-label-md text-on-surface font-bold flex items-center gap-2 mb-space-sm">
                    <span class="material-symbols-outlined text-primary text-[18px]" aria-hidden="true">schedule</span>
                    <?php esc_html_e('إرشادات الجرعة وطريقة الاستخدام', 'baraa-pharmacy'); ?>
                </h4>
                <p class="font-body-sm text-body-sm text-on-surface-variant"><?php esc_html_e('تؤخذ الجرعة طبقاً لتوجيهات الطبيب أو المدونة على العبوة. لا تزيد عن الجرعة الموصوفة.', 'baraa-pharmacy'); ?></p>
                <div class="mt-space-sm flex items-start gap-2 p-space-sm rounded-lg bg-error-container/20 border border-error/20">
                    <span class="material-symbols-outlined text-error text-[18px] shrink-0 mt-0.5" aria-hidden="true">warning</span>
                    <p class="font-body-sm text-body-sm text-on-surface font-semibold">
                        <?php esc_html_e('تنبيه سريري: لا تتوقف عن تناول الدواء بشكل مفاجئ دون استشارة الطبيب المعالج.', 'baraa-pharmacy'); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Pharmacology -->
        <div class="baraa-tab-panel space-y-space-md hidden" data-panel="pharmacology">
            <?php if ($description) : ?>
                <div class="font-body-md text-body-md text-on-surface-variant leading-relaxed prose max-w-none">
                    <?php echo wp_kses_post($description); ?>
                </div>
            <?php else : ?>
                <div class="space-y-space-md">
                    <div>
                        <h3 class="font-title-lg text-title-lg text-on-surface font-bold flex items-center gap-2 mb-space-sm">
                            <span class="material-symbols-outlined text-primary text-[20px]" aria-hidden="true">biotech</span>
                            <?php esc_html_e('آلية العمل الصيدلانية', 'baraa-pharmacy'); ?>
                        </h3>
                        <p class="font-body-md text-body-md text-on-surface-variant"><?php esc_html_e('تفاصيل المادة الفعالة وآلية عملها متاحة في النشرة الطبية الرسمية للمنتج.', 'baraa-pharmacy'); ?></p>
                    </div>
                    <div>
                        <h3 class="font-title-lg text-title-lg text-on-surface font-bold flex items-center gap-2 mb-space-sm">
                            <span class="material-symbols-outlined text-tertiary text-[20px]" aria-hidden="true">sentiment_dissatisfied</span>
                            <?php esc_html_e('الآثار الجانبية الشائعة', 'baraa-pharmacy'); ?>
                        </h3>
                        <p class="font-body-md text-body-md text-on-surface-variant"><?php esc_html_e('راجع النشرة الطبية المرفقة مع العبوة أو تحدث مع الصيدلي.', 'baraa-pharmacy'); ?></p>
                    </div>
                    <div>
                        <h3 class="font-title-lg text-title-lg text-on-surface font-bold flex items-center gap-2 mb-space-sm">
                            <span class="material-symbols-outlined text-error text-[20px]" aria-hidden="true">block</span>
                            <?php esc_html_e('موانع الاستعمال', 'baraa-pharmacy'); ?>
                        </h3>
                        <p class="font-body-md text-body-md text-on-surface-variant"><?php esc_html_e('أبلغ الصيدلي عن جميع الأمراض والأدوية الحالية قبل استخدام هذا المنتج.', 'baraa-pharmacy'); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Drug Interactions -->
        <div class="baraa-tab-panel space-y-space-md hidden" data-panel="interactions">
            <div>
                <h3 class="font-title-lg text-title-lg text-on-surface font-bold flex items-center gap-2 mb-1">
                    <span class="material-symbols-outlined text-secondary text-[20px]" aria-hidden="true">troubleshoot</span>
                    <?php esc_html_e('فاحص التداخلات الدوائية التفاعلي', 'baraa-pharmacy'); ?>
                </h3>
                <p class="font-body-md text-body-md text-on-surface-variant mb-space-md">
                    <?php esc_html_e('ابحث عن الأدوية الأخرى التي تتناولها بانتظام لفحص مدى أمانها الفوري مع هذا الدواء.', 'baraa-pharmacy'); ?>
                </p>
                <div class="flex gap-space-sm">
                    <div class="relative flex-1 flex items-center bg-surface-container-low rounded-lg px-space-md focus-within:ring-2 focus-within:ring-primary">
                        <span class="material-symbols-outlined text-outline text-[20px] ml-2" aria-hidden="true">search</span>
                        <input
                            type="text"
                            class="w-full bg-transparent py-2.5 font-body-md text-body-md text-on-surface placeholder:text-outline focus:outline-none"
                            placeholder="<?php esc_attr_e('ابحث عن دواء للتحقق من التفاعل...', 'baraa-pharmacy'); ?>"
                        />
                    </div>
                    <button type="button" class="px-space-md py-2.5 rounded-lg bg-primary text-on-primary font-label-md text-label-md font-bold hover:bg-primary-container transition-colors flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]" aria-hidden="true">verified</span>
                        <?php esc_html_e('فحص فوري', 'baraa-pharmacy'); ?>
                    </button>
                </div>
            </div>
            <div class="rounded-xl bg-surface-container-low border border-outline-variant/30 p-space-md">
                <p class="font-label-md text-label-md text-on-surface-variant flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px] text-primary" aria-hidden="true">info</span>
                    <?php esc_html_e('أبرز التفاعلات المسجلة في قاعدة البيانات الصيدلانية:', 'baraa-pharmacy'); ?>
                </p>
                <div class="mt-space-sm space-y-space-sm">
                    <?php
                    $interactions = array(
                        array(
                            'drug'    => __('حاصرات قنوات الكالسيوم (فيراباميل، ديلتيازيم):', 'baraa-pharmacy'),
                            'risk'    => 'high',
                            'desc'    => __('تداخل شديد — يُمنع الاستخدام المتزامن عبر الوريد لخطورة التباطؤ المفرط لعضلة القلب.', 'baraa-pharmacy'),
                        ),
                        array(
                            'drug'    => __('مضادات الالتهاب غير الستيرويدية NSAIDs (إيبوبروفين، ديكلوفيناك):', 'baraa-pharmacy'),
                            'risk'    => 'medium',
                            'desc'    => __('قد تقلل من فاعلية خفض ضغط الدم عند الاستخدام المتكرر. استشر الصيدلي عن بديل.', 'baraa-pharmacy'),
                        ),
                    );
                    $risk_styles = array(
                        'high'   => 'bg-error-container/30 border-error/30 text-error',
                        'medium' => 'bg-tertiary-fixed/30 border-tertiary/30 text-tertiary',
                    );
                    foreach ($interactions as $ix) :
                        $style = $risk_styles[$ix['risk']] ?? $risk_styles['medium'];
                    ?>
                        <div class="p-space-sm rounded-xl border <?php echo esc_attr($style); ?>">
                            <p class="font-label-md text-label-md font-bold"><?php echo esc_html($ix['drug']); ?></p>
                            <p class="font-body-sm text-body-sm mt-0.5"><?php echo esc_html($ix['desc']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Alternatives -->
        <div class="baraa-tab-panel space-y-space-md hidden" data-panel="alternatives">
            <div>
                <h3 class="font-title-lg text-title-lg text-on-surface font-bold flex items-center gap-2 mb-1">
                    <span class="material-symbols-outlined text-primary text-[20px]" aria-hidden="true">sync_alt</span>
                    <?php esc_html_e('البدائل والمثائل الدوائية المتكافئة حيوياً', 'baraa-pharmacy'); ?>
                </h3>
                <p class="font-body-md text-body-md text-on-surface-variant mb-space-md">
                    <?php esc_html_e('أدوية مرخصة ومعتمدة من هيئة الدواء المصرية تحتوي على نفس التركيبة الدوائية وبنفس الكفاءة العلاجية، وتُعتبر خيارات بديلة في حال البحث عن توفير اقتصادي أو في حال نقص المخزون.', 'baraa-pharmacy'); ?>
                </p>
            </div>
            <p class="font-body-sm text-body-sm text-on-surface-variant bg-surface-container-low rounded-xl p-space-md text-center">
                <?php esc_html_e('سيتم عرض البدائل الدوائية تلقائياً من قِبل الصيادلة عند تأكيد الطلب.', 'baraa-pharmacy'); ?>
            </p>
        </div>

        <!-- Reviews — renders the real WooCommerce review form + comments -->
        <div class="baraa-tab-panel space-y-space-md hidden" data-panel="reviews">
            <?php
            // Trigger WC reviews tab content directly
            comments_template();
            ?>
        </div>

    </div><!-- /.p-space-lg -->
</div><!-- /.tabs card -->

<!-- ═══════════════════════════════════════════════════════════
     UPSELLS / RELATED (WooCommerce standard hook)
     — Displayed below tabs as "Complementary care" section
═══════════════════════════════════════════════════════════ -->
<div class="mt-space-lg">
    <?php do_action('woocommerce_after_single_product_summary'); ?>
</div>

<!-- Tab switching JS (vanilla, no dependencies) -->
<script>
(function () {
    var btns   = document.querySelectorAll('.baraa-tab-btn');
    var panels = document.querySelectorAll('.baraa-tab-panel');

    function activateTab(key) {
        btns.forEach(function (b) {
            var active = b.getAttribute('data-tab') === key;
            b.classList.toggle('text-primary',              active);
            b.classList.toggle('border-primary',            active);
            b.classList.toggle('border-b-2',                active);
            b.classList.toggle('text-on-surface-variant',  !active);
            b.classList.toggle('border-transparent',       !active);
            b.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        panels.forEach(function (p) {
            p.classList.toggle('hidden', p.getAttribute('data-panel') !== key);
        });
    }

    btns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            activateTab(btn.getAttribute('data-tab'));
        });
    });

    // Activate first tab by default
    if (btns.length > 0) {
        activateTab(btns[0].getAttribute('data-tab'));
    }
})();
</script>

</article>

<?php do_action('woocommerce_after_single_product'); ?>
