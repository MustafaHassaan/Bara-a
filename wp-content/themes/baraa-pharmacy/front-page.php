<?php
if (!defined('ABSPATH')) {
    exit;
}

get_header();

$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');

if (function_exists('wc_get_products')) {
    $deals = wc_get_products(array(
        'status'   => 'publish',
        'limit'    => 4,
        'on_sale'  => true,
        'orderby'  => 'popularity',
        'order'    => 'DESC',
    ));

    $products = wc_get_products(array(
        'status'  => 'publish',
        'limit'   => 4,
        'orderby' => 'popularity',
        'order'   => 'DESC',
    ));

    $categories = get_terms(array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
        'number'     => 6,
        'parent'     => 0,
    ));

    if (is_wp_error($categories)) {
        $categories = array();
    }
} else {
    $deals      = array();
    $products   = array();
    $categories = array();
}
?>

<main class="w-full bg-surface min-h-[calc(100vh-280px)]" id="main">
    <div class="flex flex-col w-full">

        <?php if (function_exists('wc_get_products')) : ?>
            <?php get_template_part('template-parts/homepage/hero',        null, array('shop_url' => $shop_url)); ?>
            <?php get_template_part('template-parts/homepage/services'); ?>
            <?php get_template_part('template-parts/homepage/deals',       null, array('deals' => $deals, 'shop_url' => $shop_url)); ?>
            <?php get_template_part('template-parts/homepage/categories',  null, array('categories' => $categories)); ?>
            <?php get_template_part('template-parts/homepage/products',    null, array('products' => $products, 'shop_url' => $shop_url)); ?>
            <?php get_template_part('template-parts/homepage/pharmacist'); ?>
        <?php else : ?>
            <section class="w-full px-margin py-space-xl">
                <div class="bg-surface-container-low rounded-xl p-space-lg text-center text-on-surface-variant font-body-md text-body-md">
                    <?php esc_html_e('قم بتثبيت WooCommerce وتفعيله لعرض المنتجات والأقسام.', 'baraa-pharmacy'); ?>
                </div>
            </section>
        <?php endif; ?>

    </div>
</main>

<?php get_footer(); ?>
