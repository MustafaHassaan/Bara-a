<?php
/**
 * Plugin Name: WooCommerce Kashier Gateway 
 * Description: Acceptd online payments on your store.
 * Author: Kashier
 * Author URI: https://kashier.io/
 * Version: 2.0.1
 * Requires at least: 6.0
 * Tested up to: 9.0
 * WC requires at least: 6.0
 * WC tested up to: 8.0
 * Text Domain: woocommerce-gateway-kashier
 * Domain Path: /languages
 * Plugin URI: https://github.com/Kashier-payments/Kashier-WooCommerce-Plugin/
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}



add_action('plugins_loaded', 'woocommerce_kashier_gateway_init', 0);

function woocommerce_kashier_gateway_init() {
    if (!class_exists('WC_Payment_Gateway')) return;
    add_filter( 'woocommerce_subscriptions_is_duplicate_site', '__return_false' );
    load_plugin_textdomain('woocommerce-gateway-kashier', false, plugin_basename(__DIR__) . '/languages');
    if (!class_exists('WooCommerce')) {

        add_action('admin_notices', 'woocommerce_kashier_missing_wc_notice');

        return;
    }



    
       if (!class_exists('WC_Kashier')) :
        /**
         * Required minimums and constants
         */
        define('WC_KASHIER_VERSION', '1.0.0');
        define('WC_KASHIER_MIN_PHP_VER', '5.6.0');
        define('WC_KASHIER_MIN_WC_VER', '2.6.0');
        define('WC_KASHIER_MAIN_FILE', __FILE__);
        define('WC_KASHIER_PLUGIN_URL', untrailingslashit(plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__))));
        define('WC_KASHIER_PLUGIN_PATH', untrailingslashit(plugin_dir_path(__FILE__)));

        class WC_Kashier
        {
            /**
             * @var WC_Kashier $instance Singleton The reference the *Singleton* instance of this class
             */
            private static $instance;

            /**
             * Returns the *Singleton* instance of this class.
             *
             * @return WC_Kashier The *Singleton* instance.
             */
            public static function get_instance()
            {
                if (null === self::$instance) {
                    self::$instance = new self();
                }

                return self::$instance;
            }

            /**
             * Private clone method to prevent cloning of the instance of the
             * *Singleton* instance.
             *
             * @return void
             */
            private function __clone()
            {
            }

            /**
             *
             * @return void
             */
            public function __wakeup()
            {
            }

            /**
             * Protected constructor to prevent creating a new instance of the
             * *Singleton* via the `new` operator from outside of this class.
             */
            private function __construct()
            {
                add_action('admin_init', array($this, 'install'));
                $this->init();
            }

            /**
             * Init the plugin after plugins_loaded so environment variables are set.
             */
            public function init()
            {
                require_once __DIR__ . '/lib/vendor/autoload.php';
                require_once __DIR__ . '/includes/class-wc-kashier-exception.php';
                require_once __DIR__ . '/includes/class-wc-kashier-logger-factory.php';
                require_once __DIR__ . '/includes/class-wc-kashier-logger.php';
                require_once __DIR__ . '/includes/class-wc-kashier-helper.php';
                require_once __DIR__ . '/class-kashier-gateway.php';
                require_once __DIR__ . '/includes/class-wc-gateway-card.php';
                require_once __DIR__ . '/includes/class-wc-gateway-installment.php';
                require_once __DIR__ . '/includes/class-wc-gateway-valu.php';
                require_once __DIR__ . '/includes/class-wc-gateway-souhoola.php';
                require_once __DIR__ . '/includes/class-wc-gateway-aman.php';
                require_once __DIR__ . '/includes/class-wc-gateway-contact.php';
                require_once __DIR__ . '/includes/class-wc-gateway-wallet.php';
         

                add_filter('woocommerce_payment_gateways', array($this, 'add_gateways'));
                // add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'plugin_action_links'));

                if (version_compare(WC_VERSION, '3.5', '<')) {
                    add_filter( 'woocommerce_get_sections_checkout', array( $this, 'filter_gateway_order_admin' ) );
                }
            }

            /**
             * Updates the plugin version in db
             */
            public function update_plugin_version()
            {
                delete_option('wc_kashier_version');
                update_option('wc_kashier_version', WC_KASHIER_VERSION);
            }

            /**
             * Handles upgrade routines.
             */
            public function install()
            {
                if (!is_plugin_active(plugin_basename(__FILE__))) {
                    return;
                }

                if (!defined('IFRAME_REQUEST') && (WC_KASHIER_VERSION !== get_option('wc_kashier_version'))) {
                    do_action('woocommerce_kashier_updated');

                    if (!defined('WC_KASHIER_INSTALLING')) {
                        define('WC_KASHIER_INSTALLING', true);
                    }

                    $this->update_plugin_version();
                }
            }

            /**
             * Adds plugin action links.
             */
            // public function plugin_action_links($links)
            // {
            //     $plugin_links = array(
            //         '<a href="admin.php?page=wc-settings&tab=checkout&section=kashier">' . esc_html__('Settings', 'woocommerce-gateway-kashier') . '</a>'
            //     );

            //     return array_merge($plugin_links, $links);
            // }

            /**
             * Add the gateways to WooCommerce.
             */
            public function add_gateways($methods)
            {
                $methods[] = 'WC_Gateway_Kashier_Card';
                $methods[] = 'WC_Gateway_Kashier_Installment';
                $methods[] = 'WC_Gateway_Kashier_Valu';
                $methods[] = 'WC_Gateway_Kashier_Souhoola';
                $methods[] = 'WC_Gateway_Kashier_Aman';
                $methods[] = 'WC_Gateway_Kashier_Contact';
                $methods[] = 'WC_Gateway_Kashier_Wallet';

          

                return $methods;
            }

            /**
             * Modifies the order of the gateways displayed in admin.
             */
            public function filter_gateway_order_admin($sections)
            {
                unset($sections['kashier']);

                $sections['kashier'] = 'Kashier';

                return $sections;
            }
        }

        WC_Kashier::get_instance();
    endif;

}

