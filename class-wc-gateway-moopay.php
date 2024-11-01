<?php

/**
 * @wordpress-plugin
 * Plugin Name:             Zoksh.com Payment Gateway for WooCommerce
 * Description:             Cryptocurrency Payment Gateway.
 * Version:                 0.0.4
 * Author:                  a@zoksh.com
 * Author URI:              https://zoksh.com/
 * License:                 proprietary
 * License URI:             http://zoksh.com/
 * Text Domain:             wc-zoksh-gateway
 * Domain Path:             /
 * Requires at least:       4.9.4
 * Tested up to:            6.0.1
 * WC requires at least:    4.9.5
 * WC tested up to:         6.7.0
 *
 */

/**
 * Exit if accessed directly.
 */
if (!defined('ABSPATH'))
{
    exit();
}

if (version_compare(phpversion(), '7.1', '>=')) {
    ini_set('precision', 10);
    ini_set('serialize_precision', 10);
}

if (!defined('ZOKSH_FOR_WOOCOMMERCE_PLUGIN_DIR')) {
    define('ZOKSH_FOR_WOOCOMMERCE_PLUGIN_DIR', dirname(__FILE__));
}
if (!defined('ZOKSH_FOR_WOOCOMMERCE_ASSET_URL')) {
    define('ZOKSH_FOR_WOOCOMMERCE_ASSET_URL', plugin_dir_url(__FILE__));
}
if (!defined('ZOKSH_VERSION_PFW')) {
    define('ZOKSH_VERSION_PFW', '1.6.2');
}

function wc_zoksh_add_to_gateways( $gateways ) 
{
    if (!in_array('WC_Gateway_zoksh', $gateways)) {
        $gateways[] = 'WC_Gateway_zoksh';
    }
	return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'wc_zoksh_add_to_gateways' );

function wc_zoksh_gateway_plugin_links( $links ) 
{
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=zoksh_gateway' ) . '">' . __( 'Configure', 'wc-zoksh-gateway' ) . '</a>',
        '<a href="mailto:support@zoksh.com?cc=a@zoksh.com">' . __( 'Email Developer', 'wc-zoksh-gateway' ) . '</a>'
    );

	return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_zoksh_gateway_plugin_links' );

