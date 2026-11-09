=== Mastercard Merchant Cloud ===
Contributors: saucal
Tags: credit card, payments, woocommerce, woo, mastercard
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 1.0.1
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Seamless WooCommerce checkout with 35+ payment methods, built-in 3D Secure, saved cards, and refunds managed from your order screen.

== Description ==

Mastercard Merchant Cloud gives your WooCommerce store a fast, familiar checkout backed by Mastercard's payment gateway — supporting 35+ payment methods and multi-currency for global selling, with built-in security and simple integration.

= Why merchants use it =

* **Improve conversion** — fast, familiar checkout with flexible flows and optional saved cards that reduce friction and cart abandonment.
* **Reduce fraud** — built-in 3D Secure and risk controls protect transactions without hurting the customer experience.
* **Operate efficiently** — capture, void, and refund directly from the WooCommerce order screen, with webhooks keeping your store and the gateway in sync.

= Flexible checkout options =

* **Hosted Session** — embedded secure payment fields with saved-card support.
* **Hosted Checkout – Embedded** — Mastercard-hosted form inside an iframe.
* **Hosted Checkout – Redirect** — standalone Mastercard page for the lowest PCI effort.

= Features =

* Authorize-only or authorize-and-capture payment actions
* Saved cards
* 3D Secure authentication
* Full and partial refunds and voids from the order admin
* Webhook notifications for real-time synchronization
* Logging and debugging tools
* Translation-ready

= Security =

* Tokenization and secure processing handled by Mastercard
* PCI-optimized flows; sensitive card data never handled or stored by your store

== Installation ==

1. Requirements: PHP 8.0+, WordPress 6.0+, WooCommerce 9.0+, an active SSL certificate, and a Mastercard merchant account.
2. Install and activate the plugin through Plugins → Add New → Upload Plugin, or via the WordPress plugin directory.
3. Go to WooCommerce → Settings → Payments and enable Mastercard Merchant Cloud.
4. From your Merchant Portal, open Admin → Integration Settings and copy your Integration Authentication Password.
5. In the gateway settings, enter your Merchant Region, Merchant ID, and Integration Authentication Password, then save.
6. Choose your checkout mode (Hosted Session or Hosted Checkout) and optionally enable 3D Secure and saved cards.

== Frequently Asked Questions ==

= Where do I get my credentials? =
Log into your Mastercard Merchant Portal, go to Admin → Integration Settings, and retrieve your Integration Authentication Password. Your Merchant ID and Region are shown in your account.

= Do I need an SSL certificate? =
Yes. A valid SSL certificate (HTTPS) is required to process payments.

== External services ==

This plugin connects to the Mastercard Merchant Cloud payment gateway (Mastercard Gateway API) to process payments. This service is required for the plugin to function. The gateway endpoint is region-specific and selected by the Merchant Region you configure (for example `https://<region-gateway-host>/api/rest/...`). This service is provided by Mastercard. See Mastercard's terms of service: https://developer.mastercard.com/terms-of-use and privacy policy: https://www.mastercard.com/us/en/global-privacy-notice.html.

== Source code ==

The complete source code is publicly available at: https://github.com/saucal/mastercard-merchant-cloud

== Changelog ==

= 1.0.1 =
* Improve coding standards to meet WordPress.org guidelines

= 1.0.0 =
* Initial release
