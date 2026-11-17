<?php

class Kashier_Gateway_Aman_Blocks extends Base_Gateway_Blocks {
    protected $gateway_script = 'kashier_gateway-aman-blocks-integration';
protected $name = 'kashier-Aman';
    protected function create_gateway_instance() {
        return new WC_Gateway_Kashier_Aman();
    }
}