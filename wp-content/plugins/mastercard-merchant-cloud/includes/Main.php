<?php
/**
 * Main class.
 *
 * @package  MastercardMerchantCloud
 * @version  1.0.0
 */

namespace MastercardMerchantCloud;

use GatewayPaymentCore\CorePlugin;
use MastercardMerchantCloud\Gateways\WC_Mastercard_Merchant_Cloud_Payment_Gateway_CC;

/**
 * Base Plugin class holding generic functionality.
 */
final class Main extends CorePlugin {

	const GATEWAY_ID = 'mastercard_merchant_cloud';

	const PARTNER_SOLUTION_ID = 'SAUCAL';

	/**
	 * Initialize the plugin.
	 */
	public function init() {
		$this->plugin_id                 = self::GATEWAY_ID;
		$this->partner_solution_id       = self::PARTNER_SOLUTION_ID;
		$this->plugin_file               = PLUGIN_FILE;
		$this->merchant_registration_url = 'https://woocommerce.com/document/mastercard-merchant-cloud/#merchant-account-registration';

		$this->registered_gateways = array(
			self::GATEWAY_ID => WC_Mastercard_Merchant_Cloud_Payment_Gateway_CC::class,
		);
	}

	/**
	 * Get the merchant registration URL.
	 *
	 * @return string
	 */
	public function merchant_registration_message() {
		$message = __( 'You must have a merchant account with a compatible payment service provider (PSP) before using this plugin.', 'mastercard-merchant-cloud' );

		$message .= ' ' . sprintf(
			/* translators: %s: Merchant registration URL */
			__( 'Don\'t have an account? %1$sFollow the instructions here%2$s', 'mastercard-merchant-cloud' ),
			'<a href="' . esc_url( $this->merchant_registration_url ) . '" target="_blank">',
			'</a>'
		);

		return $message;
	}


	/**
	 * Get the plugin's title.
	 *
	 * @return string
	 */
	public function get_plugin_title() {
		return esc_html__( 'Mastercard Merchant Cloud', 'mastercard-merchant-cloud' );
	}
}
