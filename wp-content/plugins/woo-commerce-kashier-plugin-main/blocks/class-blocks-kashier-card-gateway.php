<?php
class Kashier_Gateway_Card_Blocks extends Base_Gateway_Blocks {
    protected $gateway_script = 'kashier_gateway_card_blocks_integration';
    protected $name = 'kashier_card';
    protected function create_gateway_instance() {
        return new WC_Gateway_Kashier_Card();
    }
}