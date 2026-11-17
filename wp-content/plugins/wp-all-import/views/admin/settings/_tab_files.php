<?php
if ( ! defined( 'ABSPATH' ) ) exit; // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
/**
 * Files tab: where import files and logs live, and how long they stay.
 */
$cleanup_url = add_query_arg(array('action' => 'cleanup', '_wpnonce' => wp_create_nonce('_wpnonce-cleanup_logs')), $this->baseUrl);
?>
<form method="post" action="" class="settings wpai-set__form">
	<?php wp_nonce_field('edit-settings', '_wpnonce_edit-settings') ?>
	<input type="hidden" name="is_settings_submitted" value="1" />

	<div class="wpallimport-content-section wpai-set__panel">

		<?php wpai_settings_group(__('Storage', 'wp-all-import')); ?>

		<?php
		wpai_settings_toggle('secure', ! empty($post['secure']), array(
			'label'    => __('Secure mode', 'wp-all-import'),
			'checkbox' => __('Use randomized folder names', 'wp-all-import'),
			'help'     => sprintf(
				/* translators: %s: uploads directory path */
				__('Import files, chunks, logs, and temporary files go into a randomly named folder inside <code>%s</code>, so they can\'t be found by guessing the URL.', 'wp-all-import'),
				esc_html(preg_replace('%.*wp-content%', 'wp-content', wp_normalize_path($uploads_basedir)))
			),
		));
		?>

		<?php wpai_settings_group(__('Retention', 'wp-all-import')); ?>

		<?php
		wpai_settings_number('log_storage', $post['log_storage'], array(
			'label' => __('Logs kept', 'wp-all-import'),
			'unit'  => __('per import', 'wp-all-import'),
			'max'   => 1000,
			'help'  => __('How many run logs to keep for each import. Set to 0 to never store logs.', 'wp-all-import'),
		));

		wpai_settings_number('history_file_count', $post['history_file_count'], array(
			'label' => __('Import files kept', 'wp-all-import'),
			'unit'  => __('files', 'wp-all-import'),
			'max'   => 100000,
			'help'  => __('How many past import files to keep on disk. The oldest are deleted once this many are stored.', 'wp-all-import'),
		));

		wpai_settings_number('history_file_age', $post['history_file_age'], array(
			'label' => __('Delete files older than', 'wp-all-import'),
			'unit'  => __('days', 'wp-all-import'),
			'max'   => 3650,
			'help'  => __('Past import files older than this are removed. Applies on top of the file count above, whichever comes first.', 'wp-all-import'),
		));
		?>

		<?php wpai_settings_group(__('Maintenance', 'wp-all-import')); ?>

		<?php
		ob_start();
		?>
		<a class="button wpai-set-danger wpallimport-clean-up-tmp-files"
			href="<?php echo esc_url($cleanup_url); ?>"
			data-wpai-confirm="<?php esc_attr_e('Delete all leftover temporary files and cached chunks? Imports that are currently running may fail. Your imports, logs, and settings are not affected.', 'wp-all-import'); ?>"
			data-wpai-confirm-label="<?php esc_attr_e('Delete temp files', 'wp-all-import'); ?>"><?php esc_html_e('Clean up temp files', 'wp-all-import'); ?></a>
		<?php
		$control = ob_get_clean();

		wpai_settings_row(array(
			'label'   => __('Temporary files', 'wp-all-import'),
			'for'     => '',
			'control' => $control,
			'help'    => __('Removes chunk files and caches left behind by imports that were stopped or crashed. Safe to run at any time when no import is in progress.', 'wp-all-import'),
		));
		?>

		<?php require(PMXI_Plugin::ROOT_DIR . '/views/admin/settings/_save_actions.php'); ?>
	</div>
</form>
