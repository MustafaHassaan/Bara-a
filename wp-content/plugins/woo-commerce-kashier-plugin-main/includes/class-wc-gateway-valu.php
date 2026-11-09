<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WC_Gateway_Kashier class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_Kashier_Valu extends Kashier_Gateway
{
    protected $_code = 'valu';
    public $name = 'valu';
    public $icons = ['valu.svg'];

}