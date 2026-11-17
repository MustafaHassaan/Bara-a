<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WC_Gateway_Kashier class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_Kashier_Aman extends Kashier_Gateway
{
    protected $_code = 'aman';
     public $name = 'aman';
    public $icons = ['aman.svg'];

}