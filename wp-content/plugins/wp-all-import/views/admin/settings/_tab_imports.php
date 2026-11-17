<?php
if ( ! defined( 'ABSPATH' ) ) exit; // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
/**
 * Imports tab: how the import engine runs.
 */
?>
<form method="post" action="" class="settings wpai-set__form">
	<?php wp_nonce_field('edit-settings', '_wpnonce_edit-settings') ?>
	<input type="hidden" name="is_settings_submitted" value="1" />

	<div class="wpallimport-content-section wpai-set__panel">

		<?php wpai_settings_group(__('Throughput', 'wp-all-import')); ?>

		<?php
		wpai_settings_number('large_feed_limit', $post['large_feed_limit'], array(
			'label' => __('Chunk size', 'wp-all-import'),
			'unit'  => __('records per chunk', 'wp-all-import'),
			'min'   => 1,
			'max'   => 100000,
			'help'  => __('Large files are split into chunks of this size before importing. Smaller chunks keep long imports from slowing down as they progress.', 'wp-all-import'),
		));
		?>

		<?php wpai_settings_group(__('File parsing', 'wp-all-import')); ?>

		<?php
		wpai_settings_toggle('force_stream_reader', ! empty($post['force_stream_reader']), array(
			'label'    => __('Parser', 'wp-all-import'),
			'checkbox' => __('Use StreamReader instead of XMLReader', 'wp-all-import'),
			'help'     => __('XMLReader is much faster, but a known bug can make it skip records in files with unusual markup. Switch to StreamReader only if records are going missing.', 'wp-all-import'),
		));
		?>

		<?php wpai_settings_group(__('Safety and compatibility', 'wp-all-import')); ?>

		<?php
		wpai_settings_toggle('pingbacks', ! empty($post['pingbacks']), array(
			'label'    => __('Pingbacks', 'wp-all-import'),
			'checkbox' => __('Suppress pingbacks during imports', 'wp-all-import'),
			'help'     => __('Defines the <code>WP_IMPORTING</code> constant while an import runs, which stops WordPress from sending pingbacks for every imported post.', 'wp-all-import'),
		));

		wpai_settings_number('port', $post['port'], array(
			'label'       => __('Custom port', 'wp-all-import'),
			'unit'        => __('port number', 'wp-all-import'),
			'placeholder' => __('none', 'wp-all-import'),
			'min'         => 1,
			'max'         => 65535,
			'help'        => __('Only needed if your site runs on a non-standard port and the import wizard stalls when moving between steps. Leave blank otherwise.', 'wp-all-import'),
		));
		?>

		<?php require(PMXI_Plugin::ROOT_DIR . '/views/admin/settings/_save_actions.php'); ?>
	</div>
</form>
