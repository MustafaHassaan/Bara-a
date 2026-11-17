<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WC_Gateway_Kashier class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_Kashier_Wallet extends Kashier_Gateway
{
    protected $_code = 'wallet';
    public $name = 'wallet';
    public $icons = ['meeza-wallet.svg'];

}