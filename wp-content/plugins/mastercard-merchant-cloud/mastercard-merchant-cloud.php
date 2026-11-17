<?php
/**
 * @wordpress-plugin
 * Plugin Name: Mastercard Merchant Cloud
 * Plugin URI:  https://wordpress.org/plugins/mastercard-merchant-cloud
 * Description: Access a flexible, seamless checkout solution supporting over 35 payment methods and multi-currency options to meet global needs around the world, with ongoing expansion into new markets. Enjoy built-in security features and simple integration for a smooth experience.
 * Version:     1.0.1
 * Author:      Mastercard Merchant Cloud
 * Author URI:  https://www.mastercard.com/
 * Requires Plugins: woocommerce
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: mastercard-merchant-cloud
 * Domain Path: /i18n/languages
 * Requires at least: 6.0
 * Tested up to: 7.0
 * WC requires at least: 9.0
 * WC tested up to: 10.3.6
 * Requires PHP: 8.0
 */

namespace MastercardMerchantCloud;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define constants.
const VERSION     = '1.0.1';
const PLUGIN_FILE = __FILE__;

/**
 * Return error data
 *
 * @return array
 */
function get_error() {
	return array(
		/* translators: 1: composer command. 2: plugin directory */
		'message'   => esc_html__( 'Your installation of Mastercard Merchant Cloud plugin is incomplete. Please run %1$s within the %2$s directory.', 'mastercard-merchant-cloud' ),
		'command'   => 'composer install',
		'directory' => esc_html( str_replace( ABSPATH, '', __DIR__ ) ),
	);
}

/**
 * Main instance of the plugin.
 */
function mastercard_merchant_cloud() {
	static $plugin;

	if ( ! isset( $plugin ) ) {
		$autoloader = __DIR__ . '/vendor/autoload.php';
		if ( file_exists( $autoloader ) ) {
			require $autoloader;
		}

		$plugin = new Main();
	}

	return $plugin;
}

/**
 * Initialize the plugin.
 */
function mastercard_merchant_cloud_init() {
	if ( ! is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$composer_error = get_error();
			error_log( sprintf( $composer_error['message'], '`' . $composer_error['command'] . '`', '`' . $composer_error['directory'] . '`' ) ); // phpcs:ignore
		}

		add_action( 'admin_notices', __NAMESPACE__ . '\\mastercard_merchant_cloud_missing_autoloader_notice' );
		return;
	}

	mastercard_merchant_cloud();
}

/**
 * Display an admin notice if the autoloader is missing.
 */
function mastercard_merchant_cloud_missing_autoloader_notice() {
	$composer_error = get_error();
	?>
	<div class="notice notice-error">
		<p>
			<?php printf( $composer_error['message'], '<code>' . $composer_error['command'] . '</code>', '<code>' . $composer_error['directory'] . '</code>' ); // phpcs:ignore ?>
		</p>
	</div>
	<?php
}

// Initialize the plugin.
mastercard_merchant_cloud_init();
