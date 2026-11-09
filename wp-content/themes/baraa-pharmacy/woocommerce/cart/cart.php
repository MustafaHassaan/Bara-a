<?php
/**
 * Cart Page — Baraa Pharmacy
 * Matches the detailed pharmacy cart design.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @version 11.2.0
 */
defined('ABSPATH') || exit;

do_action('woocommerce_before_cart');

$cart_count = WC()->cart->get_cart_contents_count();
$cart_items = WC()->cart->get_cart();
?>

<div class="flex flex-col w-full">

<!-- ═══════════════════════════════════════════════════════
     HEADER BAR — Title + Trust badges
════════════════════════════════════════════════════════ -->
<div class="w-full px-margin pb-space-lg">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-space-md bg-surface-container-lowest p-space-lg rounded-xl shadow-sm">

        <div class="flex items-center gap-space-md">
            <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                <span class="material-symbols-outlined text-[28px]" style="font-variation-settings:'FILL' 1" aria-hidden="true">shopping_cart_checkout</span>
            </div>
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="font-headline-md text-headline-md font-bold text-on-surface">
                        <?php esc_html_e('سلة المشتريات والطلب السريع', 'baraa-pharmacy'); ?>
                    </h1>
                    <span class="px-2.5 py-0.5 rounded-full bg-primary-fixed text-on-primary-fixed font-label-sm text-label-sm font-semibold flex items-center gap-1"
                          id="baraa-cart-count-badge">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse" aria-hidden="true"></span>
                        <?php
                        printf(
                            esc_html(_n('%s صنف مختار', '%s أصناف مختارة', $cart_count, 'baraa-pharmacy')),
                            esc_html(number_format_i18n($cart_count))
                        );
                        ?>
                    </span>
                </div>
                <p class="font-body-sm text-body-sm text-on-surface-variant mt-0.5">
                    <?php esc_html_e('تأكيد الوصفات الطبية، سلسلة التبريد المعتمدة، وسداد آمن مرخص من وزارة الصحة', 'baraa-pharmacy'); ?>
                </p>
            </div>
        </div>

        <!-- Trust badges -->
        <div class="flex items-center flex-wrap gap-space-sm font-label-sm text-label-sm text-on-surface-variant">
            <div class="flex items-center gap-1.5 px-3 py-1.5 bg-surface-container-low rounded-lg">
                <span class="material-symbols-outlined text-primary text-[18px]" aria-hidden="true">ac_unit</span>
                <span><?php esc_html_e('سلسلة تبريد مخصصة 2-8°م', 'baraa-pharmacy'); ?></span>
            </div>
            <div class="flex items-center gap-1.5 px-3 py-1.5 bg-surface-container-low rounded-lg">
                <span class="material-symbols-outlined text-secondary text-[18px]" aria-hidden="true">verified_user</span>
                <span><?php esc_html_e('منتجات أصلية 100% مضمونة', 'baraa-pharmacy'); ?></span>
            </div>
            <div class="flex items-center gap-1.5 px-3 py-1.5 bg-surface-container-low rounded-lg">
                <span class="material-symbols-outlined text-primary text-[18px]" aria-hidden="true">bolt</span>
                <span><?php esc_html_e('توصيل خلال 45-60 دقيقة', 'baraa-pharmacy'); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     PRESCRIPTION UPLOAD SECTION
════════════════════════════════════════════════════════ -->
<?php
// Check if any product in cart requires prescription
$has_rx_products = false;
foreach ($cart_items as $cart_item) {
    $_product = $cart_item['data'];
    if ($_product instanceof WC_Product) {
        $product_id = $_product->get_id();
        $requires_rx = get_post_meta($product_id, '_requires_prescription', true);
        if ($requires_rx === 'yes') {
            $has_rx_products = true;
            break;
        }
    }
}
?>

