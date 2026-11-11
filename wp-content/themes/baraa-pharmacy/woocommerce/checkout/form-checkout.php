<?php
/**
 * Checkout Form — Baraa Pharmacy
 * Custom two-column layout that matches the cart page design language
 * (MD3 / Tailwind tokens). All WooCommerce hooks are preserved so the
 * checkout JS and payment gateways (e.g. Kashier) keep working.
 *
 * @see https://woocommerce.com/document/template-structure/
 */
defined('ABSPATH') || exit;

// If checkout registration is disabled and not logged in, the user cannot checkout.
if (!$checkout->is_registration_enabled() && $checkout->is_registration_required() && !is_user_logged_in()) {
    echo esc_html(apply_filters('woocommerce_checkout_must_be_logged_in_message', __('يجب تسجيل الدخول لإتمام الطلب.', 'baraa-pharmacy')));
    return;
}

$item_count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
$cart_url   = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/');
?>

<div class="w-full px-margin pb-space-xl">

    <!-- ═══ HEADER BAR — Title + trust badges ═══════════════ -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-space-md bg-surface-container-lowest p-space-lg rounded-xl shadow-sm mb-space-lg">
        <div class="flex items-center gap-space-md">
            <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                <span class="material-symbols-outlined text-[28px]" style="font-variation-settings:'FILL' 1" aria-hidden="true">lock</span>
            </div>
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="font-headline-md text-headline-md font-bold text-on-surface">
                        <?php esc_html_e('إتمام الطلب', 'baraa-pharmacy'); ?>
                    </h1>
                    <span class="px-2.5 py-0.5 rounded-full bg-primary-fixed text-on-primary-fixed font-label-sm text-label-sm font-semibold flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse" aria-hidden="true"></span>
                        <?php
                        printf(
                            esc_html(_n('%s صنف في الطلب', '%s أصناف في الطلب', $item_count, 'baraa-pharmacy')),
                            esc_html(number_format_i18n($item_count))
                        );
                        ?>
                    </span>
                </div>
                <p class="font-body-sm text-body-sm text-on-surface-variant mt-0.5">
                    <?php esc_html_e('خطوة أخيرة — راجع بياناتك واختر طريقة الدفع لإتمام طلبك بأمان', 'baraa-pharmacy'); ?>
                </p>
            </div>
        </div>

        <div class="flex items-center flex-wrap gap-space-sm font-label-sm text-label-sm text-on-surface-variant">
            <a class="flex items-center gap-1.5 px-3 py-1.5 bg-surface-container-low rounded-lg hover:bg-surface-container transition-colors text-primary font-semibold"
               href="<?php echo esc_url($cart_url); ?>">
                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">arrow_back</span>
                <span><?php esc_html_e('العودة إلى السلة', 'baraa-pharmacy'); ?></span>
            </a>
            <div class="flex items-center gap-1.5 px-3 py-1.5 bg-surface-container-low rounded-lg">
                <span class="material-symbols-outlined text-primary text-[18px]" aria-hidden="true">encrypted</span>
                <span><?php esc_html_e('دفع مشفّر 100%', 'baraa-pharmacy'); ?></span>
            </div>
            <div class="flex items-center gap-1.5 px-3 py-1.5 bg-surface-container-low rounded-lg">
                <span class="material-symbols-outlined text-secondary text-[18px]" aria-hidden="true">local_shipping</span>
                <span><?php esc_html_e('توصيل سريع ومنظّم', 'baraa-pharmacy'); ?></span>
            </div>
        </div>
    </div>

    <?php do_action('woocommerce_before_checkout_form', $checkout); ?>

    <form name="checkout" method="post" class="checkout woocommerce-checkout baraa-checkout" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__('إتمام الطلب', 'baraa-pharmacy'); ?>">

        <div class="baraa-checkout-grid flex flex-col lg:flex-row gap-space-lg items-start">

            <!-- ═══ LEFT: customer details ════════════════════ -->
            <div class="w-full lg:flex-1">
                <?php do_action('woocommerce_checkout_before_customer_details'); ?>

                <div id="customer_details">
                    <?php do_action('woocommerce_checkout_billing'); ?>
                </div>

                <?php do_action('woocommerce_checkout_after_customer_details'); ?>
            </div>

            <!-- ═══ RIGHT: order review (sticky) ══════════════ -->
            <aside class="w-full lg:w-[420px] xl:w-[460px] flex-shrink-0 lg:sticky lg:top-48">
                <div class="bg-surface-container-lowest rounded-xl shadow-sm p-space-lg">
                    <h2 id="order_review_heading" class="font-title-lg text-title-lg font-bold text-on-surface flex items-center gap-2 mb-space-md">
                        <span class="material-symbols-outlined text-primary text-[22px]" aria-hidden="true">receipt_long</span>
                        <?php esc_html_e('ملخص الطلب', 'baraa-pharmacy'); ?>
                    </h2>

                    <?php do_action('woocommerce_checkout_before_order_review'); ?>

                    <div id="order_review" class="woocommerce-checkout-review-order">
                        <?php do_action('woocommerce_checkout_order_review'); ?>
                    </div>

                    <?php do_action('woocommerce_checkout_after_order_review'); ?>
                </div>

                <!-- Security badges -->
                <div class="mt-space-md p-space-md rounded-xl bg-surface-container-low space-y-2 text-center text-on-surface-variant">
                    <div class="flex items-center justify-center gap-4 text-primary">
                        <div class="flex items-center gap-1 font-label-sm text-label-sm font-semibold">
                            <span class="material-symbols-outlined text-[16px]" aria-hidden="true">lock</span>
                            <?php esc_html_e('تشفير مصرفي 256-Bit', 'baraa-pharmacy'); ?>
                        </div>
                        <div class="flex items-center gap-1 font-label-sm text-label-sm font-semibold">
                            <span class="material-symbols-outlined text-[16px]" aria-hidden="true">health_and_safety</span>
                            <?php esc_html_e('تأمين دوائي معتمد', 'baraa-pharmacy'); ?>
                        </div>
                    </div>
                </div>
            </aside>

        </div>
    </form>
</div>

<?php do_action('woocommerce_after_checkout_form', $checkout); ?>
