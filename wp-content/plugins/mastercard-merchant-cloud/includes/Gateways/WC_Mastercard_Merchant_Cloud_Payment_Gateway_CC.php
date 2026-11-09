<?php
/**
 * Mastercard Merchant Cloud CC Payment Gateway class.
 *
 * @class       WC_Mastercard_Merchant_Cloud_Payment_Gateway_CC
 * @version     1.0.0
 * @package     MastercardMerchantCloud/Gateway/
 */

namespace MastercardMerchantCloud\Gateways;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use GatewayPaymentCore\Gateways\WC_Abstract_Payment_Gateway_CC;
use MastercardMerchantCloud\Main;
use function MastercardMerchantCloud\mastercard_merchant_cloud;

/**
 * Show the payment form for Payment Gateway.
 */
final class WC_Mastercard_Merchant_Cloud_Payment_Gateway_CC extends WC_Abstract_Payment_Gateway_CC {

	/**
	 * Gateway ID.
	 */
	const ID = Main::GATEWAY_ID;

	/**
	 * Initialize Payment Gateway.
	 */
	public function __construct() {
		// Load the plugin instance.
		$this->core_plugin = mastercard_merchant_cloud();

		// Load the gateway settings.
		$this->id                 = self::ID;
		$this->method_title       = __( 'Mastercard Merchant Cloud', 'mastercard-merchant-cloud' );
		$this->method_description = __( 'Accept secure payments on your WooCommerce store.', 'mastercard-merchant-cloud' );
		$this->icon               = $this->core_plugin->payment_core()->utils()->plugin_url() . '/assets/images/logo.png';

		$this->build();
	}
}
