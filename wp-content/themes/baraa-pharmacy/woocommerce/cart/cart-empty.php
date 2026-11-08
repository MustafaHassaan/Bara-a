<?php
/**
 * Empty cart page — Baraa Pharmacy MD3 Design
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined('ABSPATH') || exit;
?>

<section class="w-full min-h-[60vh] bg-surface px-margin py-space-xl flex items-center justify-center">
    <div class="max-w-lg w-full bg-surface-container-lowest rounded-xl shadow-sm p-space-xl flex flex-col items-center text-center gap-space-lg">

        <!-- Icon -->
        <div class="w-24 h-24 rounded-full bg-surface-container flex items-center justify-center">
            <span class="material-symbols-outlined text-outline text-[48px]" aria-hidden="true">shopping_bag</span>
        </div>

        <?php do_action('woocommerce_cart_is_empty'); ?>

        <div class="space-y-space-sm">
            <h1 class="font-headline-lg text-headline-lg text-on-surface font-bold">
                <?php esc_html_e('سلتك فارغة', 'baraa-pharmacy'); ?>
            </h1>
            <p class="font-body-md text-body-md text-on-surface-variant">
                <?php esc_html_e('تصفح المتجر وأضف المنتجات التي تحتاجها إلى سلتك.', 'baraa-pharmacy'); ?>
            </p>
        </div>

        <?php if (wc_get_page_id('shop') > 0) : ?>
            <a
                class="w-full sm:w-auto px-space-xl py-3 rounded-lg bg-primary text-on-primary font-label-md text-label-md font-bold hover:bg-primary-container transition-colors flex items-center justify-center gap-2"
                href="<?php echo esc_url(apply_filters('woocommerce_return_to_shop_redirect', wc_get_page_permalink('shop'))); ?>"
            >
                <span class="material-symbols-outlined text-[20px]" aria-hidden="true">store</span>
                <?php echo esc_html(apply_filters('woocommerce_return_to_shop_text', __('تصفح المتجر', 'baraa-pharmacy'))); ?>
            </a>
        <?php endif; ?>

        <!-- Quick links -->
        <div class="flex items-center gap-space-md flex-wrap justify-center font-label-sm text-label-sm text-on-surface-variant">
            <a class="hover:text-primary transition-colors flex items-center gap-1" href="https://wa.me/201556859030" target="_blank" rel="noopener noreferrer">
                <span class="material-symbols-outlined text-[16px]" aria-hidden="true">chat</span>
                <?php esc_html_e('استشارة صيدلي', 'baraa-pharmacy'); ?>
            </a>
        </div>

    </div>
</section>
