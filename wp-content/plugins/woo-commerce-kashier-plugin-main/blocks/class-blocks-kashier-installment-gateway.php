<?php
class Kashier_Gateway_Bank_Installments_Blocks extends Base_Gateway_Blocks {
    protected $gateway_script = 'kashier_gateway-bank_installments-blocks-integration';
    protected $name = 'kashier-bank_installments';
    protected function create_gateway_instance() {
        return new WC_Gateway_Kashier_Installment();
    }
}