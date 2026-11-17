<?php
if (!defined('ABSPATH')) {
    exit;
}

$product = isset($args['product']) ? $args['product'] : null;

if (!$product instanceof WC_Product) {
    return;
}

$can_add     = $product->is_type('simple') && $product->is_purchasable() && $product->is_in_stock();
$action_url  = $can_add ? $product->add_to_cart_url() : $product->get_permalink();
$is_on_sale  = $product->is_on_sale();
$in_stock    = $product->is_in_stock();
$categories  = wc_get_product_category_list($product->get_id(), ', ');

// شارة العرض: تظهر فقط للمنتجات الداخلة في حملة شغالة (والروشتة مستثناة تلقائيًا).
$offer_badge  = '';
$offer_color1 = '#ba1a1a';
$offer_color2 = '#93000a';

if (function_exists('baraa_pharmacy_active_offer')) {
    $offer = baraa_pharmacy_active_offer();

    if ($offer && (float) $offer['amount'] > 0 && baraa_pharmacy_offer_covers_product($offer, $product->get_id())) {
        $offer_badge  = $offer['name'] . ' − ' . baraa_pharmacy_offer_amount_label($offer);
        $offer_color1 = !empty($offer['color1']) ? $offer['color1'] : $offer_color1;
        $offer_color2 = !empty($offer['color2']) ? $offer['color2'] : $offer_color2;
    }
}
?>
<article class="bg-surface-container-lowest rounded-xl p-space-md flex flex-col justify-between shadow-sm hover:shadow-md transition-all group">

    <div class="relative space-y-space-sm">

        <!-- Product image -->
        <a
            href="<?php echo esc_url($product->get_permalink()); ?>"
            class="block w-full h-44 rounded-lg bg-surface-container-low overflow-hidden relative"
            tabindex="-1"
            aria-hidden="true"
        >
            <?php echo wp_kses_post($product->get_image('woocommerce_thumbnail', array('class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-300'))); ?>
            <?php if ('' !== $offer_badge) : ?>
                <span
                    class="absolute top-2 right-2 max-w-[85%] truncate px-2 py-0.5 rounded-full font-label-sm text-[11px] text-white font-bold shadow-sm"
                    style="background-image:linear-gradient(90deg,<?php echo esc_attr($offer_color1); ?>,<?php echo esc_attr($offer_color2); ?>)"
                ><?php echo esc_html($offer_badge); ?></span>
            <?php endif; ?>
            <?php if ($is_on_sale) : ?>
                <span class="absolute bottom-2 right-2 px-2 py-0.5 rounded bg-surface-container-lowest/90 backdrop-blur-sm font-label-sm text-[11px] text-error font-bold">
                    <?php esc_html_e('خصم', 'baraa-pharmacy'); ?>
                </span>
            <?php endif; ?>
        </a>

        <!-- Meta -->
        <div class="space-y-1">
            <?php if ($categories) : ?>
                <span class="font-label-sm text-label-sm text-outline"><?php echo wp_kses_post($categories); ?></span>
            <?php endif; ?>
            <h3 class="font-title-md text-title-md text-on-surface font-bold leading-tight group-hover:text-primary transition-colors">
                <a href="<?php echo esc_url($product->get_permalink()); ?>"><?php echo esc_html($product->get_name()); ?></a>
            </h3>
            <?php
            $short_desc = $product->get_short_description();
            if ($short_desc) :
            ?>
                <p class="font-body-sm text-body-sm text-on-surface-variant line-clamp-1"><?php echo wp_kses_post($short_desc); ?></p>
            <?php endif; ?>
        </div>

    </div>

    <!-- Footer: price + CTA -->
    <div class="pt-space-md space-y-space-sm">

        <!-- Stock indicator -->
        <div class="flex items-center justify-between font-label-sm text-label-sm">
            <?php if ($in_stock) : ?>
                <span class="text-primary font-semibold flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-primary" aria-hidden="true"></span>
                    <?php esc_html_e('متوفر في المخزن', 'baraa-pharmacy'); ?>
                </span>
            <?php else : ?>
                <span class="text-error font-semibold flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-error" aria-hidden="true"></span>
                    <?php esc_html_e('غير متوفر حالياً', 'baraa-pharmacy'); ?>
                </span>
            <?php endif; ?>
        </div>

        <!-- Price + add to cart -->
        <div class="flex items-center justify-between pt-1">
            <div class="flex flex-col">
                <?php
                $price_html = $product->get_price_html();
                echo wp_kses_post('<span class="font-headline-md text-headline-md text-[#16A085] font-bold">' . $price_html . '</span>');
                ?>
            </div>
            <a
                href="<?php echo esc_url($action_url); ?>"
                class="px-space-md py-2.5 rounded-lg bg-[#16A085] hover:bg-[#117A65] text-white font-label-md text-label-md font-bold transition-all shadow-sm flex items-center gap-1.5<?php echo $can_add ? ' add_to_cart_button ajax_add_to_cart product_type_simple' : ''; ?>"
                <?php if ($can_add) : ?>
                    rel="nofollow"
                    data-quantity="1"
                    data-product_id="<?php echo esc_attr($product->get_id()); ?>"
                    data-product_sku="<?php echo esc_attr($product->get_sku()); ?>"
                <?php endif; ?>
            >
                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">add_shopping_cart</span>
                <span>
                    <?php echo $can_add
                        ? esc_html__('أضف للسلة', 'baraa-pharmacy')
                        : esc_html__('عرض المنتج', 'baraa-pharmacy');
                    ?>
                </span>
            </a>
        </div>

    </div>
</article>
