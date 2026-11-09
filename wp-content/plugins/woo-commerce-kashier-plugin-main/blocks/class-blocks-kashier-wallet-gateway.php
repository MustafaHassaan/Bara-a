<?php
class Kashier_Gateway_Wallet_Blocks extends Base_Gateway_Blocks {
    protected $gateway_script = 'kashier_gateway-Wallet-blocks-integration';
    protected $name = 'kashier-Wallet';
    protected function create_gateway_instance() {
        return new WC_Gateway_Kashier_Wallet();
    }
}