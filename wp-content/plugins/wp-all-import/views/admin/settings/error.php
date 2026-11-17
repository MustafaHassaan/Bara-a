<?php
if ( ! defined( 'ABSPATH' ) ) exit; // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
/**
 * Settings-screen validation errors.
 *
 * `inline` is load-bearing, not decoration. WordPress moves every un-inlined `div.error`
 * inside `.wrap` to just after `.wp-header-end`, which lifted these out of the
 * `role="alert"` region the screen focuses on submit, leaving the focus target empty and
 * the messages stranded above the page header.
 */
?>
<?php foreach ($errors as $msg): ?>
	<div class="error inline"><p><?php echo wp_kses_post($msg); ?></p></div>
<?php endforeach ?>
