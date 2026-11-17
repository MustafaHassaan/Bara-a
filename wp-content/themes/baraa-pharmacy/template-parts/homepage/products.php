<?php
if (!defined('ABSPATH')) {
    exit;
}

$products = isset($args['products']) ? $args['products'] : array();
$shop_url = isset($args['shop_url']) ? $args['shop_url'] : home_url('/');

$filter_tabs = array(
    'all'     => __('الكل', 'baraa-pharmacy'),
    'otc'     => __('أدوية مسكنة وOTC', 'baraa-pharmacy'),
    'rx'      => __('تتطلب روشتة (Rx)', 'baraa-pharmacy'),
    'skincare' => __('عناية بالبشرة علاجية', 'baraa-pharmacy'),
);
?>

<section class="w-full px-margin py-space-xl">
    <div class="space-y-space-lg">

        <!-- Section header + filter tabs -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-space-sm border-b border-outline-variant/30 pb-space-md">
            <div>
                <div class="flex items-center gap-space-sm">
                    <span class="font-label-md text-label-md text-primary font-bold"><?php esc_html_e('صيدلية الشعب الآمنة', 'baraa-pharmacy'); ?></span>
                    <span class="px-2 py-0.5 rounded bg-surface-container text-on-surface-variant text-[11px] font-label-sm"><?php esc_html_e('الأسعار طبقاً للتسعيرة الجبرية لوزارة الصحة', 'baraa-pharmacy'); ?></span>
                </div>
                <h2 class="font-headline-lg text-headline-lg text-on-surface font-bold mt-1"><?php esc_html_e('الأدوية والمستلزمات الأكثر طلباً', 'baraa-pharmacy'); ?></h2>
            </div>

            <div class="flex items-center gap-space-xs overflow-x-auto pb-1" role="group" aria-label="<?php esc_attr_e('تصفية المنتجات', 'baraa-pharmacy'); ?>">
                <?php foreach ($filter_tabs as $key => $label) : ?>
                    <button
                        class="px-space-md py-1.5 rounded-lg font-label-md text-label-md shrink-0 transition-colors <?php echo $key === 'all' ? 'bg-primary text-on-primary font-semibold' : 'bg-surface-container text-on-surface-variant hover:text-on-surface'; ?>"
                        type="button"
                        data-filter="<?php echo esc_attr($key); ?>"
                    ><?php echo esc_html($label); ?></button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Products grid -->
        <?php if ($products) : ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-gutter">
                <?php foreach ($products as $product) : ?>
                    <?php get_template_part('template-parts/homepage/product-card', null, array('product' => $product)); ?>
                <?php endforeach; ?>
            </div>
            <div class="text-center pt-space-md">
                <a
                    class="inline-flex items-center gap-2 px-space-xl py-3 rounded-lg border-2 border-primary text-primary font-label-md text-label-md font-bold hover:bg-primary hover:text-on-primary transition-colors"
                    href="<?php echo esc_url($shop_url); ?>"
                >
                    <?php esc_html_e('عرض جميع المنتجات', 'baraa-pharmacy'); ?>
                    <span class="material-symbols-outlined text-[18px]" aria-hidden="true">chevron_left</span>
                </a>
            </div>
        <?php else : ?>
            <p class="font-body-md text-body-md text-on-surface-variant bg-surface-container-low rounded-xl p-space-lg text-center">
                <?php esc_html_e('ستظهر المنتجات هنا بعد إضافتها للمتجر.', 'baraa-pharmacy'); ?>
            </p>
        <?php endif; ?>

    </div>
</section>
