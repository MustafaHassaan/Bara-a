<?php
/**
 * Template Name: العروض
 * Deals page template
 */
if (!defined('ABSPATH')) {
    exit;
}

get_header();

$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');

if (function_exists('wc_get_products')) {
    $products = wc_get_products(array(
        'status'  => 'publish',
        'limit'   => -1,
        'orderby' => 'popularity',
        'order'   => 'DESC',
        'on_sale' => true,
    ));
} else {
    $products = array();
}
?>
<main class="w-full bg-surface min-h-[calc(100vh-280px)]" id="main">
    <div class="flex flex-col w-full">

        <!-- Hero section -->
        <section class="w-full px-margin py-space-xl bg-gradient-to-r from-[#16A085] to-[#117A65] text-white">
            <div class="max-w-4xl mx-auto text-center">
                <h1 class="font-headline-2xl text-headline-2xl font-bold mb-4"><?php esc_html_e('العروض الخاصة', 'baraa-pharmacy'); ?></h1>
                <p class="font-body-lg text-body-lg text-white/90 max-w-2xl mx-auto">
                    <?php esc_html_e('استمتع بأفضل العروض والخصومات على جميع منتجاتنا المعتمدة. العروض محدودة، لا تفوت الفرصة!', 'baraa-pharmacy'); ?>
                </p>
            </div>
        </section>

        <!-- Products grid -->
        <?php if (!empty($products)) : ?>
            <section class="w-full px-margin py-space-xl">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-gutter">
                    <?php
                    foreach ($products as $product) :
                        if (!($product instanceof WC_Product) || !$product->is_visible()) {
                            continue;
                        }

                        $p_id       = $product->get_id();
                        $p_name     = $product->get_name();
                        $p_price    = $product->get_price_html();
                        $p_link     = $product->get_permalink();
                        $p_img      = $product->get_image('woocommerce_thumbnail', array('class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-300'));
                        $p_short    = $product->get_short_description();
                        $p_on_sale  = $product->is_on_sale();
                        $p_in_stock = $product->is_in_stock();
                        $can_add    = $product->is_type('simple') && $product->is_purchasable() && $p_in_stock;
                        $add_url    = $can_add ? $product->add_to_cart_url() : $p_link;
                        ?>
                        <div class="group bg-surface-container-lowest rounded-xl shadow-sm hover:shadow-md transition-all flex flex-col justify-between overflow-hidden">
                            <div class="p-space-md space-y-space-sm">
                                <!-- Image -->
                                <div class="relative w-full aspect-square rounded-lg overflow-hidden bg-surface-container-low flex items-center justify-center">
                                    <a href="<?php echo esc_url($p_link); ?>" tabindex="-1" aria-hidden="true">
                                        <?php echo wp_kses_post($p_img); ?>
                                    </a>
                                    <?php if ($p_on_sale) : ?>
                                        <span class="absolute top-2 right-2 px-2 py-0.5 rounded-full bg-red-600 text-white font-label-sm text-label-sm font-bold flex items-center gap-1 shadow-sm">
                                            <span class="material-symbols-outlined text-[12px]" aria-hidden="true">local_offer</span>
                                            <?php esc_html_e('عرض', 'baraa-pharmacy'); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Product info -->
                                <div>
                                    <a href="<?php echo esc_url($p_link); ?>" class="block">
                                        <h3 class="font-title-md text-title-md font-bold text-on-surface hover:text-primary transition-colors line-clamp-2">
                                            <?php echo esc_html($p_name); ?>
                                        </h3>
                                    </a>
                                    <div class="mt-2">
                                        <?php echo wp_kses_post($p_price); ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Add to cart -->
                            <div class="p-space-md pt-0">
                                <a
                                    href="<?php echo esc_url($add_url); ?>"
                                    class="w-full py-2.5 rounded-lg bg-[#16A085] hover:bg-[#117A65] text-white font-label-md text-label-md font-bold flex items-center justify-center gap-1.5 shadow-sm transition-colors"
                                    aria-label="<?php echo esc_attr(sprintf(__('أضف %s للسلة', 'baraa-pharmacy'), $p_name)); ?>"
                                >
                                    <span class="material-symbols-outlined text-[18px]" aria-hidden="true">add_shopping_cart</span>
                                    <span><?php esc_html_e('أضف للسلة', 'baraa-pharmacy'); ?></span>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php else : ?>
            <section class="w-full px-margin py-space-xl">
                <div class="bg-surface-container-lowest rounded-xl p-space-xl text-center">
                    <span class="material-symbols-outlined text-[64px] text-outline mb-4" aria-hidden="true">local_offer</span>
                    <h2 class="font-title-lg text-title-lg font-bold text-on-surface mb-2">
                        <?php esc_html_e('لا توجد عروض حالياً', 'baraa-pharmacy'); ?>
                    </h2>
                    <p class="font-body-md text-body-md text-on-surface-variant mb-space-md">
                        <?php esc_html_e('تابعنا على وسائل التواصل الاجتماعي لتعرف أحدث العروض والخصومات.', 'baraa-pharmacy'); ?>
                    </p>
                    <a href="<?php echo esc_url($shop_url); ?>" class="inline-flex items-center gap-1.5 px-space-md py-2 rounded-lg bg-[#16A085] hover:bg-[#117A65] text-white font-label-md text-label-md font-bold transition-colors">
                        <span class="material-symbols-outlined text-[18px]" aria-hidden="true">shopping_bag</span>
                        <span><?php esc_html_e('تصفح المنتجات', 'baraa-pharmacy'); ?></span>
                    </a>
                </div>
            </section>
        <?php endif; ?>

    </div>
</main>

<?php get_footer(); ?>
