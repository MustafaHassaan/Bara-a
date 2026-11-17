<?php
if ( ! defined( 'ABSPATH' ) ) exit; // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
/**
 * Settings screen shell: header, tab nav, active panel.
 */
require_once(PMXI_Plugin::ROOT_DIR . '/views/admin/settings/_fields.php');

$tab_files = array(
	'imports'   => '_tab_imports.php',
	'cron'      => '_tab_cron.php',
	'files'     => '_tab_files.php',
	'templates' => '_tab_templates.php',
	'functions' => '_tab_functions.php',
	'support'   => '_tab_support.php',
);
?>

<?php
/*
 * WordPress relocates every .notice inside .wrap to just after the first h1/h2 on the
 * page. This screen's own title is an h3, so without a marker the admin notices land
 * wherever the first heading happens to be. wp-header-end is core's marker for "notices
 * belong here" and pins them regardless of what follows.
 */
?>
<hr class="wp-header-end" />

<div class="wpai-set">

	<div class="wpallimport-header wpai-set__header">
		<div class="wpallimport-logo"></div>
		<div class="wpallimport-title">
			<h3><?php esc_html_e('Settings', 'wp-all-import'); ?></h3>
		</div>
		<?php echo wp_kses_post(apply_filters('wpallimport_links_block', '')); ?>
	</div>

	<nav class="nav-tab-wrapper wpai-set__tabs" aria-label="<?php esc_attr_e('Settings sections', 'wp-all-import'); ?>">
		<?php foreach ($tabs as $slug => $label): ?>
			<a href="<?php echo esc_url($tab_url[$slug]); ?>"
			   class="nav-tab<?php echo $slug === $current_tab ? ' nav-tab-active' : ''; ?>"
			   <?php if ($slug === $current_tab): ?>aria-current="page"<?php endif; ?>><?php echo esc_html($label); ?></a>
		<?php endforeach; ?>
	</nav>

	<?php if ($this->errors->get_error_codes()): ?>
		<div class="wpai-set__errors" role="alert" tabindex="-1" data-wpai-errors><?php $this->error(); ?></div>
	<?php endif; ?>

	<?php
	$active_tab_file = isset($tab_files[$current_tab]) ? $tab_files[$current_tab] : $tab_files['imports'];
	require(PMXI_Plugin::ROOT_DIR . '/views/admin/settings/' . $active_tab_file);
	?>

</div>

<div class="wpallimport-overlay"></div>
<div class="wpallimport-super-overlay"></div>

<div class="wpallimport-display-columns wpallimport-margin-top-forty">
	<?php echo wp_kses_post( apply_filters('wpallimport_footer', '') ); ?>
</div>