// add_action( 'before_woocommerce_init', function() {
//     if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
//     }
// } );

add_action('before_woocommerce_init', function(){
    
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );

    }

});
add_action('woocommerce_blocks_loaded', function(){
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );

    }
});
// Declare compatibility with WooCommerce Checkout Blocks
// function declare_cart_checkout_blocks_compatibility() {
//     if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
//         \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
//     }
// }

// add_action('before_woocommerce_init', 'declare_cart_checkout_blocks_compatibility');

// Register the custom payment gateway for WooCommerce Checkout Blocks
add_action('woocommerce_blocks_loaded', 'register_kashier_gateway_block');

function register_kashier_gateway_block() {
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) return;
    require_once plugin_dir_path(__FILE__) . '/blocks/base-gatway-block.php';

    
    $gateway_files = glob(plugin_dir_path(__FILE__) . '/blocks/*-gateway.php');

    foreach ($gateway_files as $file) {
        require_once $file;
    }
    // if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) && class_exists( 'Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry' ) ) {
     
    //     $gateway_classes = [
    //         'Kashier_Gateway_Aman_Blocks',
    //         'Kashier_Gateway_Card_Blocks',
    //         'Kashier_Gateway_Wallet_Blocks',
    //         'Kashier_Gateway_Bank_Installments_Blocks',
    //         'Kashier_Gateway_Valu_Blocks',
    //         'Kashier_Gateway_Souhoola_Blocks'
    //     ];

    //     add_action(
    //             'woocommerce_blocks_payment_method_type_registration',
    //             function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry)  {
    //                 $container = Automattic\WooCommerce\Blocks\Package::container();
    //                 $container->register(
    //                     'Kashier_Gateway_Card_Blocks',
    //                     function ()  {
    //                         return new Kashier_Gateway_Card_Blocks();
    //                     }
    //                 );
    //                 $payment_method_registry->register($container->get('Kashier_Gateway_Card_Blocks'));
    //             }
    //         );
        
    //     foreach ($gateway_classes as $gateway_class) {
    //         add_action(
    //             'woocommerce_blocks_payment_method_type_registration',
    //             function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) use ($gateway_class) {
    //                 $container = Automattic\WooCommerce\Blocks\Package::container();
    //                 $container->register(
    //                     $gateway_class,
    //                     function () use ($gateway_class) {
    //                         return new $gateway_class();
    //                     }
    //                 );
    //                 $payment_method_registry->register($container->get($gateway_class));
    //             }
    //         );
    //     }
    // }

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
            $payment_method_registry->register( new Kashier_Gateway_Card_Blocks() );
            $payment_method_registry->register( new Kashier_Gateway_Wallet_Blocks() );
            $payment_method_registry->register( new Kashier_Gateway_Aman_Blocks() );
            $payment_method_registry->register( new Kashier_Gateway_Contact_Blocks() );
            $payment_method_registry->register( new Kashier_Gateway_Valu_Blocks() );
            $payment_method_registry->register( new Kashier_Gateway_Souhoola_Blocks() );
            $payment_method_registry->register( new Kashier_Gateway_Bank_Installments_Blocks() );
        }
    );
 
}

    
     
    

    
    
    
    
