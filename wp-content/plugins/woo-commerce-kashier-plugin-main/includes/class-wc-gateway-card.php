<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WC_Gateway_Kashier class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_Kashier_Card extends Kashier_Gateway
{
    protected $_code = 'card';
    public $name = 'card';
    public $icons = ['credit-card.svg','visa.svg','mastercard.svg','meeza.svg'];

}