add_action('plugins_loaded', 'wc_zoksh_gateway_init', 11);
function wc_zoksh_gateway_init()
{
    if (!class_exists('WC_Payment_Gateway')) { return; }

    class WC_Gateway_Zoksh extends WC_Payment_Gateway
    {
        public function __construct()
        {
            $this->id = 'zoksh_gateway';
            $this->icon = apply_filters('woocommerce_zoksh_icon', 'https://zoksh.com/wp-content/uploads/2022/08/Logo-Small.png');
            $this->has_fields = false;
            $this->method_title = __('zoksh.com', 'wc-gateway-zoksh');
            $this->method_description = __( 'Allows Cryptocurrency payments via zoksh.com', 'wc-zoksh-gateway' );

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->instructions = $this->get_option( 'instructions', $this->description );
            $this->api_secret = $this->get_option('api_secret');
            $this->api_key = $this->get_option('api_key');
            $this->network = $this->get_option('network');
            $this->invoice_prefix = $this->get_option('invoice_prefix', 'WC-');
            $this->simple_total = $this->get_option('simple_total') == 'yes' ? true : false;

            if($this->network=='mainnet')
            {
                $this->baseApiUrl = "https://payments.zoksh.com";
                $this->redirectApiUrl = "https://pay.zoksh.com";
            }
            else
            {
                $this->baseApiUrl = "https://payments.testnet.zoksh.com";
                $this->redirectApiUrl = "https://pay.testnet.zoksh.com";
            }
            // Logs
            $this->log = new WC_Logger();

            // Actions
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
			add_action('woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

            if (!$this->is_valid_for_use()) {
                $this->enabled = false;
            }
        }

		public function thankyou_page() 
        {
			if ( $this->instructions ) {
				echo esc_html(wpautop( wptexturize( $this->instructions ) ) );

                $resp = $this->getTransactionStatus($_GET['transaction']);
                $status  = $this->validate_response($resp);
                if($status == true) { }
			}
        }

        public function admin_options()
        {
            ?>
            <h3><?php _e('zoksh.com', 'woocommerce'); ?></h3>
            <p><?php _e('Completes checkout via zoksh.com', 'woocommerce'); ?></p>

            <?php if ($this->is_valid_for_use()) : ?>

                <table class="form-table">
                    <?php $this->generate_settings_html(); ?>
                </table>
                <!--/.form-table-->

            <?php else : ?>
                <div class="inline error">
                    <p><strong><?php _e('Gateway Disabled', 'woocommerce'); ?></strong>: <?php _e('zoksh.com does not support your store currency.', 'woocommerce'); ?></p>
                </div>
            <?php endif;
        }

        function init_form_fields() { require_once( 'includes/setting_form_fields.php' ); }

        function sendHttpRequest($endpoint, $obj) 
        {
            $requestUrl = ($this->baseApiUrl).$endpoint;
            $content = json_encode($obj);
            $ts = time();
            $requestStr = $ts.$endpoint.$content;
            $sign = hash_hmac('sha256', $requestStr, $this->api_secret);

            $response = wp_remote_post( $requestUrl, array(
                'method'      => 'POST',
                'timeout'     => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => true,
                'headers'     => array(
                                    'Content-type: application/json',
                                    'ZOKSH-KEY: '.$this->api_key,
                                    'ZOKSH-TS: '.$ts,
                                    'ZOKSH-SIGN: '.$sign
                                ),
                'body'        => $content
                )
            );
            if(is_wp_error($response)){
                $error_message = $response->get_error_message();
                $error = new WP_Error( 'request_failed', 'Zoksh Request Failed', $error_message );
                throw new \Exception();
            }
            return $response;
        }

        function getTransactionStatus($tid)
        {
            $dataObj = (object)array('transaction' => $tid);
            $endpoint = 'v2/validate-payment';
            $response = $this->sendHttpRequest($endpoint, $dataObj);
            return $response;
        }

        function process_payment($order_id)
        {
            $order = wc_get_order($order_id);
            $redirect_url= $this->generate_zoksh_url($order);
            return array(
                'result' => 'success',
                'redirect' => $redirect_url
            );
        }

        function generate_zoksh_url($order)
        {
            if ($order->get_status() != 'completed' && get_post_meta($order->get_id(), 'Zoksh payment complete', true) != 'Yes') {
                $order->add_order_note('Customer is being redirected to zoksh...');
                $order->update_status('pending', 'Customer is being redirected to zoksh...');
            }

            $oid = $this->get_zoksh_oid($order);
            $failure = urlencode(esc_url_raw($order->get_cancel_order_url_raw()));
            $success = urlencode($this->get_return_url($order));

            $zoksh_adr = $this->redirectApiUrl."?order=MPO-".$oid."&success_url=".$success."&failure_url=".$failure;

            return $zoksh_adr;
        }

        function get_zoksh_oid($order)
        {
            if ($this->simple_total) {
                $amount = number_format($order->get_total(), 8, '.', '');
            } else if (wc_tax_enabled() && wc_prices_include_tax()) {
                $amount = number_format($order->get_total(), 8, '.', '');
            } else {
                $amount = number_format($order->get_total(), 8, '.', '');
                $amount += $order->get_total_tax();
            }

            $dataObj = (object)array(
                'amount' => $amount,
                'fiat' => $order->get_currency(),
                'label' => 'Zoksh Pay',
                'merchant' => (object)array(
                    'desc' => $amount.' '.$order->get_currency() .' payable for invoice number '.$this->invoice_prefix . $order->get_order_number(),
                    'extra' => $this->invoice_prefix . $order->get_order_number(),
                    'orderId' => $this->invoice_prefix . $order->get_order_number()
                ),
                'prefill' => (object)array(
                    'email' => $order->get_billing_email(),
                    'name' => $order->get_billing_first_name(),
                    'phone' => str_replace(array('( ', '-', ' ', ' )', '.'), '', $order->get_billing_phone())
                )
            );
            $endpoint = '/v2/order';
            $response = $this->sendHttpRequest($endpoint, $dataObj);

            $oid = $response->orderId;
            return $oid;
        }

        function is_valid_for_use() { return true; }

        function validate_response($response)
        {
            $order = false;
            $error_msg = null;

            $valid_order_id = str_replace($this->invoice_prefix, "", $response->merchantExtra->orderId);
            $order = new WC_Order($valid_order_id);

            if ($order !== false) {
                if($response->status != 'validated') {
                    $error_msg = "Transaction not validated yet";
                } elseif ($response->payment->token->fiat < $order->get_total()) {
                    $error_msg = "Amount received is less than the total!";
                }
            } else {
                $error_msg = "Could not find order info for order ";
            }

            if($error_msg) return false;

            $order->update_status('processing', 'Order is processing.');
            $order->add_order_note('Payment Completed: ' . wp_kses($_GET['transaction']));
            return true;
        }
    }
}