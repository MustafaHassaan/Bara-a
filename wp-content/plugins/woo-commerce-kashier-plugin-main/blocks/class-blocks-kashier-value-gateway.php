<?php
class Kashier_Gateway_Valu_Blocks extends Base_Gateway_Blocks {
    protected $gateway_script = 'kashier_gateway-valu-blocks-integration';
    protected $name = 'kashier_valu';
    protected function create_gateway_instance() {
        return new WC_Gateway_Kashier_Valu();
    }
}