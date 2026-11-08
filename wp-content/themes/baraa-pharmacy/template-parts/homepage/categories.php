<?php
if (!defined('ABSPATH')) {
    exit;
}

$categories = isset($args['categories']) ? $args['categories'] : array();

/**
 * Return a Material Symbol icon name + Tailwind colour classes for a
 * product category based on its slug. Compatible with PHP 7.2+.
 *
 * @param string $slug Category slug.
 * @return array { icon: string, color: string }
 */
function baraa_category_icon_data($slug)
{
    $map = array(
        'heart'      => array('icon' => 'cardiology',        'color' => 'bg-error-container/40 text-error'),
        'blood'      => array('icon' => 'cardiology',        'color' => 'bg-error-container/40 text-error'),
        'pressure'   => array('icon' => 'cardiology',        'color' => 'bg-error-container/40 text-error'),
        'diabet'     => array('icon' => 'bloodtype',         'color' => 'bg-secondary-fixed/50 text-secondary'),
        'sugar'      => array('icon' => 'bloodtype',         'color' => 'bg-secondary-fixed/50 text-secondary'),
        'vitamin'    => array('icon' => 'shield_with_heart', 'color' => 'bg-primary-fixed/50 text-primary'),
        'supplement' => array('icon' => 'shield_with_heart', 'color' => 'bg-primary-fixed/50 text-primary'),
        'immune'     => array('icon' => 'shield_with_heart', 'color' => 'bg-primary-fixed/50 text-primary'),
        'skin'       => array('icon' => 'spa',               'color' => 'bg-tertiary-fixed/50 text-tertiary'),
        'derma'      => array('icon' => 'spa',               'color' => 'bg-tertiary-fixed/50 text-tertiary'),
        'beauty'     => array('icon' => 'spa',               'color' => 'bg-tertiary-fixed/50 text-tertiary'),
        'baby'       => array('icon' => 'child_care',        'color' => 'bg-surface-container-high text-secondary'),
        'child'      => array('icon' => 'child_care',        'color' => 'bg-surface-container-high text-secondary'),
        'mother'     => array('icon' => 'child_care',        'color' => 'bg-surface-container-high text-secondary'),
        'digest'     => array('icon' => 'ecg_heart',         'color' => 'bg-surface-container text-secondary'),
        'stomach'    => array('icon' => 'ecg_heart',         'color' => 'bg-surface-container text-secondary'),
        'device'     => array('icon' => 'monitor_heart',     'color' => 'bg-surface-container-high text-primary'),
        'medical'    => array('icon' => 'medical_services',  'color' => 'bg-primary-fixed/50 text-primary'),
        'pain'       => array('icon' => 'medication',        'color' => 'bg-primary-fixed/30 text-primary'),
        'antibiotic' => array('icon' => 'pill',              'color' => 'bg-secondary-fixed/50 text-secondary'),
    );

    foreach ($map as $keyword => $data) {
        if (strpos($slug, $keyword) !== false) {
            return $data;
        }
    }

    // Default fallback
    return array('icon' => 'local_pharmacy', 'color' => 'bg-primary-fixed/30 text-primary');
}
?>

<section class="w-full px-margin py-space-lg">
    <div class="space-y-space-lg">

        <!-- Section header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-space-sm">
            <div>
                <span class="font-label-md text-label-md text-primary font-bold">
                    <?php esc_html_e('الأقسام العلاجية المتخصصة', 'baraa-pharmacy'); ?>
                </span>
                <h2 class="font-headline-lg text-headline-lg text-on-surface font-bold mt-1">
                    <?php esc_html_e('تسوق حسب الحالة الصحية', 'baraa-pharmacy'); ?>
                </h2>
                <p class="font-body-md text-body-md text-on-surface-variant">
                    <?php esc_html_e('تصفح أدويتك ومستلزماتك بإشراف صيدلي متخصص لكل تخصص طبي.', 'baraa-pharmacy'); ?>
                </p>
            </div>

            <?php
            $shop_url   = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
            $total_cats = (int) wp_count_terms(array('taxonomy' => 'product_cat', 'hide_empty' => true));
            ?>
            <a
                class="inline-flex items-center gap-1 text-primary hover:text-primary-container font-label-md text-label-md font-bold transition-colors shrink-0"
                href="<?php echo esc_url($shop_url); ?>"
            >
                <span>
                    <?php
                    printf(
                        /* translators: %s: number of categories */
                        esc_html__('عرض كافة الأقسام (%s تصنيف)', 'baraa-pharmacy'),
                        esc_html(number_format_i18n($total_cats))
                    );
                    ?>
                </span>
                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">chevron_left</span>
            </a>
        </div>

        <!-- Categories grid -->
        <?php if (!empty($categories)) : ?>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-gutter">
                <?php foreach ($categories as $category) :
                    if (!($category instanceof WP_Term)) {
                        continue;
                    }
                    $cat_url   = get_term_link($category);
                    if (is_wp_error($cat_url)) {
                        continue;
                    }
                    $icon_data = baraa_category_icon_data($category->slug);
                    $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
                    $has_image = !empty($thumbnail_id) && wp_get_attachment_image_src($thumbnail_id, 'thumbnail');
                ?>
                    <a
                        class="bg-surface-container-lowest p-space-md rounded-xl shadow-sm hover:shadow-md transition-all group flex flex-col items-center text-center space-y-space-sm"
                        href="<?php echo esc_url($cat_url); ?>"
                    >
                        <?php if ($has_image) : ?>
                            <div class="w-16 h-16 rounded-full overflow-hidden flex items-center justify-center group-hover:scale-110 transition-transform bg-surface-container-low">
                                <?php echo wp_get_attachment_image($thumbnail_id, 'thumbnail', false, array('class' => 'w-full h-full object-cover')); ?>
                            </div>
                        <?php else : ?>
                            <div class="w-16 h-16 rounded-full <?php echo esc_attr($icon_data['color']); ?> flex items-center justify-center group-hover:scale-110 transition-transform">
                                <span class="material-symbols-outlined text-[32px]" aria-hidden="true">
                                    <?php echo esc_html($icon_data['icon']); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        <div class="space-y-0.5">
                            <h4 class="font-title-md text-title-md text-on-surface font-bold group-hover:text-primary transition-colors">
                                <?php echo esc_html($category->name); ?>
                            </h4>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

        <?php else : ?>
            <div class="bg-surface-container-low rounded-xl p-space-lg text-center">
                <p class="font-body-md text-body-md text-on-surface-variant">
                    <?php esc_html_e('ستظهر الأقسام هنا بعد إضافتها للمتجر.', 'baraa-pharmacy'); ?>
                </p>
            </div>
        <?php endif; ?>

    </div>
</section>
