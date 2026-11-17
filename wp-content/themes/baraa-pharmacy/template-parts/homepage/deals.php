<?php
if (!defined('ABSPATH')) {
    exit;
}

$shop_url = isset($args['shop_url']) ? $args['shop_url'] : home_url('/');

/*
 * البلاطة دي بقت بتاعة العروض والمناسبات بس: من غير حملة شغالة ما تظهرش خالص
 * (لا عدّاد ولا نِسَب وهمية)، ولوحة التحكم هي اللي بتقرر إمتى تظهر.
 */
$offer = function_exists('baraa_pharmacy_active_offer') ? baraa_pharmacy_active_offer() : null;

if (!$offer) {
    return;
}

$offer_products = baraa_pharmacy_offer_products($offer, 4);
$offer_ends     = baraa_pharmacy_offer_datetime($offer['ends']);
$offer_seconds  = '' !== $offer_ends ? max(0, strtotime($offer_ends) - strtotime(current_time('mysql'))) : 0;
$offer_image    = (int) $offer['image_id'] > 0 ? (string) wp_get_attachment_image_url((int) $offer['image_id'], 'large') : '';

$offer_title = '' !== $offer['hero_title']
    ? $offer['hero_title']
    : baraa_pharmacy_text('عروض اليوم والتوفير الصحي', "Today's deals and healthy savings");

$offer_text = '' !== $offer['hero_text']
    ? $offer['hero_text']
    : baraa_pharmacy_text(
        'أسعار مدعومة لأجهزة الفحص الدوري وأشهر منتجات العناية والفيتامينات العالمية.',
        'Supported prices on screening devices and the most popular care and vitamin products.'
    );

$offer_label = (float) $offer['amount'] > 0
    ? baraa_pharmacy_text('وفر حتى ', 'Save up to ') . baraa_pharmacy_offer_amount_label($offer)
    : '';
?>

<section class="w-full px-margin py-space-xl">
    <div class="bg-surface-container-low rounded-xl p-space-lg shadow-sm">

        <!-- Section header + countdown -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-space-md pb-space-lg">
            <div class="space-y-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="px-2.5 py-0.5 rounded-full bg-error text-on-error font-label-sm text-label-sm font-bold flex items-center gap-1 animate-pulse">
                        <span class="material-symbols-outlined text-[14px]" aria-hidden="true">local_fire_department</span>
                        <span><?php echo esc_html($offer['name']); ?></span>
                    </span>
                    <?php if ('' !== $offer_label) : ?>
                        <span class="font-label-md text-label-md text-tertiary font-bold"><?php echo esc_html($offer_label); ?></span>
                    <?php endif; ?>
                </div>
                <h2 class="font-headline-lg text-headline-lg text-on-surface font-bold"><?php echo esc_html($offer_title); ?></h2>
                <p class="font-body-md text-body-md text-on-surface-variant"><?php echo esc_html($offer_text); ?></p>
            </div>

            <!-- Countdown timer -->
            <div class="flex items-center gap-space-sm bg-surface-container-lowest px-space-md py-2.5 rounded-lg shadow-sm self-start md:self-auto" aria-live="polite" aria-label="<?php esc_attr_e('الوقت المتبقي على انتهاء العرض', 'baraa-pharmacy'); ?>">
                <span class="material-symbols-outlined text-tertiary text-[20px]" aria-hidden="true">timer</span>
                <?php if ($offer_seconds > 0) : ?>
                    <span
                        class="font-bold font-title-md text-title-md text-primary tabular-nums"
                        data-baraa-countdown
                        data-seconds="<?php echo esc_attr($offer_seconds); ?>"
                        data-done="<?php echo esc_attr(baraa_pharmacy_text('انتهى العرض', 'Offer ended')); ?>"
                    ></span>
                <?php else : ?>
                    <span class="font-label-md text-label-md text-on-surface font-bold"><?php echo esc_html(baraa_pharmacy_text('عرض مستمر', 'Ongoing offer')); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Campaign banner (اختياري من صفحة العروض) -->
        <?php if ('' !== $offer_image) : ?>
            <div class="mb-space-lg overflow-hidden rounded-xl shadow-sm">
                <img
                    src="<?php echo esc_url($offer_image); ?>"
                    alt="<?php echo esc_attr($offer['name']); ?>"
                    class="w-full h-auto max-h-[240px] object-cover"
                    loading="lazy"
                    decoding="async"
                >
            </div>
        <?php endif; ?>

        <!-- Products grid -->
        <?php if ($offer_products) : ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-gutter">
                <?php foreach ($offer_products as $product) : ?>
                    <?php get_template_part('template-parts/homepage/product-card', null, array('product' => $product)); ?>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="text-center py-space-xl">
                <p class="font-body-md text-body-md text-on-surface-variant mb-space-md"><?php esc_html_e('لا توجد منتجات مشمولة في العرض حالياً.', 'baraa-pharmacy'); ?></p>
                <a class="inline-flex items-center gap-2 px-space-lg py-3 rounded-lg bg-primary text-on-primary font-label-md text-label-md font-bold hover:bg-primary-container transition-colors" href="<?php echo esc_url($shop_url); ?>">
                    <?php esc_html_e('تصفح المتجر', 'baraa-pharmacy'); ?>
                </a>
            </div>
        <?php endif; ?>

    </div>
</section>
