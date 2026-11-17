<?php
/**
 * Template Name: من نحن
 * About us page template
 */
if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<main class="w-full bg-surface min-h-[calc(100vh-280px)]" id="main">
    <div class="flex flex-col w-full">

        <!-- Hero section -->
        <section class="w-full px-margin py-space-xl bg-gradient-to-r from-[#16A085] to-[#117A65] text-white">
            <div class="max-w-4xl mx-auto text-center">
                <h1 class="font-headline-2xl text-headline-2xl font-bold mb-4"><?php esc_html_e('من نحن', 'baraa-pharmacy'); ?></h1>
                <p class="font-body-lg text-body-lg text-white/90 max-w-2xl mx-auto">
                    <?php esc_html_e('صيدليات براءة - شريكك الموثوق في الرعاية الصحية', 'baraa-pharmacy'); ?>
                </p>
            </div>
        </section>

        <!-- About content -->
        <section class="w-full px-margin py-space-xl">
            <div class="max-w-4xl mx-auto space-y-space-xl">
                
                <!-- Our story -->
                <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm">
                    <h2 class="font-title-lg text-title-lg font-bold text-on-surface mb-space-md flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[28px]" aria-hidden="true">history_edu</span>
                        <?php esc_html_e('قصتنا', 'baraa-pharmacy'); ?>
                    </h2>
                    <div class="font-body-md text-body-md text-on-surface-variant space-y-4 leading-relaxed">
                        <p>
                            <?php esc_html_e('صيدليات براءة هي سلسلة صيدليات متخصصة في الإسكندرية، نسعى لتقديم أفضل خدمات الرعاية الصحية لعملائنا. بدأنا رحلتنا بهدف تقديم أدوية أصلية معتمدة وخدمات صيدلانية موثوقة للجميع.', 'baraa-pharmacy'); ?>
                        </p>
                        <p>
                            <?php esc_html_e('نؤمن بأن الصحة هي أغلى ما يملكه الإنسان، ولذلك نعمل بجد على توفير جميع الأدوية والمستحضرات الطبية اللازمة بأعلى معايير الجودة والسلامة.', 'baraa-pharmacy'); ?>
                        </p>
                    </div>
                </div>

                <!-- Our mission -->
                <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm">
                    <h2 class="font-title-lg text-title-lg font-bold text-on-surface mb-space-md flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[28px]" aria-hidden="true">flag</span>
                        <?php esc_html_e('رؤيتنا ورسالتنا', 'baraa-pharmacy'); ?>
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-space-md">
                        <div class="p-space-md bg-surface-container rounded-lg">
                            <h3 class="font-title-md text-title-md font-bold text-primary mb-2"><?php esc_html_e('رؤيتنا', 'baraa-pharmacy'); ?></h3>
                            <p class="font-body-sm text-body-sm text-on-surface-variant">
                                <?php esc_html_e('أن نكون الخيار الأول للعائلات في الإسكندرية عند البحث عن أدوية أصلية وخدمات صيدلانية موثوقة.', 'baraa-pharmacy'); ?>
                            </p>
                        </div>
                        <div class="p-space-md bg-surface-container rounded-lg">
                            <h3 class="font-title-md text-title-md font-bold text-primary mb-2"><?php esc_html_e('رسالتنا', 'baraa-pharmacy'); ?></h3>
                            <p class="font-body-sm text-body-sm text-on-surface-variant">
                                <?php esc_html_e('توفير أدوية أصلية معتمدة من هيئة الدواء المصرية، مع تقديم استشارات صيدلانية متخصصة وخدمة توصيل سريعة وموثوقة.', 'baraa-pharmacy'); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Our values -->
                <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm">
                    <h2 class="font-title-lg text-title-lg font-bold text-on-surface mb-space-md flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[28px]" aria-hidden="true">verified</span>
                        <?php esc_html_e('قيمنا', 'baraa-pharmacy'); ?>
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-space-md">
                        <div class="text-center p-space-md">
                            <div class="w-16 h-16 mx-auto mb-3 rounded-full bg-primary/10 flex items-center justify-center">
                                <span class="material-symbols-outlined text-primary text-[32px]" aria-hidden="true">workspace_premium</span>
                            </div>
                            <h3 class="font-title-md text-title-md font-bold text-on-surface mb-2"><?php esc_html_e('الجودة', 'baraa-pharmacy'); ?></h3>
                            <p class="font-body-sm text-body-sm text-on-surface-variant">
                                <?php esc_html_e('نوفر فقط أدوية أصلية معتمدة من الشركات المصنعة والوكلاء المعتمدين.', 'baraa-pharmacy'); ?>
                            </p>
                        </div>
                        <div class="text-center p-space-md">
                            <div class="w-16 h-16 mx-auto mb-3 rounded-full bg-primary/10 flex items-center justify-center">
                                <span class="material-symbols-outlined text-primary text-[32px]" aria-hidden="true">support_agent</span>
                            </div>
                            <h3 class="font-title-md text-title-md font-bold text-on-surface mb-2"><?php esc_html_e('الثقة', 'baraa-pharmacy'); ?></h3>
                            <p class="font-body-sm text-body-sm text-on-surface-variant">
                                <?php esc_html_e('نقدم استشارات صيدلانية موثوقة مع صيدليين مؤهلين ومتخصصين.', 'baraa-pharmacy'); ?>
                            </p>
                        </div>
                        <div class="text-center p-space-md">
                            <div class="w-16 h-16 mx-auto mb-3 rounded-full bg-primary/10 flex items-center justify-center">
                                <span class="material-symbols-outlined text-primary text-[32px]" aria-hidden="true">local_shipping</span>
                            </div>
                            <h3 class="font-title-md text-title-md font-bold text-on-surface mb-2"><?php esc_html_e('السرعة', 'baraa-pharmacy'); ?></h3>
                            <p class="font-body-sm text-body-sm text-on-surface-variant">
                                <?php esc_html_e('خدمة توصيل سريعة مع سلسلة تبريد دوائية معتمدة للأدوية الحساسة.', 'baraa-pharmacy'); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Contact CTA -->
                <div class="bg-gradient-to-r from-[#16A085] to-[#117A65] rounded-xl p-space-lg text-white text-center">
                    <h2 class="font-title-lg text-title-lg font-bold mb-2"><?php esc_html_e('تواصل معنا', 'baraa-pharmacy'); ?></h2>
                    <p class="font-body-md text-body-md text-white/90 mb-space-md">
                        <?php esc_html_e('هل لديك أي استفسار؟ فريقنا جاهز لمساعدتك على مدار الساعة.', 'baraa-pharmacy'); ?>
                    </p>
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                        <a
                            href="https://wa.me/201556859030"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1.5 px-space-md py-2.5 rounded-lg bg-white text-[#16A085] font-label-md text-label-md font-bold transition-colors hover:bg-white/90"
                        >
                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">chat</span>
                            <span><?php esc_html_e('واتساب', 'baraa-pharmacy'); ?></span>
                        </a>
                        <a
                            href="tel:16840"
                            class="inline-flex items-center gap-1.5 px-space-md py-2.5 rounded-lg bg-white/20 text-white font-label-md text-label-md font-bold transition-colors hover:bg-white/30"
                        >
                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">phone</span>
                            <span><?php esc_html_e('هوتلاين', 'baraa-pharmacy'); ?></span>
                        </a>
                    </div>
                </div>

            </div>
        </section>

    </div>
</main>

<?php get_footer(); ?>
