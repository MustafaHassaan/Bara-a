<?php
if ( ! defined( 'ABSPATH' ) ) exit; // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
/**
 * Import/export of saved import templates.
 */
$templates = new PMXI_Template_List();
$templates->getBy()->convertRecords();
?>
<form method="post" action="" class="settings wpai-set__form" enctype="multipart/form-data">
	<?php wp_nonce_field('delete-templates', '_wpnonce_delete-templates') ?>
	<input type="hidden" name="is_templates_submitted" value="1" />

	<div class="wpallimport-content-section wpai-set__panel">

		<?php wpai_settings_group(
			__('Saved templates', 'wp-all-import'),
			__('A template is a saved field mapping from a previous import. Export one to reuse a configuration on another site, or to keep a copy before you change an import.', 'wp-all-import')
		); ?>

		<?php if ($templates->total()): ?>
			<fieldset class="wpai-set-templates">
				<legend class="screen-reader-text"><?php esc_html_e('Saved templates', 'wp-all-import'); ?></legend>
				<ul class="wpai-set-templates__list">
					<?php foreach ($templates as $t): ?>
						<li>
							<label class="wpai-set-check" for="template-<?php echo esc_attr($t->id); ?>">
								<input id="template-<?php echo esc_attr($t->id); ?>" type="checkbox" name="templates[]" value="<?php echo esc_attr($t->id); ?>" />
								<span><?php echo esc_html($t->name); ?></span>
							</label>
						</li>
					<?php endforeach ?>
				</ul>
			</fieldset>
			<p class="wpai-set-actions">
				<button type="submit" class="button button-primary" name="export_templates" value="1"><?php esc_html_e('Export selected', 'wp-all-import') ?></button>
				<button type="submit" class="button wpai-set-danger" name="delete_templates" value="1"
					data-wpai-confirm="<?php esc_attr_e('Delete the selected templates? This cannot be undone. Imports already created from them are not affected.', 'wp-all-import'); ?>"
					data-wpai-confirm-label="<?php esc_attr_e('Delete templates', 'wp-all-import'); ?>"><?php esc_html_e('Delete selected', 'wp-all-import') ?></button>
			</p>
		<?php else: ?>
			<div class="wpai-set-empty">
				<p class="wpai-set-empty__title"><?php esc_html_e('No saved templates yet', 'wp-all-import') ?></p>
				<p class="wpai-set-empty__body"><?php esc_html_e('When you tick “Save settings as a template” while setting up an import, it appears here, ready to export to another site.', 'wp-all-import') ?></p>
			</div>
		<?php endif ?>

		<?php wpai_settings_group(__('Import from a file', 'wp-all-import')); ?>

		<?php
		ob_start();
		?>
		<span class="wpai-set-inputgroup wpai-set-inputgroup--file">
			<input type="file" id="wpai-set-template-file" name="template_file" accept=".txt"
				aria-describedby="wpai-set-template-file-help" />
			<button type="submit" class="button" name="import_templates" value="1"><?php esc_html_e('Import templates', 'wp-all-import') ?></button>
		</span>
		<?php
		$control = ob_get_clean();

		wpai_settings_row(array(
			'label'   => __('Template file', 'wp-all-import'),
			'for'     => 'wpai-set-template-file',
			'control' => $control,
			'help'    => __('Accepts a .txt file exported from WP All Import. Files exported from WP All Export won\'t work here.', 'wp-all-import'),
			'class'   => 'wpai-set-row--stack',
		));
		?>
	</div>
</form>
