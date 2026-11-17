<?php
if (!defined('ABSPATH')) {
    exit;
}

$services = array(
    array(
        'icon'  => 'electric_bolt',
        'color' => 'bg-primary-fixed text-on-primary-fixed',
        'title' => __('توصيل إسعافي 30 دقيقة', 'baraa-pharmacy'),
        'desc'  => __('أسطول مجهز بحقائب مبردة لتغطية الطوارئ الحادة على مدار اليوم.', 'baraa-pharmacy'),
    ),
    array(
        'icon'  => 'medical_services',
        'color' => 'bg-secondary-fixed text-on-secondary-fixed',
        'title' => __('صيادلة متاحون 24/7', 'baraa-pharmacy'),
        'desc'  => __('استشارات مجانية للجرعات والتداخلات الدوائية مع استشاري دواء.', 'baraa-pharmacy'),
    ),
    array(
        'icon'  => 'verified_user',
        'color' => 'bg-primary-fixed-dim text-on-primary-fixed',
        'title' => __('دواء أصلي 100% موثق', 'baraa-pharmacy'),
        'desc'  => __('مرخص رسميًا وخاضع لتتبع الباركود بهيئة الدواء المصرية.', 'baraa-pharmacy'),
    ),
);
?>

<section class="w-full px-margin py-space-lg" aria-label="<?php esc_attr_e('خدمات صيدليات براءة', 'baraa-pharmacy'); ?>">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-gutter">
        <?php foreach ($services as $service) : ?>
            <div class="bg-surface-container-lowest p-space-md rounded-xl shadow-sm hover:shadow-md transition-shadow flex items-start gap-space-md">
                <div class="w-12 h-12 rounded-lg <?php echo esc_attr($service['color']); ?> flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-[26px]" aria-hidden="true"><?php echo esc_html($service['icon']); ?></span>
                </div>
                <div class="flex flex-col">
                    <h3 class="font-title-md text-title-md text-on-surface font-bold"><?php echo esc_html($service['title']); ?></h3>
                    <p class="font-body-sm text-body-sm text-on-surface-variant mt-0.5"><?php echo esc_html($service['desc']); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
