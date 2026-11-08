<?php
if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<main class="baraa-single-product" id="main">
    <div class="baraa-single-product__container">
        <nav class="baraa-single-product__breadcrumbs" aria-label="<?php esc_attr_e('Breadcrumb', 'baraa-pharmacy'); ?>">
            <?php woocommerce_breadcrumb(array('delimiter' => '<span aria-hidden="true">/</span>')); ?>
        </nav>

        <?php while (have_posts()) : ?>
            <?php
            the_post();
            wc_get_template_part('content', 'single-product');
            ?>
        <?php endwhile; ?>
    </div>
</main>
<?php
get_footer();
