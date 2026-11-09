<?php
if (!defined('ABSPATH')) {
    exit;
}
use ITeam\Kashier\Core\KashierConstants;


/**
 * Provides static methods as helpers.
 */
class WC_Kashier_Helper
{
    /**
     * Localize Kashier messages based on code
     * @return array
     */
    public static function get_localized_messages()
    {
        return apply_filters(
            'wc_kashier_localized_messages',
            array( 
                'card' =>    array(
  
                              'payment_method_title' => __('Card', 'woocommerce-gateway-kashier'),
                              'payment_method_description' => __('Online Payments via Credit Card by <a href="%1$s">Kashier</a> <a href="%2$s">Signup</a> to obtain your test MID and Credentials.', 'woocommerce-gateway-kashier'),
                              'payment_method_public_description' => __('Pay with your Credit Card.'),
                              'please_check_card_info' => __('Please check your card info.', 'woocommerce-gateway-kashier'),
                              'order_not_found' => __('Requested order not found', 'woocommerce-gateway-kashier'),
                              'payment_failed' => __('Payment processing failed. Please try again.', 'woocommerce-gateway-kashier'),
                              'refund_failed' => __('Refund processing failed. Please try again.', 'woocommerce-gateway-kashier'),
                              'minimum_amount_error' => __('Sorry, the minimum allowed order total is %1$s to use this payment method.', 'woocommerce-gateway-kashier')
                  ),
                  'wallet' =>         array(
  
                               'payment_method_title' => __('Mobile Wallet', 'woocommerce-gateway-kashier'),
                               'payment_method_description' => __('Online Payments via Mobile Wallet by <a href="%1$s">Kashier</a> <a href="%2$s">Signup</a> to obtain your test MID and Credentials.', 'woocommerce-gateway-kashier'),
                               'payment_method_public_description' => __('Pay with your Mobile Wallet.'),
                               'please_check_card_info' => __('Please check your card info.', 'woocommerce-gateway-kashier'),
                               'order_not_found' => __('Requested order not found', 'woocommerce-gateway-kashier'),
                               'payment_failed' => __('Payment processing failed. Please try again.', 'woocommerce-gateway-kashier'),
                               'refund_failed' => __('Refund processing failed. Please try again.', 'woocommerce-gateway-kashier'),
                               'minimum_amount_error' => __('Sorry, the minimum allowed order total is %1$s to use this payment method.', 'woocommerce-gateway-kashier')
                  ),
                  'bank_installments' =>         array(
  
                               'payment_method_title' => __('Bank Installment', 'woocommerce-gateway-kashier'),
                               'payment_method_description' => __('Online Payments via Bank Installment by <a href="%1$s">Kashier</a> <a href="%2$s">Signup</a> to obtain your test MID and Credentials.', 'woocommerce-gateway-kashier'),
                               'payment_method_public_description' => __('Pay with your Bank Installment.'),
                               'please_check_card_info' => __('Please check your card info.', 'woocommerce-gateway-kashier'),
                               'order_not_found' => __('Requested order not found', 'woocommerce-gateway-kashier'),
                               'payment_failed' => __('Payment processing failed. Please try again.', 'woocommerce-gateway-kashier'),
                               'refund_failed' => __('Refund processing failed. Please try again.', 'woocommerce-gateway-kashier'),
                               'minimum_amount_error' => __('Sorry, the minimum allowed order total is %1$s to use this payment method.', 'woocommerce-gateway-kashier')
                   ),
                     'fawry' =>         array(

                               'payment_method_title' => __('Fawry', 'woocommerce-gateway-kashier'),
                               'payment_method_description' => __('Online Payments via Fawry reference code by <a href="%1$s">Kashier</a> <a href="%2$s">Signup</a> to obtain your test MID and Credentials.', 'woocommerce-gateway-kashier'),
                               'payment_method_public_description' => __('Pay with Fawry code.'),
                               'please_check_card_info' => __('Please check your f info.', 'woocommerce-gateway-kashier'),
                               'order_not_found' => __('Requested order not found', 'woocommerce-gateway-kashier'),
                               'payment_failed' => __('Payment processing failed. Please try again.', 'woocommerce-gateway-kashier'),
                               'refund_failed' => __('Refund processing failed. Please try again.', 'woocommerce-gateway-kashier'),
                               'minimum_amount_error' => __('Sorry, the minimum allowed order total is %1$s to use this payment method.', 'woocommerce-gateway-kashier')
                   ),
                     'valu' =>         array(
                    'payment_method_title' => __('ValU', 'woocommerce-gateway-kashier'),
                    'payment_method_description' => __('Online Payments via ValU  by <a href="%1$s">Kashier</a> <a href="%2$s">Signup</a> to obtain your test MID and Credentials.', 'woocommerce-gateway-kashier'),
                    'payment_method_public_description' => __('Pay with ValU.'),
                    'please_check_card_info' => __('Please check your ValU info.', 'woocommerce-gateway-kashier'),
                    'order_not_found' => __('Requested order not found', 'woocommerce-gateway-kashier'),
                    'payment_failed' => __('Payment processing failed. Please try again.', 'woocommerce-gateway-kashier'),
                    'refund_failed' => __('Refund processing failed. Please try again.', 'woocommerce-gateway-kashier'),
                    'minimum_amount_error' => __('Sorry, the minimum allowed order total is %1$s to use this payment method.', 'woocommerce-gateway-kashier')
                     ),
                     'souhoola' =>         array(
                    'payment_method_title' => __('Souhoola', 'woocommerce-gateway-kashier'),
                    'payment_method_description' => __('Online Payments via Souhoola  by <a href="%1$s">Kashier</a> <a href="%2$s">Signup</a> to obtain your test MID and Credentials.', 'woocommerce-gateway-kashier'),
                    'payment_method_public_description' => __('Pay with Souhoola.'),
                    'please_check_card_info' => __('Please check your Souhoola info.', 'woocommerce-gateway-kashier'),
                    'order_not_found' => __('Requested order not found', 'woocommerce-gateway-kashier'),
                    'payment_failed' => __('Payment processing failed. Please try again.', 'woocommerce-gateway-kashier'),
                    'refund_failed' => __('Refund processing failed. Please try again.', 'woocommerce-gateway-kashier'),
                    'minimum_amount_error' => __('Sorry, the minimum allowed order total is %1$s to use this payment method.', 'woocommerce-gateway-kashier')
                ),
                'aman' =>         array(
               'payment_method_title' => __('Aman', 'woocommerce-gateway-kashier'),
               'payment_method_description' => __('Online Payments via Aman  by <a href="%1$s">Kashier</a> <a href="%2$s">Signup</a> to obtain your test MID and Credentials.', 'woocommerce-gateway-kashier'),
               'payment_method_public_description' => __('Pay with Aman.'),
               'please_check_card_info' => __('Please check your Aman info.', 'woocommerce-gateway-kashier'),
               'order_not_found' => __('Requested order not found', 'woocommerce-gateway-kashier'),
               'payment_failed' => __('Payment processing failed. Please try again.', 'woocommerce-gateway-kashier'),
               'refund_failed' => __('Refund processing failed. Please try again.', 'woocommerce-gateway-kashier'),
               'minimum_amount_error' => __('Sorry, the minimum allowed order total is %1$s to use this payment method.', 'woocommerce-gateway-kashier')
                ),
                'contact' =>         array(
               'payment_method_title' => __('Contact Now', 'woocommerce-gateway-kashier'),
               'payment_method_description' => __('Online Payments via Contact Now  by <a href="%1$s">Kashier</a> <a href="%2$s">Signup</a> to obtain your test MID and Credentials.', 'woocommerce-gateway-kashier'),
               'payment_method_public_description' => __('Pay with Contact Now.'),
               'please_check_card_info' => __('Please check your Contact Now info.', 'woocommerce-gateway-kashier'),
               'order_not_found' => __('Requested order not found', 'woocommerce-gateway-kashier'),
               'payment_failed' => __('Payment processing failed. Please try again.', 'woocommerce-gateway-kashier'),
               'refund_failed' => __('Refund processing failed. Please try again.', 'woocommerce-gateway-kashier'),
               'minimum_amount_error' => __('Sorry, the minimum allowed order total is %1$s to use this payment method.', 'woocommerce-gateway-kashier')
           )
        ));
    }

