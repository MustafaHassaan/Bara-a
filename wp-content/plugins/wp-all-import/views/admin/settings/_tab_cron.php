<?php
if ( ! defined( 'ABSPATH' ) ) exit; // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
/**
 * Cron tab: the free edition runs no cron or scheduled imports, so this points to Pro.
 */
?>
<div class="wpallimport-content-section wpai-set__panel">

	<?php wpai_settings_group(
		__('Scheduled imports', 'wp-all-import'),
		esc_html__('Running imports on a schedule, with cron jobs or Automatic Scheduling, is part of the Pro edition. The secret key and run limits for those imports are set here in Pro.', 'wp-all-import')
	); ?>

	<div class="wpallimport-free-edition-notice txt_center">
		<a href="https://www.wpallimport.com/checkout/?edd_action=add_to_cart&download_id=5839966&edd_options%5Bprice_id%5D=1&discount=welcome-upgrade-99&utm_source=import-plugin-free&utm_medium=upgrade-notice&utm_campaign=settings-cron" target="_blank" class="upgrade_link"><?php esc_html_e('Upgrade to the Pro edition of WP All Import to Schedule Imports', 'wp-all-import'); ?></a>
		<p><?php esc_html_e('If you already own it, remove the free edition and install the Pro edition.', 'wp-all-import'); ?></p>
	</div>

</div>
