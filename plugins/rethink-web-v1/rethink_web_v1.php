<?php
/**
 * Plugin Name: Rethink Payment Web V1
 * Description: Rethink Payment Plugin for Web
 * Version: 1.0
 * Author: Bud Plug
 * License: GPLv2 or later
 * Text Domain: rethink-payment
 *
 * @package Rethink Payment
 */

if (!defined('ABSPATH')) {
    return;
}

add_action('plugins_loaded', 'init_rethink_web_gateway_class');

add_filter('woocommerce_payment_gateways', 'add_rethink_web_gateway_class');

/**
 * Add the gateway to WooCommerce
 *
 * @param array $methods WooCommerce payment methods.
 */
function add_rethink_web_gateway_class($methods)
{
    $methods[] = 'Rethink_Payment_Web_Gateway';
    return $methods;
}

/**
 * Initialize Gateway Class
 */
function init_rethink_web_gateway_class()
{
    /**
     * Add the gateway to WooCommerce
     */
    class Rethink_Payment_Web_Gateway extends WC_Payment_Gateway
    {


        public function payment_fields() {
            // Display your payment form fields
            echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';
        
            // Add this action hook if you want your custom payment gateway to support it
            do_action( 'woocommerce_credit_card_form_start', $this->id );
        
//             // Input field for Card Number
//             echo '<div class="form-row form-row-wide">
//                     <label>Card Number <span class="required">*</span></label>
//                     <input id="cardnumber" name="cardnumber" type="text" maxlength="16" autocomplete="off">
//                   </div>';
        
//             // Input field for Expiry Month
//             echo '<div class="form-row form-row-first">
//                     <label>Expiry Month <span class="required">*</span></label>
//                     <input id="exp_month" name="exp_month" type="text" maxlength="2" autocomplete="off" placeholder="MM">
//                   </div>';
        
//             // Input field for Expiry Year
//             echo '<div class="form-row form-row-last">
//                     <label>Expiry Year <span class="required">*</span></label>
//                     <input id="exp_year" name="exp_year" type="text" maxlength="2" autocomplete="off" placeholder="YY">
//                   </div>';
        
//             // Input field for CVC
//             echo '<div class="form-row form-row-wide">
//                     <label>CVC <span class="required">*</span></label>
//                     <input id="cvc" name="cvc" type="text" maxlength="4" autocomplete="off">
//                   </div>';
        
            // Close the form
            do_action( 'woocommerce_credit_card_form_end', $this->id );
        
            echo '<div class="clear"></div></fieldset>';
        }
        
        


        /**
         * Api URL
         *
         * @var string
         */
        public $api_url = 'https://api.rt.app/api/crypto/onramp';

        /**
         * Api Check Token URL
         *
         * @var string
         */
        public $api_check_token_url = 'https://www.rethinkpay.io/V1/check_payment_by_token';

        /**
         * Merchant ID
         *
         * @var string
         */
        public string $merchant_id;

        /**
         * API Key
         *
         * @var string
         */
        public string $api_key;

        /**
         * Secret Key
         *
         * @var string
         */
        public string $secret_key;

        /**
         * Order Status
         *
         * @var string
         */
        public string $order_status;

        /**
         * Class constructor
         */
        public function __construct()
        {
            $this->id = 'rethink_payment_v1';
            $this->icon = 'https://www.rethinkpay.io/resources/rethinkapi/images/logo_icon.png';
            $this->method_title = __('Rethink Payments Web', 'rethink-payment');
            $this->method_description = __('Rethink Payment Plugin for Web', 'rethink-payment');
            $this->title = __('Rethink Payments', 'rethink-payment');
            $this->has_fields = false;
            $this->enabled = $this->get_option('enabled');
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->merchant_id = $this->get_option('merchant_id');
            $this->api_key = $this->get_option('api_key');
            $this->secret_key = $this->get_option('secret_key');
            $this->order_status = $this->get_option('order_status');
            $this->api_key = $this->get_option('api_key');

            $this->init_form_fields();
            $this->init_settings();

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }

        /**
         * Initialize Gateway Settings Form Fields
         *
         * @since 1.0.0
         * @return void
         */
        public function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'rethink-payment'),
                    'type' => 'checkbox',
                    'label' => __('Enable WeedMart Payments', 'rethink-payment'),
                    'default' => 'yes',
                ),

                'title' => array(
                    'title' => __('Method Title', 'rethink-payment'),
                    'type' => 'text',
                    'description' => __('This controls the title', 'rethink-payment'),
                    'default' => __('Weedmart Payments', 'rethink-payment'),
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => __('Customer Message', 'rethink-payment'),
                    'type' => 'textarea',
                    'css' => 'width:500px;',
                    'default' => 'Orders will be canceled if not paid within 15 minutes.',
                    'description' => __('The message which you want it to appear to the customer in the checkout page.', 'rethink-payment'),
                ),
                'merchant_id' => array(
                    'title' => __('Merchant Id', 'rethink-payment'),
                    'type' => 'text',
                    'description' => __('Enter Your Merchant ID Provided by Rethink', 'rethink-payment'),
                    'default' => __('oezE3080', 'rethink-payment'),
                    'desc_tip' => true,
                ),
                'api_key' => array(
                    'title' => __('Rethink API Key', 'rethink-payment'),
                    'type' => 'text',
                    'description' => __('Enter Your Sandbox API Key', 'rethink-payment'),
                    'default' => __('XO5Jqwy8mpSrFJzdIcIo8l88394435QH', 'rethink-payment'),
                    'desc_tip' => true,
                ),
                'secret_key' => array(
                    'title' => __('Rethink Secret Key', 'rethink-payment'),
                    'type' => 'password',
                    'description' => __('Enter Your Sandbox Secret Key', 'rethink-payment'),
                    'default' => __('pHCdFxeWzh2FyKVMcNOzyB72693080rz', 'rethink-payment'),
                    'desc_tip' => true,
                ),
                'order_status' => array(
                    'title' => __('Order Status After The Checkout', 'rethink-payment'),
                    'type' => 'select',
                    'options' => wc_get_order_statuses(),
                    'default' => 'wc-pending',
                    'description' => __('The default order status if this gateway used in payment.', 'rethink-payment'),
                ),                
            );
        }

        /**
         * Process the payment and return the result
         *
         * @param int $order_id The order ID.
         */
        public function process_payment($order_id)
        {
            $order = wc_get_order($order_id);

            // Debugging: Log the order ID to check if this function is called
            error_log('Processing payment for order ID: ' . $order_id);

            // Collect order data and prepare the payment request
            $user_ip_address = $_SERVER['REMOTE_ADDR']; // Get the user's IP address
            $result = '';

            // Identify the card brand
            $cardBrand = identify_card_brand($_POST['cardnumber']);
            error_log('Identified card brand: ' . $cardBrand);

            $user_input_data = array(
                'amount' => (int)($order->get_total() ),
                'currency' => $order->get_currency(),
                'card' => array(
                    'number' => $_POST['cardnumber'], // Get card number from the form
                    'expiration_month' => $_POST['exp_month'], // Get expiration month from the form
                    'expiration_year' => 20 . $_POST['exp_year'], // Get expiration year from the form
                    'cvc' => $_POST['cvc'], // Get CVC from the form
                    'brand' => $cardBrand // You can populate the brand if needed
                ),                
                'firstName' => $order->get_billing_first_name(),
                'lastName' => $order->get_billing_last_name(),
                'email' => $order->get_billing_email(),
                'phone_number' => $order->get_billing_phone(),
                'capture_method' => 'automatic',
                'country_code' => '1',
                'ipAddress' => $user_ip_address,
                'merchantId' => $this->merchant_id,
                'orderId' => $order->get_id(),
                'secretKey' => $this->secret_key,
                'redirectURL' => home_url('/wp-json/rethinkweb/v1/order-update/?orderId=' . $order->get_id() . '&rethink_token=' . $result->paymentToken), 
                'redirect_time' => '1',
                'payment_element_display' => 0,
                'is_allow_store_card' => 0,
                'show_only_latest_card' => 1,
                'billing_zipcode' => $order->get_billing_postcode(),
            );
            error_log('User Input Data: ' . json_encode($user_input_data));

            $request_data = json_encode($user_input_data);

            // Send the payment request to Rethink Payment API
            $ch = curl_init($this->api_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request_data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                array(
                    'Content-Type: application/json',
                    'Authorization: ' . $this->api_key,
                )
            );
            $response = curl_exec($ch);
            curl_close($ch);

            // Debugging: Log the response from the API
            error_log('API Response: ' . $response);

            $result = json_decode($response); // Move this line here

            if ('Success' === $result->status) {
                $existing_token = get_post_meta($order_id, '_rethink_card_token', true);
                if (empty($existing_token)) {
                // The custom field doesn't exist, so update it
                $updated = update_post_meta($order_id, 'rethink_card_token', $result->paymentToken);
                
                if ($updated) {
                    $order->save();
                    // Log a success message if the update was successful
                    error_log('Custom field rethink_card_token updated successfully.');
                    // error_log('Updated Order Object: ' . print_r($order->get_data(), true));

                } else {
                    // Log an error message if the update failed
                    error_log('Order ID: ' . $order_id);
                    error_log('Failed to update custom field rethink_card_token.');
                }
            } else {
                // The custom field already exists, do something else or log a message
                error_log('Custom field rethink_card_token already exists: ' . $existing_token);
            }
            
                // Return a successful result with the redirect URL including the payment token
                return array(
                    'result' => 'success',
                    'redirect' => 'https://dashboard.rt.app/V1/payment/popup?rethink_token=' . $result->paymentToken,
                );
            }

            return $this->get_failed_response();

        }

        /**
         * Get failed response.
         *
         * @return array
         */
        public function get_failed_response()
        {
            return array(
                'result' => 'failed',
                'redirect' => '/checkout',
            );
        }    
    }
}

