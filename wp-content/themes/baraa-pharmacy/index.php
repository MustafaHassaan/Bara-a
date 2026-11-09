<?php
get_header();
?>
<main class="baraa-section" id="main">
    <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post(); ?>
            <article <?php post_class(); ?>>
                <h1><?php the_title(); ?></h1>
                <div><?php the_content(); ?></div>
            </article>
        <?php endwhile; ?>
    <?php else : ?>
        <p class="baraa-empty"><?php esc_html_e('لا يوجد محتوى لعرضه حتى الآن.', 'baraa-pharmacy'); ?></p>
    <?php endif; ?>
</main>
<?php
get_footer();