<?php if ($has_rx_products) : ?>
<section class="w-full px-margin pb-space-lg" id="baraa-prescription-section">
    <div class="relative overflow-hidden rounded-xl bg-gradient-to-l from-primary via-primary-container to-secondary text-on-primary shadow-xl">
        <div class="absolute -left-12 -bottom-12 w-64 h-64 rounded-full bg-primary-fixed/10 blur-2xl pointer-events-none" aria-hidden="true"></div>
        <div class="absolute right-1/3 -top-16 w-80 h-80 rounded-full bg-secondary-fixed/15 blur-3xl pointer-events-none" aria-hidden="true"></div>

        <div class="relative z-10 p-space-lg md:p-space-xl flex flex-col lg:flex-row items-center justify-between gap-space-lg">
            <div class="flex items-start gap-space-md max-w-3xl">
                <div class="w-14 h-14 rounded-xl bg-surface-container-lowest/15 backdrop-blur-md flex items-center justify-center text-primary-fixed shrink-0">
                    <span class="material-symbols-outlined text-[32px]" aria-hidden="true">document_scanner</span>
                </div>
                <div class="space-y-1.5 text-right">
                    <div class="inline-flex items-center gap-1.5 px-space-sm py-0.5 rounded-full bg-primary-fixed text-on-primary-fixed font-label-sm text-label-sm font-bold">
                        <span class="material-symbols-outlined text-[13px]" aria-hidden="true">verified</span>
                        <?php esc_html_e('قارئ الروشتة الذكي بالذكاء الاصطناعي', 'baraa-pharmacy'); ?>
                    </div>
                    <h2 class="font-headline-md text-headline-md font-bold text-on-primary leading-tight">
                        <?php esc_html_e('هناك منتجات في سلتك تحتاج روشتة طبية', 'baraa-pharmacy'); ?>
                    </h2>
                    <p class="font-body-md text-body-md text-on-primary/90 leading-relaxed">
                        <?php esc_html_e('ارفع صورة الروشتة الورقية أو التحليل؛ وسيقوم صيدلي مرخص بمراجعتها فوراً لتأكيد طلبك.', 'baraa-pharmacy'); ?>
                    </p>
                    <!-- Status message -->
                    <div id="baraa-rx-status" class="hidden mt-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-on-primary text-primary font-label-sm text-label-sm font-bold">
                            <span class="material-symbols-outlined text-[14px]" aria-hidden="true">check_circle</span>
                            <?php esc_html_e('تم رفع الروشتة بنجاح - بانتظار مراجعة الصيدلي', 'baraa-pharmacy'); ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row items-center gap-space-sm w-full lg:w-auto shrink-0">
                <label id="baraa-rx-upload-label" class="w-full sm:w-auto cursor-pointer flex items-center justify-center gap-2 px-space-lg py-3 rounded-lg bg-surface-container-lowest text-primary font-title-md text-title-md font-bold shadow-md hover:bg-surface-container-low transition-all">
                    <span class="material-symbols-outlined text-[22px]" aria-hidden="true">upload_file</span>
                    <span id="baraa-rx-upload-text"><?php esc_html_e('رفع الروشتة الآن', 'baraa-pharmacy'); ?></span>
                    <input type="file" accept="image/*,.pdf" class="hidden" id="baraa-rx-file-input" aria-label="<?php esc_attr_e('رفع صورة الروشتة', 'baraa-pharmacy'); ?>" />
                </label>
                <a
                    class="w-full sm:w-auto flex items-center justify-center gap-1.5 px-space-md py-3 rounded-lg bg-primary-fixed/20 hover:bg-primary-fixed/30 text-on-primary font-label-md text-label-md backdrop-blur-sm transition-colors text-center"
                    href="https://wa.me/201556859030"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <span class="material-symbols-outlined text-[18px]" aria-hidden="true">chat</span>
                    <span><?php esc_html_e('تواصل مع الصيدلي', 'baraa-pharmacy'); ?></span>
                </a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════
     MAIN GRID — Items (right) + Summary (left)
