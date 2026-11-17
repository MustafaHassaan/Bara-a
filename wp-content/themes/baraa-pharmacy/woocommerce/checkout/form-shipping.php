<?php
/**
 * Checkout shipping information form — Baraa Pharmacy
 *
 * Copy of WooCommerce's checkout/form-shipping.php reduced to the single
 * «الشحن إلى عنوان مختلف؟» checkbox:
 *  - the «معلومات إضافية» (order notes) block is not wanted on checkout;
 *  - the shipping name/last-name/phone fields are never rendered, because the
 *    delivery details are collected by the pharmacy's own RouteMile card.
 * Ticking the box empties that card's address fields (see storefront.js).
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 11.2.0
 * @global WC_Checkout $checkout
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="woocommerce-shipping-fields">
	<?php if ( true === WC()->cart->needs_shipping_address() ) : ?>

		<h3 id="ship-to-different-address">
			<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
				<input id="baraa-ship-to-different-address-checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" type="checkbox" name="baraa_ship_to_different_address" value="1" /> <span><?php esc_html_e( 'Ship to a different address?', 'woocommerce' ); ?></span>
			</label>
		</h3>

	<?php endif; ?>
</div>
