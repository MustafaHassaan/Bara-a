<?php
if ( ! defined( 'ABSPATH' ) ) exit; // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
/**
 * Functions tab: the shared functions file used by import field mappings.
 *
 * The editor is read-only in the free edition: the file is still executed during an import
 * if it exists, but saving it from here is a Pro feature, so the textarea starts from an
 * empty stub rather than the file on disk.
 */
$relative_path = preg_replace('%.*wp-content%', 'wp-content', wp_normalize_path($functions_file));
?>
<div class="wpallimport-content-section wpai-set__panel wpai-set__panel--functions">

	<?php wpai_settings_group(
		__('Functions file', 'wp-all-import'),
		sprintf(
			/* translators: %s: path to the functions file */
			__('PHP functions you define here can be called from any field mapping in any import, using <code>[my_function({field})]</code>. Saved to <code>%s</code>.', 'wp-all-import'),
			esc_html($relative_path)
		)
	); ?>

	<div class="wpallimport-free-edition-notice" style="text-align:center; margin-top:0; margin-bottom: 24px;">
		<a href="https://www.wpallimport.com/checkout/?edd_action=add_to_cart&download_id=5839966&edd_options%5Bprice_id%5D=1&discount=welcome-upgrade-99&utm_source=import-plugin-free&utm_medium=upgrade-notice&utm_campaign=function-editor" target="_blank" class="upgrade_link"><?php esc_html_e('Upgrade to the Pro edition of WP All Import to use the Function Editor.', 'wp-all-import'); ?></a>
		<p><?php esc_html_e('If you already own it, remove the free edition and install the Pro edition.', 'wp-all-import'); ?></p>
	</div>

	<div id="functions_editor_container">
		<?php // Visible, not screen-reader-only: Tab indents inside the editor, so a sighted
		      // keyboard user is trapped by exactly the behaviour this sentence explains. ?>
		<p id="wpai-cm-escape-help" class="wpai-cm-escape-help"><?php esc_html_e('Press Escape, then Tab, to move focus out of the editor.', 'wp-all-import'); ?></p>
		<textarea id="wp_all_import_code" name="wp_all_import_code" aria-label="<?php esc_attr_e('Functions file contents', 'wp-all-import'); ?>" aria-describedby="wpai-cm-escape-help"><?php echo "<?php\n\n?>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- literal stub, no user input. ?></textarea>
	</div>

	<div id="wpai_function_editor_buttons" class="input" style="margin-top: 10px;">
		<div class="input" style="display:inline-block; margin-right: 20px;">
			<input type="button" class="button-primary wp_all_import_save_functions" disabled="disabled" value="<?php esc_attr_e('Save Functions', 'wp-all-import'); ?>"/>
			<a href="#help" class="wpallimport-help" title="<?php printf(
				/* translators: %s: path to functions file */
				esc_attr__('Add functions here for use during your import. You can access this file at %s', 'wp-all-import'),
				esc_attr($relative_path)
			); ?>" style="top: 0;">?</a>
			<div class="wp_all_import_functions_preloader"></div>
		</div>
		<div class="input wp_all_import_saving_status" style="display:inline-block;"></div>
	</div>
</div>
