<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WC_Gateway_Kashier class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_Kashier_Installment extends Kashier_Gateway
{

    protected $_code = 'bank_installments';
    public $name = 'bank_installments';
    public $icons = ['bank-installments.svg'];
   
}