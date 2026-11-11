<?php
/**
 * Order received (thank-you) — Baraa Pharmacy
 *
 * Custom replacement for WooCommerce's default thankyou.php so the page
 * matches the storefront MD3 / Tailwind design language, is fully Arabic and
 * supports dark mode. The order summary, line items and delivery details are
 * rendered directly from the order object; the payment-gateway thank-you hook
 * is still fired so gateways (e.g. COD) can print their own notice.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @version 11.2.0
 *
 * @var WC_Order|false $order
 */
defined('ABSPATH') || exit;

if (!isset($order)) {
    $order = false;
}
?>

<div class="woocommerce-order baraa-thankyou w-full px-margin pb-space-xl">

    <?php if ($order) : ?>

        <?php do_action('woocommerce_before_thankyou', $order->get_id()); ?>

        <?php if ($order->has_status('failed')) : ?>

            <!-- ═══ FAILED ORDER ═══════════════════════════════════ -->
            <div class="baraa-thankyou-card baraa-thankyou-failed rounded-xl shadow-sm p-space-lg text-right">
                <div class="flex items-start gap-space-md">
                    <div class="w-12 h-12 rounded-xl bg-error/10 flex items-center justify-center text-error shrink-0">
                        <span class="material-symbols-outlined text-[28px]" aria-hidden="true">error</span>
                    </div>
                    <div class="space-y-1">
                        <h1 class="font-headline-md text-headline-md font-bold">
                            <?php esc_html_e('تعذّر إتمام عملية الدفع', 'baraa-pharmacy'); ?>
                        </h1>
                        <p class="font-body-md text-body-md text-on-surface-variant">
                            <?php esc_html_e('لم يتمكّن البنك من إتمام العملية. يمكنك إعادة المحاولة أو اختيار طريقة دفع أخرى.', 'baraa-pharmacy'); ?>
                        </p>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row gap-space-sm mt-space-lg">
                    <a href="<?php echo esc_url($order->get_checkout_payment_url()); ?>"
                       class="baraa-thankyou-btn-primary inline-flex items-center justify-center gap-2 px-space-lg py-3 rounded-lg font-title-md text-title-md font-bold">
                        <span class="material-symbols-outlined text-[20px]" aria-hidden="true">refresh</span>
                        <span><?php esc_html_e('إعادة محاولة الدفع', 'baraa-pharmacy'); ?></span>
                    </a>
                    <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"
                       class="baraa-thankyou-btn-ghost inline-flex items-center justify-center gap-2 px-space-lg py-3 rounded-lg font-title-md text-title-md font-bold">
                        <span class="material-symbols-outlined text-[20px]" aria-hidden="true">storefront</span>
                        <span><?php esc_html_e('العودة إلى المتجر', 'baraa-pharmacy'); ?></span>
                    </a>
                </div>
            </div>

        <?php else : ?>

            <?php
            $order_number  = $order->get_order_number();
            $order_total   = $order->get_formatted_order_total();
            $payment_title = $order->get_payment_method_title();

            $ar_months = array(
                1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
                5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
                9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
            );
            $created_at = $order->get_date_created();
            if ($created_at && baraa_pharmacy_is_english_visitor()) {
                $order_date = $created_at->date_i18n('j F Y');
            } else {
                $order_date = $created_at
                    ? $created_at->date_i18n('j') . ' ' . $ar_months[(int) $created_at->date_i18n('n')] . ' ' . $created_at->date_i18n('Y')
                    : '';
            }

            $shipping_display = str_replace(
                array('Free shipping', 'Free!'),
                array(__('شحن مجاني', 'baraa-pharmacy'), __('مجانًا', 'baraa-pharmacy')),
                $order->get_shipping_to_display()
            );

            $billing_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());

            $country_code = $order->get_billing_country();
            $country_name = '';
            if ($country_code && function_exists('WC')) {
                $countries    = WC()->countries->get_countries();
                $country_name = isset($countries[$country_code]) ? $countries[$country_code] : $country_code;
            }

            $address_lines = array_values(array_filter(array(
                $order->get_billing_address_1(),
                $order->get_billing_address_2(),
                $order->get_billing_city(),
                $order->get_billing_state(),
                $country_name,
            )));

            $billing_phone = $order->get_billing_phone();
            ?>

            <!-- ═══ HERO ═══════════════════════════════════════════ -->
            <div class="baraa-thankyou-hero relative overflow-hidden rounded-xl shadow-xl mb-space-lg">
                <div class="absolute -left-12 -bottom-12 w-64 h-64 rounded-full bg-white/10 blur-2xl pointer-events-none" aria-hidden="true"></div>
                <div class="absolute right-1/3 -top-16 w-80 h-80 rounded-full bg-white/10 blur-3xl pointer-events-none" aria-hidden="true"></div>

                <div class="relative z-10 p-space-lg md:p-space-xl flex flex-col md:flex-row md:items-center justify-between gap-space-lg">
                    <div class="flex items-start gap-space-md text-right">
                        <div class="w-14 h-14 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[34px]" style="font-variation-settings:'FILL' 1" aria-hidden="true">check_circle</span>
                        </div>
                        <div class="space-y-1">
                            <h1 class="font-headline-md text-headline-md font-bold">
                                <?php esc_html_e('تم استلام طلبك بنجاح', 'baraa-pharmacy'); ?>
                            </h1>
                            <p class="font-body-md text-body-md opacity-90 leading-relaxed max-w-xl">
                                <?php esc_html_e('شكرًا لثقتك في صيدليات براءة. بدأنا في تجهيز طلبك، وسيتواصل معك فريق التوصيل لتأكيد الموعد.', 'baraa-pharmacy'); ?>
                            </p>
                        </div>
                    </div>

                    <div class="shrink-0 text-center rounded-xl bg-white/15 px-space-lg py-space-md backdrop-blur-sm">
                        <div class="font-label-sm text-label-sm opacity-85"><?php esc_html_e('رقم الطلب', 'baraa-pharmacy'); ?></div>
                        <div class="font-headline-md text-headline-md font-bold" dir="ltr">#<?php echo esc_html($order_number); ?></div>
                    </div>
                </div>
            </div>

            <!-- ═══ OVERVIEW CARDS ═════════════════════════════════ -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-space-md mb-space-lg">
                <div class="baraa-thankyou-card rounded-xl shadow-sm p-space-md flex items-center gap-space-md">
                    <div class="w-11 h-11 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[22px]" aria-hidden="true">receipt_long</span>
                    </div>
                    <div class="min-w-0 text-right">
                        <div class="font-label-sm text-label-sm text-on-surface-variant"><?php esc_html_e('رقم الطلب', 'baraa-pharmacy'); ?></div>
                        <div class="font-title-md text-title-md font-bold text-on-surface" dir="ltr">#<?php echo esc_html($order_number); ?></div>
                    </div>
                </div>

                <div class="baraa-thankyou-card rounded-xl shadow-sm p-space-md flex items-center gap-space-md">
                    <div class="w-11 h-11 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[22px]" aria-hidden="true">event</span>
                    </div>
                    <div class="min-w-0 text-right">
                        <div class="font-label-sm text-label-sm text-on-surface-variant"><?php esc_html_e('التاريخ', 'baraa-pharmacy'); ?></div>
                        <div class="font-title-md text-title-md font-bold text-on-surface truncate"><?php echo esc_html($order_date); ?></div>
                    </div>
                </div>

                <div class="baraa-thankyou-card rounded-xl shadow-sm p-space-md flex items-center gap-space-md">
                    <div class="w-11 h-11 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[22px]" aria-hidden="true">payments</span>
                    </div>
                    <div class="min-w-0 text-right">
                        <div class="font-label-sm text-label-sm text-on-surface-variant"><?php esc_html_e('الإجمالي', 'baraa-pharmacy'); ?></div>
                        <div class="font-title-md text-title-md font-bold text-on-surface baraa-thankyou-total"><?php echo wp_kses_post($order_total); ?></div>
                    </div>
                </div>

                <?php if ($payment_title) : ?>
                    <div class="baraa-thankyou-card rounded-xl shadow-sm p-space-md flex items-center gap-space-md">
                        <div class="w-11 h-11 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[22px]" aria-hidden="true">credit_card</span>
                        </div>
                        <div class="min-w-0 text-right">
                            <div class="font-label-sm text-label-sm text-on-surface-variant"><?php esc_html_e('طريقة الدفع', 'baraa-pharmacy'); ?></div>
                            <div class="font-title-md text-title-md font-bold text-on-surface truncate"><?php echo wp_kses_post($payment_title); ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ═══ ORDER DETAILS ══════════════════════════════════ -->
            <div class="baraa-thankyou-card rounded-xl shadow-sm overflow-hidden mb-space-lg">
                <div class="p-space-md border-b border-outline-variant/30 flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-primary text-[22px]" aria-hidden="true">shopping_bag</span>
                    <h2 class="font-title-lg text-title-lg font-bold text-on-surface"><?php esc_html_e('تفاصيل الطلب', 'baraa-pharmacy'); ?></h2>
                </div>

                <div class="baraa-thankyou-table-wrap">
                    <table class="baraa-thankyou-table w-full">
                        <thead>
                            <tr>
                                <th class="text-right"><?php esc_html_e('المنتج', 'baraa-pharmacy'); ?></th>
                                <th class="text-center"><?php esc_html_e('الكمية', 'baraa-pharmacy'); ?></th>
                                <th class="text-left"><?php esc_html_e('الإجمالي', 'baraa-pharmacy'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $order_items = $order->get_items();
                            foreach ($order_items as $item_id => $item) :
                                $product = $item->get_product();
                                $thumb   = $product ? $product->get_image('woocommerce_thumbnail', array('class' => 'w-full h-full object-contain')) : '';
                                $link    = $product ? $product->get_permalink() : '';
                                ?>
                                <tr>
                                    <td class="text-right">
                                        <div class="flex items-center gap-space-md">
                                            <?php if ($thumb) : ?>
                                                <div class="baraa-thankyou-thumb w-14 h-14 rounded-lg p-1 shrink-0 flex items-center justify-center overflow-hidden">
                                                    <?php if ($link) : ?>
                                                        <a href="<?php echo esc_url($link); ?>" tabindex="-1" aria-hidden="true"><?php echo wp_kses_post($thumb); ?></a>
                                                    <?php else : ?>
                                                        <?php echo wp_kses_post($thumb); ?>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="min-w-0">
                                                <?php if ($link) : ?>
                                                    <a class="baraa-thankyou-name font-body-md text-body-md font-semibold hover:text-primary transition-colors" href="<?php echo esc_url($link); ?>">
                                                        <?php echo wp_kses_post($item->get_name()); ?>
                                                    </a>
                                                <?php else : ?>
                                                    <span class="baraa-thankyou-name font-body-md text-body-md font-semibold"><?php echo wp_kses_post($item->get_name()); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center font-body-md text-body-md"><?php echo esc_html($item->get_quantity()); ?></td>
                                    <td class="text-left font-body-md text-body-md font-semibold"><?php echo wp_kses_post($order->get_formatted_line_subtotal($item)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th class="text-right" colspan="2"><?php esc_html_e('المجموع الفرعي', 'baraa-pharmacy'); ?></th>
                                <td class="text-left"><?php echo wp_kses_post(wc_price($order->get_subtotal())); ?></td>
                            </tr>

                            <?php if ($order->get_discount_total() > 0) : ?>
                                <tr>
                                    <th class="text-right" colspan="2"><?php esc_html_e('الخصم', 'baraa-pharmacy'); ?></th>
                                    <td class="text-left baraa-thankyou-discount">- <?php echo wp_kses_post(wc_price($order->get_discount_total())); ?></td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($order->get_fees() as $fee) : ?>
                                <tr>
                                    <th class="text-right" colspan="2"><?php echo esc_html($fee->get_name()); ?></th>
                                    <td class="text-left"><?php echo wp_kses_post(wc_price($fee->get_total())); ?></td>
                                </tr>
                            <?php endforeach; ?>

                            <tr>
                                <th class="text-right" colspan="2"><?php esc_html_e('الشحن', 'baraa-pharmacy'); ?></th>
                                <td class="text-left"><?php echo wp_kses_post($shipping_display); ?></td>
                            </tr>

                            <?php if ($order->get_total_tax() > 0) : ?>
                                <tr>
                                    <th class="text-right" colspan="2"><?php esc_html_e('الضريبة', 'baraa-pharmacy'); ?></th>
                                    <td class="text-left"><?php echo wp_kses_post(wc_price($order->get_total_tax())); ?></td>
                                </tr>
                            <?php endif; ?>

                            <tr class="baraa-thankyou-grandtotal">
                                <th class="text-right" colspan="2"><?php esc_html_e('الإجمالي', 'baraa-pharmacy'); ?></th>
                                <td class="text-left"><?php echo wp_kses_post($order->get_formatted_order_total()); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- ═══ DELIVERY + PAYMENT ═════════════════════════════ -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-space-lg mb-space-lg">

                <!-- Delivery -->
                <div class="baraa-thankyou-card rounded-xl shadow-sm p-space-lg text-right">
                    <div class="flex items-center gap-2.5 mb-space-md">
                        <span class="material-symbols-outlined text-primary text-[22px]" aria-hidden="true">location_on</span>
                        <h2 class="font-title-lg text-title-lg font-bold text-on-surface"><?php esc_html_e('عنوان التوصيل', 'baraa-pharmacy'); ?></h2>
                    </div>

                    <?php if ($billing_name) : ?>
                        <div class="font-title-md text-title-md font-bold text-on-surface mb-1"><?php echo esc_html($billing_name); ?></div>
                    <?php endif; ?>

                    <?php if ($address_lines) : ?>
                        <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">
                            <?php echo esc_html(implode(' — ', $address_lines)); ?>
                        </p>
                    <?php endif; ?>

                    <?php if ($billing_phone) : ?>
                        <div class="flex items-center gap-2 mt-space-md font-body-md text-body-md text-on-surface">
                            <span class="material-symbols-outlined text-primary text-[18px]" aria-hidden="true">call</span>
                            <a class="hover:text-primary transition-colors" dir="ltr" href="tel:<?php echo esc_attr($billing_phone); ?>"><?php echo esc_html($billing_phone); ?></a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Payment -->
                <div class="baraa-thankyou-card rounded-xl shadow-sm p-space-lg text-right">
                    <div class="flex items-center gap-2.5 mb-space-md">
                        <span class="material-symbols-outlined text-primary text-[22px]" aria-hidden="true">verified_user</span>
                        <h2 class="font-title-lg text-title-lg font-bold text-on-surface"><?php esc_html_e('تفاصيل الدفع', 'baraa-pharmacy'); ?></h2>
                    </div>

                    <?php if ($payment_title) : ?>
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-primary-fixed text-on-primary-fixed font-label-md text-label-md font-bold">
                            <span class="material-symbols-outlined text-[16px]" aria-hidden="true">credit_card</span>
                            <span><?php echo wp_kses_post($payment_title); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="baraa-thankyou-gateway font-body-md text-body-md text-on-surface-variant mt-space-md leading-relaxed">
                        <?php do_action('woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id()); ?>
                    </div>
                </div>
            </div>

            <!-- ═══ CTA ════════════════════════════════════════════ -->
            <div class="flex flex-col sm:flex-row gap-space-md">
                <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"
                   class="baraa-thankyou-btn-primary flex-1 inline-flex items-center justify-center gap-2 px-space-lg py-3 rounded-lg font-title-md text-title-md font-bold">
                    <span class="material-symbols-outlined text-[20px]" aria-hidden="true">storefront</span>
                    <span><?php esc_html_e('متابعة التسوق', 'baraa-pharmacy'); ?></span>
                </a>
                <a href="https://wa.me/201556859030" target="_blank" rel="noopener noreferrer"
                   class="baraa-thankyou-btn-ghost flex-1 inline-flex items-center justify-center gap-2 px-space-lg py-3 rounded-lg font-title-md text-title-md font-bold">
                    <span class="material-symbols-outlined text-[20px]" aria-hidden="true">chat</span>
                    <span><?php esc_html_e('تواصل مع الصيدلي', 'baraa-pharmacy'); ?></span>
                </a>
            </div>

        <?php endif; ?>

    <?php else : ?>

        <?php wc_get_template('checkout/order-received.php', array('order' => false)); ?>

    <?php endif; ?>

</div>
