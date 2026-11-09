<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

abstract class Base_Gateway_Blocks extends AbstractPaymentMethodType {
    protected $gateway;
    protected $gateway_id;
    protected $gateway_name;
    protected $gateway_script;

    public function initialize() {
        $this->gateway = $this->create_gateway_instance();
    }

    abstract protected function create_gateway_instance();

    public function get_payment_method_script_handles() {
        $v = rand(1, 9999);
        
        wp_register_script(
            $this->gateway_script,
            plugin_dir_url(__FILE__) . '../assets/js/blocks/' . $this->gateway->name . '.js?v=' . $v,
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            null,
            true
        );

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations($this->gateway_script);
        }

        if (isset(WC()->cart->total) && WC()->cart->total > 0) {
            wp_localize_script($this->gateway_script, 'wc_'.$this->gateway->name.'_params', array(
                'baseurl'       => plugin_dir_url(__FILE__) . '../',
                'icons'         => $this->gateway->icons,
                'title'         => $this->gateway->name,
                'description'   => $this->gateway->method_public_description,
                'id'            => $this->gateway->id
            ));
        }

        return [$this->gateway_script];
    }
}
