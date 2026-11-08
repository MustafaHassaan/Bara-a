<?php
if (!defined('ABSPATH')) {
    exit;
}

$deals    = isset($args['deals'])    ? $args['deals']    : array();
$shop_url = isset($args['shop_url']) ? $args['shop_url'] : home_url('/');
?>

<section class="w-full px-margin py-space-xl">
    <div class="bg-surface-container-low rounded-xl p-space-lg shadow-sm">

        <!-- Section header + countdown -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-space-md pb-space-lg">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full bg-error text-on-error font-label-sm text-label-sm font-bold flex items-center gap-1 animate-pulse">
                        <span class="material-symbols-outlined text-[14px]" aria-hidden="true">local_fire_department</span>
                        <span><?php esc_html_e('عروض اليوم المحدودة', 'baraa-pharmacy'); ?></span>
                    </span>
                    <span class="font-label-md text-label-md text-tertiary font-bold"><?php esc_html_e('وفر حتى 35%', 'baraa-pharmacy'); ?></span>
                </div>
                <h2 class="font-headline-lg text-headline-lg text-on-surface font-bold"><?php esc_html_e('عروض اليوم والتوفير الصحي', 'baraa-pharmacy'); ?></h2>
                <p class="font-body-md text-body-md text-on-surface-variant"><?php esc_html_e('أسعار مدعومة لأجهزة الفحص الدوري وأشهر منتجات العناية والفيتامينات العالمية.', 'baraa-pharmacy'); ?></p>
            </div>

            <!-- Countdown timer -->
            <div class="flex items-center gap-space-sm bg-surface-container-lowest px-space-md py-2.5 rounded-lg shadow-sm self-start md:self-auto" aria-live="polite" aria-label="<?php esc_attr_e('الوقت المتبقي على انتهاء العرض', 'baraa-pharmacy'); ?>">
                <span class="material-symbols-outlined text-tertiary text-[20px]" aria-hidden="true">timer</span>
                <span class="font-label-md text-label-md text-on-surface font-bold ml-1"><?php esc_html_e('ينتهي العرض خلال:', 'baraa-pharmacy'); ?></span>
                <div class="flex items-center gap-1 font-bold font-title-md text-title-md" dir="ltr">
                    <span class="px-2 py-1 bg-surface-container rounded text-primary" id="flashHours">08</span>
                    <span class="text-outline">:</span>
                    <span class="px-2 py-1 bg-surface-container rounded text-primary" id="flashMins">42</span>
                    <span class="text-outline">:</span>
                    <span class="px-2 py-1 bg-surface-container rounded text-tertiary" id="flashSecs">19</span>
                </div>
            </div>
        </div>

        <!-- Products grid -->
        <?php if ($deals) : ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-gutter">
                <?php foreach ($deals as $product) : ?>
                    <?php get_template_part('template-parts/homepage/product-card', null, array('product' => $product)); ?>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="text-center py-space-xl">
                <p class="font-body-md text-body-md text-on-surface-variant mb-space-md"><?php esc_html_e('لا توجد عروض خاصة حالياً. تصفح منتجاتنا المتوفرة.', 'baraa-pharmacy'); ?></p>
                <a class="inline-flex items-center gap-2 px-space-lg py-3 rounded-lg bg-primary text-on-primary font-label-md text-label-md font-bold hover:bg-primary-container transition-colors" href="<?php echo esc_url($shop_url); ?>">
                    <?php esc_html_e('تصفح المتجر', 'baraa-pharmacy'); ?>
                </a>
            </div>
        <?php endif; ?>

    </div>
</section>

<script>
(function initFlashCountdown() {
    var totalSeconds = (8 * 3600) + (42 * 60) + 19;
    var hEl = document.getElementById('flashHours');
    var mEl = document.getElementById('flashMins');
    var sEl = document.getElementById('flashSecs');
    if (!hEl || !mEl || !sEl) return;
    setInterval(function () {
        if (totalSeconds <= 0) { totalSeconds = 24 * 3600; }
        totalSeconds--;
        var hrs  = Math.floor(totalSeconds / 3600);
        var mins = Math.floor((totalSeconds % 3600) / 60);
        var secs = totalSeconds % 60;
        hEl.textContent = String(hrs).padStart(2, '0');
        mEl.textContent = String(mins).padStart(2, '0');
        sEl.textContent = String(secs).padStart(2, '0');
    }, 1000);
})();
</script>
