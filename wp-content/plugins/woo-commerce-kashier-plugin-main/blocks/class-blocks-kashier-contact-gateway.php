<?php

class Kashier_Gateway_Contact_Blocks extends Base_Gateway_Blocks {
    protected $gateway_script = 'kashier_gateway-contact-blocks-integration';
protected $name = 'kashier_contact';
    protected function create_gateway_instance() {
        return new WC_Gateway_Kashier_Contact();
    }
}