    public static function get_localized_message($method,$message_key)
    {
        $messages = self::get_localized_messages();
        return $messages[$method][$message_key];
    }

    /**
     * Checks Kashier minimum order value authorized
     */
    public static function get_minimum_amount()
    {
        return 0;
    }

    /**
     * Checks if WC version is less than passed in version.
     *
     * @param string $version Version to check against.
     * @return bool
     */
    public static function is_wc_lt($version)
    {
        return version_compare(WC_VERSION, $version, '<');
    }

    public static function expiry_year_format($year, $from_format = 'y', $to_format = 'Y')
    {
        $dt = DateTime::createFromFormat($from_format, $year);
        return $dt->format($to_format);
    }

    public static function prepare_script($parameters)
    {
        $order_id = $parameters['order']->get_id();
        $connected_account_mid = isset($parameters['connected_account_mid']) ? $parameters['connected_account_mid'] : null;
        $firstName =  WC_Kashier_Helper::is_wc_lt('3.0') ? $parameters['order']->billing_first_name : $parameters['order']->get_billing_first_name();
        $secondName =  WC_Kashier_Helper::is_wc_lt('3.0') ? $parameters['order']->billing_last_name : $parameters['order']->get_billing_last_name();
        $fullName =  str_replace(' ', '-', $firstName . ' ' . $secondName);
        
        $metaData = self::encode_urlComponent(json_encode(array(
            'ecommercePlatform'=>'woocommerce',
            'OrderId' => $order_id,
            'CustomerEmail' => $parameters['order']->get_billing_email(),
            'CustomerName' => $fullName,
        )));

        $language = substr(get_bloginfo("language"),0,2) == 'ar' ? 'ar' : 'en';

        $values = self::currency_converter($parameters['advanced_options'], $parameters['selected_currency'], $parameters['exchange_rate'], $parameters['order']);

        $storeName = str_replace(' ', '-', get_bloginfo( 'name' ));
        $iframe_base_url = KashierConstants::IFRAME_BASE_URL;
        $iframeParameter = new \ITeam\Kashier\Iframe\IframeParameter();
        $iframeParameter
            ->setOrderId($parameters['order']->get_id() . '-' . time())
            ->setMethod($parameters['method'])
            ->setMerchantId($parameters['context']->getMerchantId())
            ->setShopperReference((string)get_current_user_id())
            ->setDisplay($language)
            ->setCurrency($values['currency'])
            ->setAmount($values['amount'])
            ->setMode($parameters['mode'])
            ->setMerchantRedirect($parameters['order']->get_checkout_order_received_url());

        // save order_id-timestamp in the DB
        update_post_meta($order_id, '_kashier_order_id', $iframeParameter->getOrderId());

        $hash = new \ITeam\Kashier\Security\Hash($parameters['context'], $iframeParameter);
        $iframeParameter
        ->setHash($hash->encrypt());
        
        $method = $iframeParameter->getMethod();

        $query = [
            'merchantId'        => $parameters['context']->getMerchantId(),
            'orderId'           => $parameters['order']->get_id() . '-' . time(),
            'amount'            => $values['amount'],
            'currency'          => $values['currency'],
            'hash'              => $hash->encrypt(),
            'mode'              => $parameters['mode'],
            'metaData'          => $metaData,
            'merchantRedirect'  => $parameters['order']->get_checkout_order_received_url(),
            'allowedMethods'    => $method =='valu' || $method =='souhoola' || $method =='aman' || $method =='contact'?'bnpl'."[".$method."]" : $method,
            'failureRedirect'   => 'true', // Optional: change if needed
            'redirectMethod'    => 'get',   // Or 'post' if Kashier supports it
            'display'           => $language,
            'serverWebhook'     => add_query_arg('wc-api', $parameters['webhookParameter'], home_url('/')),
        ];

        $dataDefaultMethod = null;

        if ($method == 'valu') {
            $dataDefaultMethod = 'bnpl'."[".$method."]";
        } elseif ($method == 'souhoola') {
            $dataDefaultMethod = 'bnpl'."[".$method."]";
        } elseif ($method == 'aman') {
            $dataDefaultMethod = 'bnpl'."[".$method."]";
        }elseif ($method == 'contact') {
            $dataDefaultMethod = 'bnpl'."[".$method."]";
        }

        if ($dataDefaultMethod) {
            $query['defaultMethod'] = $dataDefaultMethod;
        }

        $redirect_url = $iframe_base_url . '?' . http_build_query($query);
        ?>

        <script>
            window.location.href = <?php echo json_encode($redirect_url); ?>;
        </script>

        <?php
    }

    
    public static function  encode_urlComponent($str) {
        $revert = array('%21'=>'!', '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')');
        return strtr(rawurlencode($str), $revert);
    }

