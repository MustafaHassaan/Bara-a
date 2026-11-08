<?php
if ( ! defined( 'ABSPATH' ) ) exit; // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
/**
 * Support tab: the things you need when something has gone wrong, plus the extension
 * point add-ons hook into.
 */
?>
<div class="wpallimport-content-section wpai-set__panel">

	<?php wpai_settings_group(
		__('Get help', 'wp-all-import'),
		__('Support answers faster when a system report is attached. It saves a round trip asking for your versions and limits.', 'wp-all-import')
	); ?>

	<div class="wpai-set-actions">
		<a class="button button-primary" href="https://www.wpallimport.com/support/?utm_source=import-plugin-free&utm_medium=help&utm_campaign=premium-support" target="_blank"><?php esc_html_e('Contact support', 'wp-all-import'); ?></a>
		<a class="button" href="https://www.wpallimport.com/documentation/?utm_source=import-plugin-free&utm_medium=help&utm_campaign=docs" target="_blank"><?php esc_html_e('Documentation', 'wp-all-import'); ?></a>
	</div>

	<?php wpai_settings_group(__('System report', 'wp-all-import')); ?>

	<?php
	ob_start();
	?>
	<div class="wpai-set-report">
		<textarea id="wpai-set-system-report" class="wpai-set-report__text" rows="14" readonly
			aria-describedby="wpai-set-system-report-help"><?php echo esc_textarea($system_report); ?></textarea>
		<p class="wpai-set-actions">
			<button type="button" class="button wpai-set-copy" data-target="wpai-set-system-report"><span data-copy-label><?php esc_html_e('Copy', 'wp-all-import'); ?></span><span class="screen-reader-text"> <?php esc_html_e('system report', 'wp-all-import'); ?></span></button>
		</p>
	</div>
	<?php
	$control = ob_get_clean();

	wpai_settings_row(array(
		'label'   => __('Environment', 'wp-all-import'),
		'for'     => 'wpai-set-system-report',
		'control' => $control,
		'help'    => __('Versions, server limits, and active plugins. No site URLs or import data. Your theme and plugin list are included, which on a client site may identify the build.', 'wp-all-import'),
		'class'   => 'wpai-set-row--stack',
	));
	?>

	<?php if ($addon_settings): ?>
		<?php wpai_settings_group(
			__('Add-on settings', 'wp-all-import'),
			__('Contributed by the add-ons you have installed.', 'wp-all-import')
		); ?>
		<form method="post" action="" class="settings wpai-set__form wpai-set-addon-form">
			<?php wp_nonce_field('edit-settings', '_wpnonce_edit-settings') ?>
			<input type="hidden" name="is_settings_submitted" value="1" />
			<div class="wpai-set-addon-settings">
				<table class="form-table"><tbody><?php echo $addon_settings; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- third-party markup, already run through bind_contributed_labels(). ?></tbody></table>
			</div>
			<?php require(PMXI_Plugin::ROOT_DIR . '/views/admin/settings/_save_actions.php'); ?>
		</form>
	<?php endif; ?>
</div>
