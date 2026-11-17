<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<footer class="w-full bg-[#16A085] text-white pt-space-xl pb-space-lg border-t-4 border-[#117A65]">
    <div class="w-full px-margin">

        <!-- ── Footer grid ──────────────────────────────────────── -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-space-lg pb-space-xl border-b border-white/20">

            <!-- Brand + about -->
            <div class="space-y-space-md">

                <!-- Logo -->
                <a class="flex items-center gap-space-sm" href="<?php echo esc_url(home_url('/')); ?>">
                    <?php
                    $custom_logo_id = get_theme_mod('custom_logo');
                    $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
                    if (has_custom_logo()) :
                    ?>
                        <div class="w-14 h-14 rounded-xl overflow-hidden bg-white/95 p-1 ring-1 ring-white/20 flex items-center justify-center flex-shrink-0 shadow-sm">
                            <img src="<?php echo esc_url($logo[0]); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" class="w-full h-full object-contain">
                        </div>
                    <?php else : ?>
                        <div class="w-14 h-14 rounded-xl bg-white text-[#16A085] flex items-center justify-center flex-shrink-0 shadow-sm">
                            <svg viewBox="0 0 40 40" width="32" height="32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <!-- Pharmacy cross -->
                                <rect x="16" y="8"  width="8" height="24" rx="2" fill="currentColor" opacity="0.95"/>
                                <rect x="8"  y="16" width="24" height="8"  rx="2" fill="currentColor" opacity="0.95"/>
                            </svg>
                        </div>
                    <?php endif; ?>
                    <div class="flex flex-col">
                        <span class="font-headline-md text-headline-md text-white font-bold leading-tight"><?php esc_html_e('صيدليات براءة', 'baraa-pharmacy'); ?></span>
                        <span class="font-label-sm text-label-sm text-white/80 tracking-wider"><?php esc_html_e('Baraa Pharmacy', 'baraa-pharmacy'); ?></span>
                    </div>
                </a>

                <p class="font-body-md text-body-md text-white/90 max-w-sm leading-relaxed">
                    <?php esc_html_e('صيدليات متخصصة بالإسكندرية. نوفر الأدوية الأصلية المعتمدة ونقدم استشارات صيدلانية موثوقة.', 'baraa-pharmacy'); ?>
                </p>
            </div>

            <!-- Branches (dynamic, managed in WooCommerce → الفروع والتوصيل) -->
            <div class="space-y-space-sm">
                <h4 class="font-title-lg text-title-lg text-white font-bold">
                    <?php esc_html_e('الفروع', 'baraa-pharmacy'); ?>
                </h4>
                <div class="space-y-space-md font-body-sm text-body-sm text-white/90">
                    <?php foreach (baraa_pharmacy_enabled_branches() as $branch) : ?>
                        <?php
                        $branch_name  = isset($branch['name']) ? trim((string) $branch['name']) : '';
                        $branch_addr  = isset($branch['address']) ? trim((string) $branch['address']) : '';
                        $branch_addr2 = isset($branch['address2']) ? trim((string) $branch['address2']) : '';
                        $branch_map   = isset($branch['map']) ? trim((string) $branch['map']) : '';

                        if ('' === $branch_name && '' === $branch_addr) {
                            continue;
                        }
                        ?>
                        <div class="space-y-space-sm">
                            <!-- Address -->
                            <div class="flex items-start gap-2">
                                <span class="material-symbols-outlined text-white text-[18px] mt-0.5 shrink-0">pin_drop</span>
                                <div>
                                    <?php if ('' !== $branch_name) : ?>
                                        <p class="font-label-md text-label-md text-white font-bold mb-0.5">
                                            <?php echo esc_html($branch_name); ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if ('' !== $branch_addr || '' !== $branch_addr2) : ?>
                                        <p class="leading-relaxed text-white/85">
                                            <?php echo esc_html($branch_addr); ?>
                                            <?php if ('' !== $branch_addr2) : ?>
                                                <br>
                                                <?php echo esc_html($branch_addr2); ?>
                                            <?php endif; ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Google Maps link -->
                            <?php if ('' !== $branch_map) : ?>
                                <a
                                    class="inline-flex items-center gap-1.5 text-white underline hover:text-white/80 font-label-md text-label-md"
                                    href="<?php echo esc_url($branch_map); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <span class="material-symbols-outlined text-[16px]">map</span>
                                    <?php esc_html_e('عرض على خريطة جوجل', 'baraa-pharmacy'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Emergency contact -->
            <div class="space-y-space-sm">
                <h4 class="font-title-lg text-title-lg text-white font-bold"><?php esc_html_e('تواصل معنا', 'baraa-pharmacy'); ?></h4>
                <!-- WhatsApp -->
                <a
                    class="flex items-center gap-2 px-space-sm py-2 rounded-lg bg-white/10 border border-white/25 hover:bg-white/20 transition-colors font-label-md text-label-md text-white shadow-sm"
                    href="https://wa.me/201556859030"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <span class="material-symbols-outlined text-[18px] text-white">chat</span>
                    <?php esc_html_e('واتساب', 'baraa-pharmacy'); ?>
                    <span class="text-white/85 font-body-sm text-body-sm mr-auto font-semibold">01556859030</span>
                </a>
                <div class="pt-space-xs space-y-1">
                    <span class="font-label-sm text-label-sm text-white/80 block"><?php esc_html_e('البريد الإلكتروني:', 'baraa-pharmacy'); ?></span>
                    <a href="mailto:awatefgmy@gmail.com" class="font-body-sm text-body-sm text-white font-medium hover:underline">awatefgmy@gmail.com</a>
                </div>
            </div>

        </div>

        <!-- ── Bottom bar ──────────────────────────────────────── -->
        <div class="pt-space-md flex flex-col md:flex-row items-center justify-between gap-space-md text-white">

            <!-- Payment methods -->
            <?php if (function_exists('WC') && WC()->payment_gateways) : ?>
                <?php
                $payment_gateways = WC()->payment_gateways->payment_gateways;
                $enabled_gateways = array_filter($payment_gateways, function($gateway) {
                    return isset($gateway->enabled) && $gateway->enabled === 'yes';
                });
                ?>
                <?php if (!empty($enabled_gateways)) : ?>
                    <div class="flex items-center gap-space-sm flex-wrap text-white font-label-sm text-label-sm">
                        <span class="text-white/90 font-medium"><?php esc_html_e('طرق الدفع:', 'baraa-pharmacy'); ?></span>
                        <?php foreach ($enabled_gateways as $gateway) : ?>
                            <span class="px-2 py-1 bg-white/10 rounded border border-white/20 font-semibold text-white">
                                <?php echo esc_html($gateway->get_title()); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Copyright -->
            <div class="flex items-center gap-space-md font-label-sm text-label-sm text-white/80 flex-wrap justify-center">
                <?php if (get_privacy_policy_url()) : ?>
                    <a class="hover:text-white underline transition-colors" href="<?php echo esc_url(get_privacy_policy_url()); ?>"><?php esc_html_e('سياسة الخصوصية', 'baraa-pharmacy'); ?></a>
                <?php endif; ?>
                <?php $terms_id = get_option('woocommerce_terms_page_id'); if ($terms_id) : ?>
                    <a class="hover:text-white underline transition-colors" href="<?php echo esc_url(get_permalink($terms_id)); ?>"><?php esc_html_e('الشروط والأحكام', 'baraa-pharmacy'); ?></a>
                <?php endif; ?>
                <span>© <?php echo esc_html(date('Y')); ?> <?php esc_html_e('صيدليات براءة — جميع الحقوق محفوظة', 'baraa-pharmacy'); ?></span>
            </div>

        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
