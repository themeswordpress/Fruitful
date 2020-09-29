<?php
/*
* Plugin Name: CCAvenue Payment Gateway for WooCommerce
* Description: CCAvenue Payment gateway for woocommerce based on standards
* Author: CCAvenue
* Version: 4.0
* Author URI: https://www.ccavenue.ae
* Requires at least: 4.7
* Tested up to: 5.4.2
* WC requires at least: 2.5
* WC tested up to: 4.3.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action('plugins_loaded', 'wc_gateway_ccavenue_init', 0);

function wc_gateway_ccavenue_init() {

	if ( !class_exists( 'WC_Payment_Gateway' ) ) return;
	
	/**
	 * CCAvenue Standard Payment Gateway
	 *
	 * Provides a CCAvenue Standard Payment Gateway.
	 *
	 * @class 		WC_Gateway_CCAvenue
	 * @extends		WC_Payment_Gateway
	 * @version		4.0
	 * @package		WooCommerce/Classes/Payment
	 * @author 		WooThemes
	 */
	class WC_Gateway_CCAvenue extends WC_Payment_Gateway {
	
		/** @var boolean Whether or not logging is enabled */
		public static $log_enabled = false;
	
		/** @var WC_Logger Logger instance */
		public static $log = false;
	
		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {
			$this->id                 = 'ccavenue';
			$this->has_fields         = false;
			$this->order_button_text  = __( 'Proceed to Pay', 'ccave' );
			$this->method_title       = __( 'CCAvenue', 'ccave' );
			$this->icon         	  = ' https://www.ccavenue.ae/images_shoppingcart/ccavenue_pay_options.gif "style="max-width:92%;max-height:100px;float:unset;margin-left:30px;display:block;';
			$this->method_description = sprintf( __( 'CCAvenue standard sends customers to CCAvenue MCPG to enter their payment information.', 'ccave' ), '<a href="' . admin_url( 'admin.php?page=wc-status' ) . '">', '</a>' );
			$this->supports           = array( 'products' );
	
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();
	
			// Define user set variables
			$this->title          = $this->get_option( 'title' );
			$this->description    = $this->get_option( 'description' );
			$this->testmode       = 'yes' === $this->get_option( 'testmode', 'no' );
			$this->debug          = 'yes' === $this->get_option( 'debug', 'no' );
			$this->merchant_id    = $this->get_option( 'merchant_id');
	            	$this->working_key    = $this->get_option('working_key');
	            	$this->access_code    = $this->get_option('access_code');
	            	$this->notify_url     = WC()->api_request_url( 'WC_Gateway_CCAvenue' );
	
			self::$log_enabled    = $this->debug;
			
			add_action('valid-ccavenue-request', array($this, 'successful_request'));
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
	
			include_once( 'includes/class-wc-gateway-ccavenue-ipn-handler.php' );
			new WC_Gateway_CCAvenue_IPN_Handler( $this->testmode, $this->working_key, $this );
			WC_Gateway_CCAvenue::log('Start Logging');
		}
	
		/**
		 * Logging method
		 * @param  string $message
		 */
		public static function log( $message ) {
			if ( self::$log_enabled ) {
				if ( empty( self::$log ) ) {
					self::$log = new WC_Logger();
				}
				self::$log->add( 'ccavenue', $message );
			}
		}
	
	
		/**
		 * Admin Panel Options
		 * - Options for bits like 'title' and availability on a country-by-country basis
		 *
		 * 
		 */
		public function admin_options() {
			echo '<h3>'.__('CCAvenue Payment Gateway', 'ccave').'</h3>';
			echo'<a href ="https://www.ccavenue.ae" target="_blank"><img src="https://www.ccavenue.ae/images_shoppingcart/ccavenue_logo_ae.png" alt="CCAvenue Logo" title="CCAvenue MCPG" /></a></p>';
			echo '<a class="support" style=" font-size:16px;font-family:Verdana, Geneva, sans-serif; color:#09F;" href="mailto:shoppingcart@ccavenue.com?subject=UAE%20Shopping%20Cart%20-%20Wordpress%205.0%20WooCommerce%203.6" alt="Contact Support" title="Contact Support"> '._('Contact Support').'</a>';
			echo '<p>'.__('CCAvenue is most popular payment gateway for online shopping in UAE').'</p>';
			echo '<table class="form-table">';
			$this -> generate_settings_html();
			echo '</table>';
		}
	
		/**
		 * Initialise Gateway Settings Form Fields
		 */
		public function init_form_fields() {
			$this->form_fields = include( 'includes/settings-ccavenue.php' );
		}
		
			
		/**
		 * Get the CCAvenue request URL for an order
		 * @param  WC_Order  $order
		 * @param  boolean $sandbox
		 * @return string
		 */
		public function get_request_url( $order, $sandbox ) {
			if ( $sandbox == true ) {
				return 'https://secure.ccavenue.ae/transaction/transaction.do?command=initiateTransaction';	
			} else {
				return 'https://secure.ccavenue.ae/transaction/transaction.do?command=initiateTransaction';	
			}
		}
		
		/**
         	*  There are no payment fields for CCAvenue, but we want to show the description if set.
         	**/
	        function payment_fields(){
        		if($this -> description) echo wpautop(wptexturize($this -> description));
        	}
		
	        /**
	         * Receipt Page
	         **/
	        public function receipt_page( $order_id ) {
				$order = new WC_Order($order_id);
				
				echo $this -> generate_ccavenue_form($order_id);
	        }
	       

	        /**
	         * Generate CCAvenue button link
	         **/
	  public function generate_ccavenue_form($order_id){
	        	WC_Gateway_CCAvenue::log('Generating Form');
			global $woocommerce;
			include_once( 'includes/crypto-ccavenue.php' );
			$order = new WC_Order($order_id);
			$orderAmount = $this->get_grand_total($order->get_total(), array('currency' => $order->get_currency()));
			$ccavenue_args = array(
			        'merchant_id'      => $this -> merchant_id,
			        'amount'           => ( WC()->version < '3.0.0' ) ? $order->order_total : $orderAmount,
			        'order_id'         => $order_id,
			        'redirect_url'     => $this->notify_url,
			        'cancel_url'       => $this->notify_url,
			        'billing_name'     => (( WC()->version < '3.0.0' ) ? $order->billing_first_name : $order->get_billing_first_name()) .' '. (( WC()->version < '3.0.0' ) ? $order->billing_last_name : $order->get_billing_last_name()),
			        'billing_address'  => trim(( WC()->version < '3.0.0' ) ? $order->billing_address_1.($order->billing_address_2 != '' ? ', '.$order->billing_address_2 : '') : $order->get_billing_address_1().($order->get_billing_address_2() != '' ? ', '.$order->get_billing_address_2() : ''), ','),
			        'billing_country'  => wc()->countries -> countries [( WC()->version < '3.0.0' ) ? $order->billing_country : $order->get_billing_country()],
			        'billing_state'    => ( WC()->version < '3.0.0' ) ? $order->billing_state : $order->get_billing_state(),
			        'billing_city'     => ( WC()->version < '3.0.0' ) ? $order->billing_city : $order->get_billing_city(),
			        'billing_zip'      => ( WC()->version < '3.0.0' ) ? $order->billing_postcode : $order->get_billing_postcode(),
			        'billing_tel'      => ( WC()->version < '3.0.0' ) ? $order->billing_phone : $order->get_billing_phone(),
			        'billing_email'    => ( WC()->version < '3.0.0' ) ? $order->billing_email : $order->get_billing_email(),
			        'delivery_name'    => (( WC()->version < '3.0.0' ) ? $order->shipping_first_name : $order->get_shipping_first_name()) .' '. (( WC()->version < '3.0.0' ) ? $order->shipping_last_name : $order->get_shipping_last_name()),
			        'delivery_address' => ( WC()->version < '3.0.0' ) ? $order->shipping_address_1.($order->shipping_address_2 != '' ? ', '.$order->shipping_address_2 : '') : $order->get_shipping_address_1().($order->get_shipping_address_2() != '' ? ', '.$order->get_shipping_address_2() : ''),
			        'delivery_country' => ( WC()->version < '3.0.0' ) ? $order->shipping_country : $order->get_shipping_country(),
			        'delivery_state'   => ( WC()->version < '3.0.0' ) ? $order->shipping_state : $order->get_shipping_state(),
			        'delivery_tel'     => '',
			        'delivery_city'    => ( WC()->version < '3.0.0' ) ? $order->shipping_city : $order->get_shipping_city(),
			        'delivery_zip'     => ( WC()->version < '3.0.0' ) ? $order->shipping_postcode : $order->get_shipping_postcode(),
			        'language'         => 'EN',
			        'currency'         => get_woocommerce_currency()
	            );

			foreach($ccavenue_args as $param => $value) {
				$paramsJoined[] = "$param=$value";
			}
		
			$merchant_data   = implode('&', $paramsJoined);
			$encrypted_data = encrypt($merchant_data, $this -> working_key);
			$ccavenue_args_array   = array();
			$ccavenue_args_array[] = "<input type='hidden' name='encRequest' value='$encrypted_data'/>";
			$ccavenue_args_array[] = "<input type='hidden' name='access_code' value='{$this->access_code}'/>";
			
			wc_enqueue_js( '$.blockUI({
				message: "' . esc_js( __( 'Thank you for your order. We are now redirecting you to CCAvenue to make payment.', 'ccave' ) ) . '",
				baseZ: 99999,
				overlayCSS:
				{
					background: "#fff",
					opacity: 0.6
				},
				css: {
					padding:        "20px",
					zindex:         "9999999",
					textAlign:      "center",
					color:          "#555",
					border:         "3px solid #aaa",
					backgroundColor:"#fff",
					cursor:         "wait",
					lineHeight:     "24px",
			        }
				});
			jQuery("#submit_ccavenue_payment_form").click();' );
	
			$form = '<form action="' . esc_url( $this->get_request_url( $order, $this->testmode ) ) . '" method="post" name="redirect" id="ccavenue_payment_form" target="_top">
				' . implode( '', $ccavenue_args_array ) . '
				<!-- Button Fallback -->
				<script type="text/javascript">
				function formsubmit()
                {
                  document.redirect.submit();   
                }
                setTimeout("formsubmit()", 3000);
				</script>
				</form>';
			return $form;
		}
	
		public function get_grand_total( $price, $args = array() ) {
			$args = apply_filters(
				'wc_price_args', wp_parse_args(
					$args, array(
						'ex_tax_label'       => false,
						'currency'           => '',
						'decimal_separator'  => wc_get_price_decimal_separator(),
						'thousand_separator' => wc_get_price_thousand_separator(),
						'decimals'           => wc_get_price_decimals(),
						'price_format'       => get_woocommerce_price_format(),
					)
				)
			);

			$unformatted_price = $price;
			$negative          = $price < 0;
			$price             = apply_filters( 'raw_woocommerce_price', floatval( $negative ? $price * -1 : $price ) );

			if ( apply_filters( 'woocommerce_price_trim_zeros', false ) && $args['decimals'] > 0 ) {
				$price = wc_trim_zeros( $price );
			}

			$formatted_price = ( $negative ? '-' : '' ) . $price;

			return $formatted_price;
		}
	
		/**
		 * Process the payment and return the result
		 *
		 * @param int $order_id
		 * @return array
		 */
		public function process_payment( $order_id ) {	
			$order          = wc_get_order( $order_id );
	
			return array(
				'result'   => 'success',
				'redirect' => $order->get_checkout_payment_url( true )
			);
		}
		
		// get all pages
		function get_pages($title = false, $indent = true) {
			$wp_pages = get_pages('sort_column=menu_order');
			$page_list = array();
			if ($title) $page_list[] = $title;
			foreach ($wp_pages as $page) {
				$prefix = '';
				// show indented child pages?
				if ($indent) {
					$has_parent = $page->post_parent;
					while($has_parent) {
						$prefix .=  ' - ';
						$next_page = get_page($has_parent);
						$has_parent = $next_page->post_parent;
					}
				}
				// add to page list array array
				$page_list[$page->ID] = $prefix . $page->post_title;
			}
			return $page_list;
		}
	}
		
	/**
	* Add the Gateway to WooCommerce
	**/
	function woocommerce_add_wc_gateway_ccavenue($methods) {
		$methods[] = 'WC_Gateway_CCAvenue';
		return $methods;
	}
	
	add_filter('woocommerce_payment_gateways', 'woocommerce_add_wc_gateway_ccavenue' );
}
