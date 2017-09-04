<?php
/**
 * class.pmprogateway_payfast.php
 *
 * @author     Ron Darby - PayFast
 */
    require_once(dirname(__FILE__) . "/class.pmprogateway.php");
	//load classes init method
	add_action('init', array('PMProGateway_payfast', 'init'));
    class PMProGateway_payfast
    {

        const SANDBOX_MERCHANT_KEY = '46f0cd694581a';
        const SANDBOX_MERCHANT_ID = '10000100';

	    function PMProGateway_payfast($gateway = NULL)
       {
           $this->gateway = $gateway;
           return $this->gateway;
       }

	    /**
         * Run on WP init
         *
         * @since 1.8
         */
        static function init()
        {
            //make sure PayPal Express is a gateway option
            add_filter('pmpro_gateways', array('PMProGateway_payfast', 'pmpro_gateways'));

            //add fields to payment settings
            add_filter('pmpro_payment_options', array('PMProGateway_payfast', 'pmpro_payment_options'));


	        add_filter('pmpro_payment_option_fields', array('PMProGateway_payfast', 'pmpro_payment_option_fields'), 10, 2);

            //code to add at checkout
            $gateway = pmpro_getGateway();

           add_filter('pmpro_include_billing_address_fields', '__return_false');
	        add_filter('pmpro_include_payment_information_fields', '__return_false');

           add_filter('pmpro_required_billing_fields', '__return_empty_array');
            add_filter('pmpro_checkout_default_submit_button', array('PMProGateway_payfast', 'pmpro_checkout_default_submit_button'));
            add_filter('pmpro_checkout_before_change_membership_level', array('PMProGateway_payfast', 'pmpro_checkout_before_change_membership_level'), 10, 2);

        }

        /**
         * Make sure this gateway is in the gateways list
         *
         * @since 1.8
         */
        static function pmpro_gateways($gateways)
        {
            if(empty($gateways['payfast']))
                $gateways['payfast'] = __('PayFast', 'pmpro');

            return $gateways;
        }

        /**
         * Get a list of payment options that the this gateway needs/supports.
         *
         * @since 1.8
         */
        static function getGatewayOptions()
        {
            $options = array(
                'payfast_debug',
                'payfast_merchant_id',
                'payfast_merchant_key',
	            'payfast_passphrase'
            );

            return $options;
        }

        /**
         * Set payment options for payment settings page.
         *
         * @since 1.8
         */
        static function pmpro_payment_options($options)
        {
            //get stripe options
            $payfast_options = self::getGatewayOptions();

            //merge with others.
            $options = array_merge($payfast_options, $options);

            return $options;
        }

        /**
         * Display fields for this gateway's options.
         *
         * @since 1.8
         */
        static function pmpro_payment_option_fields($values, $gateway)
        {

        ?>

            <tr class="gateway gateway_payfast" <?php if( $gateway != "payfast" ) { ?>style="display: none;"<?php } ?>>
                 <th scope="row" valign="top">
                     <label for="payfast_merchant_id"><?php _e('PayFast Merchant ID', 'pmpro');?>:</label>
                 </th>
                 <td>
                     <input id="payfast_merchant_id" name="payfast_merchant_id" value="<?php echo esc_attr($values['payfast_merchant_id']); ?>" />
                 </td>
             </tr>
             <tr class="gateway gateway_payfast" <?php if( $gateway != "payfast" ) { ?>style="display: none;"<?php } ?>>
                 <th scope="row" valign="top">
                     <label for="payfast_merchant_key"><?php _e('PayFast Merchant Key', 'pmpro');?>:</label>
                 </th>
                 <td>
                     <input id="payfast_merchant_key" name="payfast_merchant_key" value="<?php echo esc_attr($values['payfast_merchant_key']); ?>" />
                 </td>
             </tr>
             <tr class="gateway gateway_payfast" <?php if( $gateway != "payfast" ) { ?>style="display: none;"<?php } ?>>
                 <th scope="row" valign="top">
                     <label for="payfast_debug"><?php _e('PayFast Debug Mode', 'pmpro');?>:</label>
                 </th>
                 <td>
                     <select name="payfast_debug">
                         <option value="1" <?php if( isset($values['payfast_debug']) && $values['payfast_debug'] ) { ?>selected="selected"<?php } ?>><?php _e('On', 'pmpro');?></option>
                         <option value="0" <?php if( isset($values['payfast_debug']) && !$values['payfast_debug']) { ?>selected="selected"<?php } ?>><?php _e('Off', 'pmpro');?></option>
                     </select>
                 </td>
             </tr>
	        <tr class="gateway gateway_payfast" <?php if( $gateway != "payfast" ) { ?>style="display: none;"<?php } ?>>
                <th scope="row" valign="top">
                    <label for="payfast_passphrase"><?php _e('PayFast Signature', 'pmpro');?>:</label>
                </th>
                <td>
                    <input id="payfast_passphrase" name="payfast_passphrase" value="<?php echo esc_attr($values['payfast_passphrase']); ?>" /> &nbsp;<small><?php _e( 'Do not set a password unless you have set it in your PayFast settings on www.PayFast.co.za'); ?></small>
                </td>
            </tr>
        <?php
      }

        /**
         * Remove required billing fields
         *
         * @since 1.8
         */
        static function pmpro_required_billing_fields($fields)
        {
            return array();
        }

        /**
         * Swap in our submit buttons.
         *
         * @since 1.8
         */
        static function pmpro_checkout_default_submit_button($show)
        {
            global $gateway, $pmpro_requirebilling;

            //show our submit buttons
            ?>

            <span id="pmpro_payfast_checkout" <?php if(($gateway != "paypalexpress" && $gateway != "payfast") || !$pmpro_requirebilling) { ?>style="display: none;"<?php } ?>>
                <input type="hidden" name="submit-checkout" value="1" />
                <input type="image" value="<?php _e('Check Out with PayFast', 'pmpro');?> &raquo;" src="https://www.payfast.co.za/images/logo/PayFast_Logo_75.png" />
            </span>

            <?php

            //don't show the default
            return false;
        }

        /**
         * Instead of change membership levels, send users to PayPal to pay.
         *
         * @since 1.8
         */
        static function pmpro_checkout_before_change_membership_level($user_id, $morder)
        {
            global $discount_code_id;

            //if no order, no need to pay
            if(empty($morder))
                return;

            $morder->user_id = $user_id;
            $morder->saveOrder();

            //save discount code use
            if(!empty($discount_code_id))
                $wpdb->query("INSERT INTO $wpdb->pmpro_discount_codes_uses (code_id, user_id, order_id, timestamp) VALUES('" . $discount_code_id . "', '" . $user_id . "', '" . $morder->id . "', now())");

            //do_action("pmpro_before_send_to_payfast", $user_id, $morder);

            $morder->Gateway->sendToPayFast($morder);
        }



        function process(&$order)
        {
            if(empty($order->code))
                $order->code = $order->getRandomCode();

            //clean up a couple values
            $order->payment_type = "PayFast";
            $order->CardType = "";
            $order->cardtype = "";

            //just save, the user will go to PayFast to pay
            $order->status = "review";
            $order->saveOrder();

            return true;
        }

        function sendToPayFast(&$order)
        {
            global $pmpro_currency;



            //taxes on initial amount
            $initial_payment = $order->InitialPayment;
            $initial_payment_tax = $order->getTaxForPrice($initial_payment);
            $initial_payment = round((float)$initial_payment + (float)$initial_payment_tax, 2);

            //taxes on the amount
            $amount = $order->PaymentAmount;
            $amount_tax = $order->getTaxForPrice($amount);
            $order->subtotal = $amount;
            $amount = round((float)$amount + (float)$amount_tax, 2);

            //build PayFast Redirect
            $environment = pmpro_getOption("gateway_environment");
            if("sandbox" === $environment || "beta-sandbox" === $environment)
            {
                $merchant_id = self::SANDBOX_MERCHANT_ID;
                $merchant_key = self::SANDBOX_MERCHANT_KEY;
                $payfast_url ="https://sandbox.payfast.co.za/eng/process";
            }
            else
            {
                $merchant_id = pmpro_getOption("payfast_merchant_id");
                $merchant_key = pmpro_getOption("payfast_merchant_key");
                $payfast_url = "https://www.payfast.co.za/eng/process";
            }

            $data = array(
                'merchant_id'      => $merchant_id,
                'merchant_key'  => $merchant_key,
                'return_url'        => pmpro_url("confirmation", "?level=" . $order->membership_level->id),
                'cancel_url'    => '',
                'notify_url'    => admin_url("admin-ajax.php") . "?action=payfast_itn_handler",
                'name_first'    => $order->FirstName,
                'name_last'     => $order->LastName,
                'email_address' => $order->Email,
                'm_payment_id'  => $order->code,
                'amount'        => number_format($initial_payment, 2, '.', ''),
                'item_name'     => substr($order->membership_level->name . " at " . get_bloginfo("name"), 0, 127)
                );

            $pfOutput = "";
            foreach( $data  as $key => $val )
            {
                $pfOutput .= $key .'='. urlencode( trim( $val ) ) .'&';
            }


            // Remove last ampersand
	        $passPhrase = pmpro_getOption( 'payfast_passphrase' );

            if( !empty( $passPhrase ) )
            {
                $pfOutput = $pfOutput."passphrase=".urlencode( $passPhrase );
            }
            else
            {
                $pfOutput = substr( $pfOutput, 0, -1 );
            }

            $signature = md5( $pfOutput );

            $payfast_url .= '?'.$pfOutput.'&signature='.$signature;


            wp_redirect($payfast_url);
            exit;
        }

        function cancel(&$order)
        {
            //payfast profile stuff
            $nvpStr = "";
            $nvpStr .= "&PROFILEID=" . urlencode($order->subscription_transaction_id) . "&ACTION=Cancel&NOTE=" . urlencode("User requested cancel.");

            $this->httpParsedResponseAr = $this->PPHttpPost('ManageRecurringPaymentsProfileStatus', $nvpStr);

            if("SUCCESS" == strtoupper($this->httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($this->httpParsedResponseAr["ACK"]) || $this->httpParsedResponseAr['L_ERRORCODE0'] == "11556") {
                $order->updateStatus("cancelled");
                return true;
                //exit('CreateRecurringPaymentsProfile Completed Successfully: '.print_r($this->httpParsedResponseAr, true));
            } else  {
                $order->status = "error";
                $order->errorcode = $this->httpParsedResponseAr['L_ERRORCODE0'];
                $order->error = urldecode($this->httpParsedResponseAr['L_LONGMESSAGE0']);
                $order->shorterror = urldecode($this->httpParsedResponseAr['L_SHORTMESSAGE0']);

                return false;
                //exit('CreateRecurringPaymentsProfile failed: ' . print_r($httpParsedResponseAr, true));
            }
        }


    }