<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WC_Gateway_Kashier class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_Kashier_Souhoola extends Kashier_Gateway
{
    protected $_code = 'souhoola';
    public $name = 'souhoola';
    public $icons = ['souhoola.svg'];

}