add_action('rest_api_init', 'register_custom_order_update_endpoint');

function register_custom_order_update_endpoint()
{
    register_rest_route('rethinkweb/v1', '/order-update', array(
        'methods' => 'GET',
        'callback' => 'custom_order_update_callback',
        'permission_callback' => '__return_true', 

    ));
}

function custom_order_update_callback($request)
{
    $rethink_token = $request->get_param('rethink_token');
    $order_id = $request->get_param('orderId'); // Get the orderId parameter
    error_log('Received rethink_token: ' . $rethink_token);

    if ($order_id) {
        // Look up the order by order ID
        $order = wc_get_order($order_id);
        if ($order) {
            error_log('Found order for order ID: ' . $order_id);
            // Update the order status to "processing"
            $order->update_status('processing');

            // Redirect the user to the order confirmation page
            $confirmation_url = $order->get_checkout_order_received_url();
            error_log('Redirecting to confirmation URL: ' . $confirmation_url);
            wp_safe_redirect($confirmation_url);
            exit;
        } else {
            error_log('No order found for order ID: ' . $order_id);
        }
    }

    // Handle the case where no order is found or no orderId parameter is provided
    wp_safe_redirect(home_url('/')); // Redirect to the home page or an error page
    exit;
}

/**
 * Identify the card brand based on the card number.
 *
 * @param string $card_number The card number to be identified.
 * @return string The card brand (e.g., 'visa', 'mastercard', etc.).
 */