════════════════════════════════════════════════════════ -->
<div class="w-full px-margin pb-space-xl">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg items-start">

        <!-- ══ LEFT: Cart items ══════════════════════════════ -->
        <div class="lg:col-span-7 xl:col-span-8 flex flex-col gap-space-md">

            <!-- Cart items card -->
            <div class="bg-surface-container-lowest rounded-xl p-space-md shadow-sm">

                <div class="flex items-center justify-between pb-space-md border-b border-outline-variant/30 mb-space-md">
                    <span class="font-title-lg text-title-lg font-bold text-on-surface">
                        <?php esc_html_e('الأدوية والمستلزمات في السلة', 'baraa-pharmacy'); ?>
                    </span>
                    <?php if (!WC()->cart->is_empty()) : ?>
                        <form method="post" action="<?php echo esc_url(wc_get_cart_url()); ?>" class="inline">
                            <?php wp_nonce_field('baraa-clear-cart', 'baraa-clear-cart-nonce'); ?>
                            <button
                                type="submit"
                                name="baraa_clear_cart"
                                value="1"
                                data-confirm="<?php esc_attr_e('هل تود بالتأكيد إفراغ جميع عناصر السلة؟', 'baraa-pharmacy'); ?>"
                                class="font-label-md text-label-md text-error hover:text-on-error-container flex items-center gap-1 transition-colors bg-transparent border-0 cursor-pointer"
                            >
                                <span class="material-symbols-outlined text-[16px]" aria-hidden="true">delete_sweep</span>
                                <?php esc_html_e('تفريغ السلة', 'baraa-pharmacy'); ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- WooCommerce notices -->
                <?php do_action('woocommerce_before_cart_table'); ?>

                <!-- Items loop -->
                <form class="woocommerce-cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">

                    <div class="space-y-space-md woocommerce-cart-form__contents">
                        <?php do_action('woocommerce_before_cart_contents'); ?>

                        <?php foreach ($cart_items as $cart_item_key => $cart_item) :
                            $_product       = apply_filters('woocommerce_cart_item_product',    $cart_item['data'],       $cart_item, $cart_item_key);
                            $product_id     = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
                            $visible        = apply_filters('woocommerce_cart_item_visible',    true,                     $cart_item, $cart_item_key);

                            if (!($_product instanceof WC_Product) || !$_product->exists() || $cart_item['quantity'] <= 0 || !$visible) {
                                continue;
                            }

                            $cart_item_name  = WC()->cart->get_item_product_name($cart_item, $_product);
                            $product_name    = apply_filters('woocommerce_cart_item_name',      $cart_item_name,          $cart_item, $cart_item_key);
                            $product_link    = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
                            $thumbnail       = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image('woocommerce_thumbnail', array('class' => 'w-full h-full object-contain')), $cart_item, $cart_item_key);
                            $min_qty         = 1;
                            $max_qty         = $_product->is_sold_individually() ? 1 : $_product->get_max_purchase_quantity();
                            $product_qty     = woocommerce_quantity_input(
                                array(
                                    'input_name'   => "cart[{$cart_item_key}][qty]",
                                    'input_value'  => $cart_item['quantity'],
                                    'max_value'    => $max_qty,
                                    'min_value'    => $min_qty,
                                    'product_name' => $product_name,
                                ),
                                $_product,
                                false
                            );
                            $categories      = wc_get_product_category_list($_product->get_id(), ', ');
                            $requires_rx     = get_post_meta($product_id, '_requires_prescription', true);
                            $is_rx           = ($requires_rx === 'yes');
                        ?>

                        <article class="baraa-cart-item woocommerce-cart-form__cart-item <?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)); ?> p-space-md rounded-xl bg-surface-container-low hover:bg-surface-container transition-all flex flex-col sm:flex-row items-start gap-space-md">

                            <!-- Product image -->
                            <div class="w-24 h-24 rounded-lg bg-surface-container-lowest p-1 shrink-0 flex items-center justify-center overflow-hidden">
                                <?php if ($product_link) : ?>
                                    <a href="<?php echo esc_url($product_link); ?>" tabindex="-1" aria-hidden="true">
                                        <?php echo wp_kses_post($thumbnail); ?>
                                    </a>
                                <?php else : ?>
                                    <?php echo wp_kses_post($thumbnail); ?>
                                <?php endif; ?>
                            </div>

                            <!-- Details -->
                            <div class="flex-1 min-w-0 text-right w-full">

                                <!-- Category + badges row -->
                                <div class="flex items-center gap-2 flex-wrap mb-1">
                                    <?php if ($categories) : ?>
                                        <span class="px-2 py-0.5 rounded bg-surface-container font-label-sm text-label-sm font-semibold text-on-surface-variant">
                                            <?php echo wp_kses_post($categories); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($is_rx) : ?>
                                        <span class="px-2 py-0.5 rounded bg-primary-fixed text-on-primary-fixed font-label-sm text-label-sm font-semibold flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[13px]" aria-hidden="true">check_circle</span>
                                            <?php esc_html_e('يتطلب روشتة معتمدة', 'baraa-pharmacy'); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($_product->is_on_sale()) : ?>
                                        <span class="px-2 py-0.5 rounded bg-tertiary-fixed text-on-tertiary-fixed font-label-sm text-label-sm font-bold">
                                            <?php esc_html_e('عرض', 'baraa-pharmacy'); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Name -->
                                <h3 class="font-title-md text-title-md font-bold text-on-surface">
                                    <?php if ($product_link) : ?>
                                        <a class="hover:text-primary transition-colors" href="<?php echo esc_url($product_link); ?>">
                                            <?php echo wp_kses_post($cart_item_name); ?>
                                        </a>
                                    <?php else : ?>
                                        <?php echo wp_kses_post($product_name); ?>
                                    <?php endif; ?>
                                </h3>

                                <!-- Short description -->
                                <?php $short = $_product->get_short_description(); if ($short) : ?>
                                    <p class="font-body-sm text-body-sm text-on-surface-variant mt-0.5 line-clamp-2">
                                        <?php echo wp_kses_post($short); ?>
                                    </p>
                                <?php endif; ?>

                                <!-- WC variation / item data -->
                                <?php do_action('woocommerce_after_cart_item_name', $cart_item, $cart_item_key); ?>
                                <?php echo wp_kses_post(wc_get_formatted_cart_item_data($cart_item, false, $cart_item_name)); ?>

                                <!-- Backorder notice -->
                                <?php if ($_product->backorders_require_notification() && $_product->is_on_backorder($cart_item['quantity'])) : ?>
                                    <?php echo wp_kses_post(apply_filters('woocommerce_cart_item_backorder_notification', '<p class="font-body-sm text-body-sm text-on-surface-variant mt-1">' . esc_html__('Available on backorder', 'woocommerce') . '</p>', $product_id)); ?>
                                <?php endif; ?>

                                <!-- Authenticity badges row -->
                                <div class="flex items-center gap-3 mt-2 text-primary font-label-sm text-label-sm font-semibold flex-wrap">
                                    <span class="flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[15px]" aria-hidden="true">verified</span>
                                        <?php esc_html_e('وكيل معتمد', 'baraa-pharmacy'); ?>
                                    </span>
                                    <span class="flex items-center gap-1 text-on-surface-variant">
                                        <span class="material-symbols-outlined text-[15px]" aria-hidden="true">swap_horizontal_circle</span>
                                        <?php esc_html_e('إرجاع مجاني خلال 14 يوم', 'baraa-pharmacy'); ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Controls: price + qty + remove -->
                            <div class="flex sm:flex-col items-center sm:items-end justify-between w-full sm:w-auto gap-space-sm shrink-0 pt-1 sm:pt-0">

                                <!-- Subtotal -->
                                <div class="text-right">
                                    <span class="font-title-lg text-title-lg font-bold text-primary block leading-none">
                                        <?php echo wp_kses_post(apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key)); ?>
                                    </span>
                                    <span class="font-label-sm text-label-sm text-on-surface-variant">
                                        <?php esc_html_e('سعر الوحدة:', 'baraa-pharmacy'); ?>
                                        <?php echo wp_kses_post(apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key)); ?>
                                    </span>
                                </div>

                                <!-- Qty stepper -->
                                <div class="flex items-center gap-1 bg-surface-container-lowest p-1 rounded-lg border border-outline-variant/20">
                                    <button
                                        type="button"
                                        class="baraa-qty-step w-7 h-7 rounded flex items-center justify-center text-on-surface-variant hover:bg-surface-container hover:text-primary transition-colors"
                                        data-quantity-step="-1"
                                        aria-label="<?php esc_attr_e('تقليل الكمية', 'baraa-pharmacy'); ?>"
                                        <?php disabled($cart_item['quantity'] <= $min_qty); ?>
                                    >
                                        <span class="material-symbols-outlined text-[18px]" aria-hidden="true">remove</span>
                                    </button>
                                    <?php echo apply_filters('woocommerce_cart_item_quantity', $product_qty, $cart_item_key, $cart_item); // phpcs:ignore ?>
                                    <button
                                        type="button"
                                        class="baraa-qty-step w-7 h-7 rounded flex items-center justify-center text-on-surface-variant hover:bg-surface-container hover:text-primary transition-colors"
                                        data-quantity-step="1"
                                        aria-label="<?php esc_attr_e('زيادة الكمية', 'baraa-pharmacy'); ?>"
                                        <?php disabled($_product->is_sold_individually() || ($max_qty > 0 && $cart_item['quantity'] >= $max_qty)); ?>
                                    >
                                        <span class="material-symbols-outlined text-[18px]" aria-hidden="true">add</span>
                                    </button>
                                </div>

                                <!-- Remove -->
                                <?php
                                echo apply_filters(
                                    'woocommerce_cart_item_remove_link',
                                    sprintf(
                                        '<a role="button" href="%s" class="remove p-1 text-outline hover:text-error transition-colors" aria-label="%s" data-product_id="%s" data-product_sku="%s" title="%s"><span class="material-symbols-outlined text-[18px]" aria-hidden="true">delete</span></a>',
                                        esc_url(wc_get_cart_remove_url($cart_item_key)),
                                        esc_attr(sprintf(__('إزالة %s من السلة', 'woocommerce'), wp_strip_all_tags($product_name))),
                                        esc_attr($product_id),
                                        esc_attr($_product->get_sku()),
                                        esc_attr__('حذف من السلة', 'baraa-pharmacy')
                                    ),
                                    $cart_item_key
                                ); // phpcs:ignore
                                ?>
                            </div>
                        </article>

                        <?php endforeach; ?>

                        <?php do_action('woocommerce_cart_contents'); ?>
                    </div><!-- /.woocommerce-cart-form__contents -->

                    <!-- Update cart button (hidden, used by JS auto-update) -->
                    <button type="submit" class="hidden" name="update_cart" value="1" id="baraa-update-cart-btn">
                        <?php esc_html_e('تحديث السلة', 'woocommerce'); ?>
                    </button>
                    <?php do_action('woocommerce_cart_actions'); ?>
                    <?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
                    <?php do_action('woocommerce_after_cart_contents'); ?>
                </form>

                <?php do_action('woocommerce_after_cart_table'); ?>
            </div><!-- /.items card -->

            <!-- Cold chain protocol panel -->
            <div class="rounded-xl p-space-lg bg-surface-container-low relative overflow-hidden">
                <div class="flex flex-col md:flex-row items-start md:items-center gap-space-md">
                    <div class="w-14 h-14 rounded-xl bg-secondary-fixed text-on-secondary-fixed flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[32px]" aria-hidden="true">severe_cold</span>
                    </div>
                    <div class="space-y-1 flex-1">
                        <h4 class="font-title-lg text-title-lg font-bold text-on-surface flex items-center gap-2">
                            <?php esc_html_e('بروتوكول سلسلة التبريد الدوائي الحصري (Cold Chain Safety)', 'baraa-pharmacy'); ?>
                            <span class="w-2.5 h-2.5 rounded-full bg-secondary animate-ping" aria-hidden="true"></span>
                        </h4>
                        <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">
                            <?php esc_html_e('يتم نقل وتوصيل الأدوية الحساسة في حقائب حرارية مبردة مخصصة تضمن ثبات درجة الحرارة بين 2° إلى 8° مئوية من لحظة خروجها من مخزن الفرع وحتى باب منزلك مع مجس إلكتروني لتسجيل درجات الحرارة.', 'baraa-pharmacy'); ?>
                        </p>
                    </div>
                    <div class="bg-surface-container-lowest px-4 py-3 rounded-xl flex items-center gap-3 shrink-0">
                        <span class="material-symbols-outlined text-primary text-[28px]" aria-hidden="true">thermostat</span>
                        <div class="text-right">
                            <span class="font-label-sm text-label-sm text-outline block leading-none">
                                <?php esc_html_e('حرارة الحقيبة الآن', 'baraa-pharmacy'); ?>
                            </span>
                            <span class="font-title-lg text-title-lg font-bold text-primary leading-tight">4.2°C ✓</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delivery note -->
            <div class="bg-surface-container-lowest rounded-xl p-space-md shadow-sm">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[22px]" aria-hidden="true">local_shipping</span>
                    <span class="font-body-sm text-body-sm text-on-surface-variant">
                        <?php esc_html_e('سيتم تحديد عنوان التوصيل وخيارات الشحن عند إتمام الطلب.', 'baraa-pharmacy'); ?>
                    </span>
                </div>
            </div>

        </div><!-- /.lg:col-span-7 -->


        <!-- ══ RIGHT: Order summary + checkout ═══════════════ -->
        <div class="lg:col-span-5 xl:col-span-4 flex flex-col gap-space-md lg:sticky lg:top-48">

            <?php if ($has_rx_products) : ?>
            <!-- Rx in cart: hide summary/payment/checkout, route to WhatsApp instead -->
            <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm space-y-space-md text-center" aria-label="<?php esc_attr_e('إتمام الشراء يتطلب مراجعة صيدلي', 'baraa-pharmacy'); ?>">
                <div class="w-14 h-14 mx-auto rounded-xl bg-primary-fixed text-on-primary-fixed flex items-center justify-center">
                    <span class="material-symbols-outlined text-[30px]" aria-hidden="true">prescriptions</span>
                </div>
                <h2 class="font-headline-md text-headline-md font-bold text-on-surface">
                    <?php esc_html_e('لا يمكن إتمام الطلب أونلاين', 'baraa-pharmacy'); ?>
                </h2>
                <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed">
                    <?php esc_html_e('سلتك تحتوي على دواء يتطلب روشتة طبية (Rx). تواصل مع الصيدلي عبر واتساب لإتمام طلبك بعد مراجعة الروشتة.', 'baraa-pharmacy'); ?>
                </p>
                <a
                    class="w-full flex items-center justify-center gap-2 px-space-lg py-3 rounded-lg bg-primary text-on-primary font-title-md text-title-md font-bold shadow-md hover:bg-primary-container transition-colors"
                    href="https://wa.me/201556859030"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <span class="material-symbols-outlined text-[20px]" aria-hidden="true">chat</span>
                    <span><?php esc_html_e('تواصل مع الصيدلي عبر واتساب', 'baraa-pharmacy'); ?></span>
                </a>
            </div>

            <?php else : ?>
            <!-- Order summary card -->
            <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm space-y-space-md" aria-label="<?php esc_attr_e('ملخص الطلب', 'baraa-pharmacy'); ?>">

                <div class="flex items-center justify-between pb-space-sm border-b border-outline-variant/30">
                    <span class="font-headline-md text-headline-md font-bold text-on-surface">
                        <?php esc_html_e('ملخص الطلب', 'baraa-pharmacy'); ?>
                    </span>
                    <span class="px-2.5 py-1 rounded-full bg-secondary-fixed text-on-secondary-fixed font-label-sm text-label-sm font-semibold">
                        #BR-<?php echo esc_html(substr(wp_generate_uuid4(), 0, 4)); ?>
                    </span>
                </div>

                <!-- WC cart totals (subtotal, shipping, discount, total) -->
                <div class="cart-collaterals">
                    <?php do_action('woocommerce_cart_collaterals'); ?>
                </div>
                <style>
                    /* Hide default WooCommerce checkout button */
                    .cart_totals .wc-proceed-to-checkout {
                        display: none !important;
                    }
                </style>

                <!-- Checkout button -->
                <div class="pt-space-sm space-y-space-xs">
                    <div class="wc-proceed-to-checkout" id="baraa-checkout-button-container">
                        <?php do_action('woocommerce_proceed_to_checkout'); ?>
                    </div>
                    <p class="text-center font-label-sm text-label-sm text-outline">
                        <?php esc_html_e('بالنقر على تأكيد الطلب، فإنك توافق على الشروط الطبية وضوابط صرف الوصفات بوزارة الصحة المصرية.', 'baraa-pharmacy'); ?>
                    </p>
                </div>

            </div><!-- /.summary card -->
            <?php endif; ?>

            <!-- Security badges -->
            <div class="p-space-md rounded-xl bg-surface-container-low space-y-2 text-center text-on-surface-variant">
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
                <p class="font-body-sm text-body-sm text-outline">
                    <?php esc_html_e('جميع الأدوية المصروفة خاضعة للإشراف التام من الهيئة المصرية للشراء الموحد وهيئة الدواء المصرية (EDA).', 'baraa-pharmacy'); ?>
                </p>
            </div>

        </div><!-- /.lg:col-span-5 -->
    </div><!-- /.grid -->
