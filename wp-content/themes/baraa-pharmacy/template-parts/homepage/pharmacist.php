<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<section class="w-full px-margin pb-space-xl">
    <div class="bg-gradient-to-l from-primary-container via-primary to-primary text-on-primary rounded-xl p-space-lg lg:p-space-xl shadow-lg relative overflow-hidden">

        <!-- Decorative blobs -->
        <div class="absolute -right-20 -top-20 w-80 h-80 rounded-full bg-white/10 blur-2xl pointer-events-none" aria-hidden="true"></div>
        <div class="absolute -left-20 -bottom-20 w-80 h-80 rounded-full bg-primary-fixed/15 blur-2xl pointer-events-none" aria-hidden="true"></div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter items-center relative z-10">

            <!-- Pharmacist illustration -->
            <div class="lg:col-span-4 flex justify-center lg:justify-start">
                <div class="relative">
                    <div class="w-56 h-56 md:w-64 md:h-64 rounded-2xl overflow-hidden shadow-xl bg-surface-container-lowest p-1.5 flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary text-[96px]" aria-hidden="true">person</span>
                    </div>
                    <!-- Trust badge -->
                    <div class="absolute -bottom-3 -right-3 bg-surface-container-lowest text-on-surface px-3 py-1.5 rounded-lg shadow-md flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[20px]" style="font-variation-settings:'FILL' 1" aria-hidden="true">verified</span>
                        <div class="flex flex-col text-right">
                            <span class="font-label-sm text-label-sm font-bold leading-tight"><?php esc_html_e('صيدلي استشاري', 'baraa-pharmacy'); ?></span>
                            <span class="text-[10px] text-outline leading-tight"><?php esc_html_e('استشاري صيدلة إكلينيكية', 'baraa-pharmacy'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="lg:col-span-8 space-y-space-md text-right">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary-fixed/20 text-primary-fixed font-label-md text-label-md">
                    <span class="material-symbols-outlined text-[16px]" aria-hidden="true">stethoscope</span>
                    <span><?php esc_html_e('العيادة الصيدلانية الرقمية المباشرة', 'baraa-pharmacy'); ?></span>
                </div>

                <h2 class="font-headline-lg text-headline-lg font-bold leading-tight">
                    <?php esc_html_e('هل تتناول أكثر من علاج معاً؟ نساعدك في فحص التفاعلات وتعديل الجرعات مجاناً', 'baraa-pharmacy'); ?>
                </h2>

                <p class="font-body-lg text-body-lg text-primary-fixed-dim max-w-2xl">
                    <?php esc_html_e('سلامتك الدوائية هي جوهر عملنا. يقدم صيادلتنا المتخصصون تدقيقاً مجانياً للروشتات المزمنة، وتوجيهات لمرضى السكري والضغط، وتأكيد تعارض الأدوية مع المكملات الغذائية قبل الاستخدام.', 'baraa-pharmacy'); ?>
                </p>

                <!-- Highlights -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-space-sm pt-space-xs">
                    <?php
                    $highlights = array(
                        array('icon' => 'chevron_forward', 'text' => __('تذكير تلقائي بتجديد دواء الأمراض المزمنة', 'baraa-pharmacy')),
                        array('icon' => 'warning',         'text' => __('كشف التداخلات الدوائية الضارة Drug Interactions', 'baraa-pharmacy')),
                        array('icon' => 'chat',            'text' => __('محادثة واتساب فورية مع صيدلي مناوب', 'baraa-pharmacy')),
                    );
                    foreach ($highlights as $h) :
                    ?>
                        <div class="p-space-sm rounded-lg bg-white/10 backdrop-blur-sm flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary-fixed text-[20px]" aria-hidden="true"><?php echo esc_html($h['icon']); ?></span>
                            <span class="font-label-md text-label-md"><?php echo esc_html($h['text']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- CTAs -->
                <div class="pt-space-sm">
                    <a
                        class="w-full sm:w-auto px-space-lg py-3 rounded-lg bg-primary-fixed text-on-primary-fixed font-label-md text-label-md font-bold hover:bg-white transition-colors flex items-center justify-center gap-2 shadow-sm"
                        href="https://wa.me/201556859030"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <span class="material-symbols-outlined text-[20px]" aria-hidden="true">chat</span>
                        <span><?php esc_html_e('تحدث مع الصيدلي عبر واتساب', 'baraa-pharmacy'); ?></span>
                    </a>
                </div>

            </div>
        </div>
    </div>
</section>
