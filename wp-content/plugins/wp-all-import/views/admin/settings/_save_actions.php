<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/* Save row for a settings form, inline at the end of the panel like every other action on this screen. */
?>
<p class="wpai-set-actions wpai-set-actions--save">
	<button type="submit" class="button button-primary" data-wpai-save><?php esc_html_e('Save changes', 'wp-all-import'); ?></button>
</p>
