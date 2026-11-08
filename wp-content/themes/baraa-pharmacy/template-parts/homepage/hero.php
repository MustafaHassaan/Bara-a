<?php
if (!defined('ABSPATH')) {
    exit;
}

$shop_url = isset($args['shop_url']) ? $args['shop_url'] : home_url('/');
?>

<!-- ── Hero Section ──────────────────────────────────────────── -->
<section class="relative w-full overflow-hidden bg-gradient-to-b from-surface-container-low/60 via-surface to-surface pb-space-xl">

    <!-- Ambient glow blobs -->
    <div class="absolute -top-24 right-1/4 w-96 h-96 bg-primary-fixed/25 rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>
    <div class="absolute top-1/3 left-10 w-80 h-80 bg-secondary-fixed/20 rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>

    <div class="w-full px-margin pt-space-lg">
        <div class="grid grid-cols-1 gap-gutter items-stretch">

            <!-- ── Left copy + search ──────────────────────────── -->
            <div class="flex flex-col justify-between py-space-sm space-y-space-lg">

                <div class="space-y-space-md">
                    <!-- Regulated badge -->
                    <div class="inline-flex items-center gap-space-xs px-3 py-1 rounded-full bg-surface-container text-primary font-label-md text-label-md">
                        <span class="material-symbols-outlined text-[18px]" style="font-variation-settings:'FILL' 1">verified</span>
                        <span><?php esc_html_e('الصيدلية الإلكترونية الأولى المعتمدة من هيئة الدواء المصرية', 'baraa-pharmacy'); ?></span>
                    </div>

                    <!-- Headline -->
                    <h1 class="text-display font-display text-on-surface leading-tight">
                        <?php esc_html_e('صيدليتك الذكية في مصر..', 'baraa-pharmacy'); ?>
                        <span class="text-primary block mt-1"><?php esc_html_e('دواء أصلي معتمد، واستشارة صيدلية موثوقة', 'baraa-pharmacy'); ?></span>
                        <?php esc_html_e('حتى باب بيتك خلال دقائق', 'baraa-pharmacy'); ?>
                    </h1>

                    <p class="font-body-lg text-body-lg text-on-surface-variant max-w-2xl">
                        <?php esc_html_e('نوفر جميع الأدوية المعتمدة محلياً ومستوردة، وحفظ حراري آمن مع سلسلة تبريد معتمدة لضمان أمان عائلتك في القاهرة والجيزة والمحافظات.', 'baraa-pharmacy'); ?>
                    </p>
                </div>

                <!-- Hero search box -->
                <div class="bg-surface-container-lowest p-space-md rounded-xl shadow-md space-y-space-sm">
                    <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" class="flex flex-col sm:flex-row items-center gap-space-sm">
                        <input type="hidden" name="post_type" value="product" />
                        <div class="relative flex-1 w-full flex items-center bg-surface-container-low rounded-lg px-space-md py-2.5">
                            <span class="material-symbols-outlined text-outline text-[22px] ml-2" aria-hidden="true">search</span>
                            <label class="sr-only" for="baraa-hero-search"><?php esc_html_e('ابحث عن منتج', 'baraa-pharmacy'); ?></label>
                            <input
                                id="baraa-hero-search"
                                class="w-full bg-transparent text-on-surface placeholder:text-outline font-body-md text-body-md focus:outline-none"
                                type="search"
                                name="s"
                                placeholder="<?php esc_attr_e('ابحث باسم الدواء، المادة الفعالة، أو العَرَض...', 'baraa-pharmacy'); ?>"
                            />
                            <span class="font-label-sm text-label-sm px-2 py-0.5 rounded bg-surface-container text-on-surface-variant mr-2"><?php esc_html_e('دقة 100%', 'baraa-pharmacy'); ?></span>
                        </div>
                        <button
                            type="submit"
                            class="w-full sm:w-auto px-space-lg py-3 rounded-lg bg-primary text-on-primary font-label-md text-label-md hover:bg-primary-container transition-all flex items-center justify-center gap-2 shadow-sm"
                        >
                            <span><?php esc_html_e('تأكيد البحث', 'baraa-pharmacy'); ?></span>
                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">arrow_back</span>
                        </button>
                    </form>

                    <!-- Trending pills -->
                    <?php
                    $trending = get_terms(array(
                        'taxonomy'   => 'product_cat',
                        'hide_empty' => true,
                        'number'     => 5,
                        'parent'     => 0,
                        'orderby'    => 'count',
                        'order'      => 'DESC',
                    ));
                    if (!is_wp_error($trending) && $trending) :
                    ?>
                    <div class="flex items-center gap-space-xs flex-wrap pt-1">
                        <span class="font-label-sm text-label-sm text-outline flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]" aria-hidden="true">trending_up</span>
                            <?php esc_html_e('الأكثر طلباً:', 'baraa-pharmacy'); ?>
                        </span>
                        <?php foreach ($trending as $term) : ?>
                            <a
                                href="<?php echo esc_url(get_term_link($term)); ?>"
                                class="px-2.5 py-1 rounded-full bg-surface-container hover:bg-primary hover:text-on-primary transition-colors text-on-surface-variant font-label-sm text-label-sm"
                            ><?php echo esc_html($term->name); ?></a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Delivery guarantees strip -->
                <div class="flex flex-wrap items-center gap-space-md text-on-surface-variant font-label-md text-label-md">
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-primary text-[20px]" aria-hidden="true">bolt</span>
                        <span><?php esc_html_e('توصيل قياسي 15-45 دقيقة', 'baraa-pharmacy'); ?></span>
                    </div>
                    <span class="opacity-30" aria-hidden="true">•</span>
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-secondary text-[20px]" aria-hidden="true">ac_unit</span>
                        <span><?php esc_html_e('نقل بارد مخصص للأنسولين واللقاحات', 'baraa-pharmacy'); ?></span>
                    </div>
                    <span class="opacity-30 hidden sm:inline" aria-hidden="true">•</span>
                    <div class="hidden sm:flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-tertiary text-[20px]" aria-hidden="true">payments</span>
                        <span><?php esc_html_e('دفع عند الاستلام أو فيزا وميزة', 'baraa-pharmacy'); ?></span>
                    </div>
                </div>

            </div>

        </div>
    </div>
</section>
