<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<footer class="w-full bg-surface-container-low text-on-surface pt-space-xl pb-space-lg">
    <div class="w-full px-margin">

        <!-- ── Footer grid ──────────────────────────────────────── -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-space-lg pb-space-xl border-b border-outline-variant/30">

            <!-- Brand + about -->
            <div class="space-y-space-md">

                <!-- Logo -->
                <a class="flex items-center gap-space-sm" href="<?php echo esc_url(home_url('/')); ?>">
                    <?php
                    $custom_logo_id = get_theme_mod('custom_logo');
                    $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
                    if (has_custom_logo()) :
                    ?>
                        <div class="w-14 h-14 flex items-center justify-center flex-shrink-0">
                            <img src="<?php echo esc_url($logo[0]); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" class="w-full h-full object-contain">
                        </div>
                    <?php else : ?>
                        <div class="w-14 h-14 rounded-xl bg-primary flex items-center justify-center text-on-primary flex-shrink-0">
                            <svg viewBox="0 0 40 40" width="32" height="32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <!-- Pharmacy cross -->
                                <rect x="16" y="8"  width="8" height="24" rx="2" fill="currentColor" opacity="0.95"/>
                                <rect x="8"  y="16" width="24" height="8"  rx="2" fill="currentColor" opacity="0.95"/>
                            </svg>
                        </div>
                    <?php endif; ?>
                    <div class="flex flex-col">
                        <span class="font-headline-md text-headline-md text-primary font-bold leading-tight"><?php esc_html_e('صيدلية براءة', 'baraa-pharmacy'); ?></span>
                        <span class="font-label-sm text-label-sm text-outline tracking-wider">Baraa Pharmacy</span>
                    </div>
                </a>

                <p class="font-body-md text-body-md text-on-surface-variant max-w-sm">
                    <?php esc_html_e('صيدلية متخصصة بالإسكندرية. نوفر الأدوية الأصلية المعتمدة ونقدم استشارات صيدلانية موثوقة.', 'baraa-pharmacy'); ?>
                </p>
            </div>

            <!-- Single branch -->
            <div class="space-y-space-sm">
                <h4 class="font-title-lg text-title-lg text-on-surface font-bold">
                    <?php esc_html_e('الفرع', 'baraa-pharmacy'); ?>
                </h4>
                <div class="space-y-space-sm font-body-sm text-body-sm text-on-surface-variant">
                    <!-- Address -->
                    <div class="flex items-start gap-2">
                        <span class="material-symbols-outlined text-primary text-[18px] mt-0.5 shrink-0">pin_drop</span>
                        <div>
                            <p class="font-label-md text-label-md text-on-surface font-semibold mb-0.5">
                                <?php esc_html_e('العجمي — الإسكندرية', 'baraa-pharmacy'); ?>
                            </p>
                            <p class="leading-relaxed">
                                <?php esc_html_e('الدرايسة — أمام مدرسة العجمي النموذجية', 'baraa-pharmacy'); ?>
                                <br>
                                <?php esc_html_e('أبو يوسف — أمام مسجد الصابرين', 'baraa-pharmacy'); ?>
                            </p>
                        </div>
                    </div>
                    <!-- Google Maps link -->
                    <a
                        class="inline-flex items-center gap-1.5 text-primary font-label-md text-label-md hover:underline"
                        href="https://www.google.com/maps?q=31.1021,29.77585"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <span class="material-symbols-outlined text-[16px]">map</span>
                        <?php esc_html_e('عرض على خريطة جوجل', 'baraa-pharmacy'); ?>
                    </a>
                </div>
            </div>

            <!-- Emergency contact -->
            <div class="space-y-space-sm">
                <h4 class="font-title-lg text-title-lg text-on-surface font-bold"><?php esc_html_e('تواصل معنا', 'baraa-pharmacy'); ?></h4>
                <!-- WhatsApp -->
                <a
                    class="flex items-center gap-2 px-space-sm py-2 rounded-lg bg-surface-container-lowest border border-outline-variant/30 hover:border-primary transition-colors font-label-md text-label-md text-on-surface"
                    href="https://wa.me/201556859030"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <span class="material-symbols-outlined text-[18px] text-primary">chat</span>
                    <?php esc_html_e('واتساب', 'baraa-pharmacy'); ?>
                    <span class="text-outline font-body-sm text-body-sm mr-auto">01556859030</span>
                </a>
                <div class="pt-space-xs space-y-1">
                    <span class="font-label-sm text-label-sm text-outline block"><?php esc_html_e('البريد الإلكتروني:', 'baraa-pharmacy'); ?></span>
                    <a href="mailto:awatefgmy@gmail.com" class="font-body-sm text-body-sm text-on-surface font-medium hover:text-primary transition-colors">awatefgmy@gmail.com</a>
                </div>
            </div>

        </div>

        <!-- ── Bottom bar ──────────────────────────────────────── -->
        <div class="pt-space-md flex flex-col md:flex-row items-center justify-between gap-space-md">

            <!-- Payment methods -->
            <div class="flex items-center gap-space-sm flex-wrap text-on-surface-variant font-label-sm text-label-sm">
                <span><?php esc_html_e('طرق الدفع:', 'baraa-pharmacy'); ?></span>
                <?php
                $payment_methods = array('ميزة Meeza', 'Visa', 'Mastercard', __('كاش عند الاستلام', 'baraa-pharmacy'));
                foreach ($payment_methods as $method) :
                ?>
                    <span class="px-2 py-1 bg-surface-container-lowest rounded border border-outline-variant/30 font-semibold text-on-surface">
                        <?php echo esc_html($method); ?>
                    </span>
                <?php endforeach; ?>
            </div>

            <!-- Copyright -->
            <div class="flex items-center gap-space-md font-label-sm text-label-sm text-outline flex-wrap justify-center">
                <?php if (get_privacy_policy_url()) : ?>
                    <a class="hover:text-primary transition-colors" href="<?php echo esc_url(get_privacy_policy_url()); ?>"><?php esc_html_e('سياسة الخصوصية', 'baraa-pharmacy'); ?></a>
                <?php endif; ?>
                <?php $terms_id = get_option('woocommerce_terms_page_id'); if ($terms_id) : ?>
                    <a class="hover:text-primary transition-colors" href="<?php echo esc_url(get_permalink($terms_id)); ?>"><?php esc_html_e('الشروط والأحكام', 'baraa-pharmacy'); ?></a>
                <?php endif; ?>
                <span>© <?php echo esc_html(date('Y')); ?> <?php esc_html_e('صيدلية براءة — جميع الحقوق محفوظة', 'baraa-pharmacy'); ?></span>
            </div>

        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
