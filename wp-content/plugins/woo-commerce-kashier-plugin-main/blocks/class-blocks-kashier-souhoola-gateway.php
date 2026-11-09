<?php
class Kashier_Gateway_Souhoola_Blocks extends Base_Gateway_Blocks {
    protected $gateway_script = 'kashier_gateway-souhoola-blocks-integration';
    protected $name = 'kashier-souhoola';
    protected function create_gateway_instance() {
        return new WC_Gateway_Kashier_Souhoola();
    }
}