</div><!-- /.px-margin -->

</div><!-- /.flex flex-col -->

<?php do_action('woocommerce_after_cart'); ?>

<?php if ($has_rx_products) : ?>
<script>
(function() {
    'use strict';

    var hasRxProducts = true;
    var isRxUploaded = false;
    var fileInput = document.getElementById('baraa-rx-file-input');
    var uploadLabel = document.getElementById('baraa-rx-upload-label');
    var uploadText = document.getElementById('baraa-rx-upload-text');
    var statusDiv = document.getElementById('baraa-rx-status');
    var checkoutContainer = document.getElementById('baraa-checkout-button-container');
    var checkoutButton = checkoutContainer ? checkoutContainer.querySelector('a.checkout-button') : null;

    // Handle file upload
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            var file = e.target.files[0];
            if (file) {
                isRxUploaded = true;
                uploadText.textContent = file.name;
                uploadLabel.classList.add('bg-primary', 'text-on-primary');
                uploadLabel.classList.remove('bg-surface-container-lowest', 'text-primary');

                if (statusDiv) {
                    statusDiv.classList.remove('hidden');
                }

                // Re-enable checkout button
                if (checkoutButton) {
                    checkoutButton.classList.remove('opacity-50', 'pointer-events-none');
                    checkoutButton.removeAttribute('disabled');
                }
            }
        });
    }

    // Initially disable checkout button if it exists
    if (checkoutButton && hasRxProducts && !isRxUploaded) {
        checkoutButton.classList.add('opacity-50', 'pointer-events-none');
        checkoutButton.setAttribute('disabled', 'true');
    }

    // Prevent checkout if prescription not uploaded
    if (checkoutButton) {
        checkoutButton.addEventListener('click', function(e) {
            if (hasRxProducts && !isRxUploaded) {
                e.preventDefault();
                e.stopPropagation();

                // Scroll to prescription section
                var prescriptionSection = document.getElementById('baraa-prescription-section');
                if (prescriptionSection) {
                    prescriptionSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }

                // Show alert
                alert('<?php esc_html_e('يرجى رفع صورة الروشتة الطبية قبل إتمام الطلب. الأدوية في سلتك تحتاج وصفة طبية معتمدة.', 'baraa-pharmacy'); ?>');

                // Flash the upload section
                if (uploadLabel) {
                    uploadLabel.style.boxShadow = '0 0 0 4px rgba(186, 26, 26, 0.5)';
                    setTimeout(function() {
                        uploadLabel.style.boxShadow = '';
                    }, 2000);
                }
            }
        });
    }

    // Store state in sessionStorage (optional - persists across page reloads)
    if (sessionStorage.getItem('baraa_rx_uploaded') === 'true') {
        isRxUploaded = true;
        if (statusDiv) {
            statusDiv.classList.remove('hidden');
        }
        if (uploadText) {
            uploadText.textContent = '<?php esc_html_e('الروشتة مرفوعة مسبقاً', 'baraa-pharmacy'); ?>';
        }
        if (checkoutButton) {
            checkoutButton.classList.remove('opacity-50', 'pointer-events-none');
            checkoutButton.removeAttribute('disabled');
        }
    }

    // Save state when uploaded
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            if (isRxUploaded) {
                sessionStorage.setItem('baraa_rx_uploaded', 'true');
            }
        });
    }

})();
</script>
<?php endif; ?>