function identify_card_brand($card_number) {
    // Known Card Patterns
    $patterns = array(
        'American Express' => '/^3[47][0-9]{13}$/',
        'Diners Club' => '/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',
        'Discover' => '/^6(?:011|5[0-9]{2})[0-9]{12}$/',
        'Elo' => '/^(?:4[035]|5[0-9]{2}|6[2356])[0-9]{12}(?:[0-9]{3})?$/',
        'Hiper' => '/^(606282|637095|637568)[0-9]{10}$/',
        'Hiper Card' => '/^(3841|606282|637095|637568)[0-9]{10}$/',
        'JCB' => '/^(?:2131|1800|35[0-9]{3})[0-9]{11}$/',
        'Maestro' => '/^(?:5[0678]|6)[0-9]{15}$/',
        'Mastercard' => '/^5[1-5][0-9]{14}$/',
        'MIR' => '/^220[0-4][0-9]{12}$/',
        'UnionPay' => '/^(62[0-9]{14,17})$/',
        'Visa' => '/^4[0-9]{12}(?:[0-9]{3})?$/'
    );

    // Remove any non-numeric characters from the card number
    $cardWithoutSpaces = preg_replace('/\s+/', '', $card_number);

    // Initialize the card brand variable
    $cardType = '';

    // Iterate through card patterns to identify the card brand
    foreach ($patterns as $type => $pattern) {
        if (preg_match($pattern, $cardWithoutSpaces)) {
            $cardType = strtolower(str_replace(' ', '-', $type));
            break;
        }
    }

    return $cardType;
}