    public static function currency_converter($advanced_options, $selected_currency, $exchange_rate, $order){
        if( $selected_currency === 'EGP' && $order->get_currency() != 'EGP' ){

            $actualAmount = (float) ($order->get_total() / $exchange_rate);
              $actualAmount = round($actualAmount,2);
                $values = array('currency' => $selected_currency, 'amount' => $actualAmount );
                return $values;              
        } 
        else {
            $values = array('currency' => $order->get_currency(), 'amount' => $order->get_total());
            return $values;
        }
    }

    public static function connected_account($advanced_options, $enable_connected_account, $order_meta){
        $connectedAccount = null;

        if($advanced_options && $enable_connected_account) {
            $path = __DIR__ .  DIRECTORY_SEPARATOR  . ".." . DIRECTORY_SEPARATOR . "connected-account" . DIRECTORY_SEPARATOR ."connected_account.txt" ;

            if(file_exists($path)){

                $file = fopen($path, "r") or die("Unable to open file!");
                $optionName = trim(fgets($file));
                
                while (($line = fgets($file)) !== false) {
                    // process the line read.
                    $seperatedLine = explode(" | ", $line) ;
                    $optionValue = trim($seperatedLine[0]);

                    if($order_meta[$optionName] == $optionValue){
                        $connectedAccount = trim($seperatedLine[1]);
                    };
                }
            
                fclose($file);
            }

        }

        return $connectedAccount;
    }
}
