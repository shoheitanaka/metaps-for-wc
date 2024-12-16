<?php
/**
 * WC_Gateway_Metaps_CC_Token class file.
 *
 * @package Metaps_For_WC
 */

use ArtisanWorkshop\PluginFramework\v2_0_12 as Framework;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WC_Gateway_Metaps_CC_Token
 *
 * This class handles the Metaps credit card token payment gateway.
 */
class WC_Gateway_Metaps_CC_Token extends WC_Payment_Gateway_CC {

	/**
	 * Framework.
	 *
	 * @var stdClass
	 */
	public $jp4wc_framework;

	/**
	 * Set metaps request class
	 *
	 * @var stdClass
	 */
	public $metaps_request;

	/**
	 * Description.
	 *
	 * @var string
	 */
	public $description;

	/**
	 * Payment action.
	 *
	 * @var string
	 */
	public $paymentaction;

	/**
	 * User ID Payment.
	 *
	 * @var string
	 */
	public $user_id_payment;

	/**
	 * Number of payments.
	 *
	 * @var array
	 */
	public $number_of_payments;

	/**
	 * IP Code.
	 *
	 * @var string
	 */
	public $ip_code;

	/**
	 * Pass Code.
	 *
	 * @var string
	 */
	public $pass_code;

	/**
	 * Payment Time Text.
	 *
	 * @var string
	 */
	public $payment_time_text;

	/**
	 * Array of number of payments.
	 *
	 * @var array
	 */
	public $array_number_of_payments = array(
		'100' => '1 time',
		'80'  => 'Revolving payment',
		'3'   => '3 times',
		'5'   => '5 times',
		'6'   => '6 times',
		'10'  => '10 times',
		'12'  => '12 times',
		'15'  => '15 times',
		'18'  => '18 times',
		'20'  => '20 times',
		'24'  => '24 times',
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id           = 'metaps_cc_token';
		$this->has_fields   = true;
		$this->method_title = esc_html__( 'Metaps Credit Card Token', 'metaps-for-woocommerce' );

		// Create plugin fields and settings.
		$this->init_form_fields();
		$this->init_settings();
		$this->method_description = esc_html__( 'Metaps Credit Card Token Payment Gateway.', 'metaps-for-woocommerce' );
		$this->supports           = array(
			'subscriptions',
			'products',
			'subscription_cancellation',
			'subscription_reactivation',
			'subscription_suspension',
			'subscription_amount_changes',
			'subscription_payment_method_change_admin',
			'subscription_date_changes',
			'multiple_subscriptions',
			'default_credit_card_form',
			'refunds',
		);

		$this->jp4wc_framework = new Framework\JP4WC_Framework();

		include_once 'includes/class-wc-gateway-metaps-request.php';
		$this->metaps_request = new WC_Gateway_Metaps_Request();

		// When no save setting error at chackout page.
		if ( is_null( $this->title ) ) {
			$this->title = esc_html__( 'Please set this payment at Control Panel! ', 'metaps-for-woocommerce' ) . esc_html( $this->method_title );
		}

		// Get setting values.
		foreach ( $this->settings as $key => $val ) {
			$this->$key = $val;
		}
		// Actions hook.
		add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'metaps_cc_token_wp_enqueue_script' ) );
		add_action( 'woocommerce_order_status_completed', array( $this, 'metaps_order_status_completed_to_capture_token' ) );
	}

	/**
	 * Initialize Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'            => array(
				'title'       => __( 'Enable/Disable', 'metaps-for-woocommerce' ),
				'label'       => __( 'Enable metaps PAYMENT Credit Card Token Payment', 'metaps-for-woocommerce' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'title'              => array(
				'title'       => __( 'Title', 'metaps-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'metaps-for-woocommerce' ),
				'default'     => __( 'Credit Card', 'metaps-for-woocommerce' ),
			),
			'description'        => array(
				'title'       => __( 'Description', 'metaps-for-woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'metaps-for-woocommerce' ),
				'default'     => __( 'Pay with your credit card via metaps PAYMENT.', 'metaps-for-woocommerce' ),
			),
			'order_button_text'  => array(
				'title'       => __( 'Order Button Text', 'metaps-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the description which the user sees during checkout.', 'metaps-for-woocommerce' ),
				'default'     => __( 'Proceed to metaps PAYMENT Credit Card', 'metaps-for-woocommerce' ),
			),
			'ip_code'            => array(
				'title'       => __( 'IP Code', 'metaps-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter IP Code here.', 'metaps-for-woocommerce' ),
			),
			'pass_code'          => array(
				'title'       => __( 'IP Password', 'metaps-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter IP Password here', 'metaps-for-woocommerce' ),
			),
			'paymentaction'      => array(
				'title'       => __( 'Payment Action', 'metaps-for-woocommerce' ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'description' => __( 'Choose whether you wish to capture funds immediately or authorize payment only.', 'metaps-for-woocommerce' ),
				'default'     => 'sale',
				'desc_tip'    => true,
				'options'     => array(
					'sale'          => __( 'Capture', 'metaps-for-woocommerce' ),
					'authorization' => __( 'Authorize', 'metaps-for-woocommerce' ),
				),
			),
			'user_id_payment'    => array(
				'title'       => __( 'User ID Payment', 'metaps-for-woocommerce' ),
				'id'          => 'wc-userid-payment',
				'type'        => 'checkbox',
				'label'       => __( 'User ID Payment', 'metaps-for-woocommerce' ),
				'default'     => 'yes',
				'description' => __( 'Use the payment method of User ID payment.', 'metaps-for-woocommerce' ),
			),
			'payment_time_text'  => array(
				'title'       => __( 'Payment Time Text', 'metaps-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the description which the user sees during checkout.', 'metaps-for-woocommerce' ),
				'default'     => __( 'Payment Times : ', 'metaps-for-woocommerce' ),
			),
			'number_of_payments' => array(
				'title'       => __( 'Number of payments', 'metaps-for-woocommerce' ),
				'type'        => 'multiselect',
				'class'       => 'wc-number-select',
				'description' => __( 'Choose whether you wish to capture funds immediately or authorize payment only.', 'metaps-for-woocommerce' ),
				'desc_tip'    => true,
				'options'     => $this->array_number_of_payments,
			),
			'debug'              => array(
				'title'       => __( 'Debug Mode', 'metaps-for-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable Debug Mode', 'metaps-for-woocommerce' ),
				'default'     => 'no',
				'description' => __( 'Save debug data using WooCommerce logging.', 'metaps-for-woocommerce' ),
			),
		);
	}
	/**
	 * UI - Payment page fields for metaps PAYMENT Payment.
	 */
	public function payment_fields() {
		// Description of payment method from settings.
		if ( $this->description ) { ?>
			<p><?php echo esc_html( $this->description ); ?></p>
					<?php
		}
		$user                 = wp_get_current_user();
		$number_payment_array = $this->form_fields['number_of_payments']['options'];
		$metaps_user_id       = get_user_meta( $user->ID, '_metaps_user_id', true );
		if ( 'yes' === $this->user_id_payment && '' !== $metaps_user_id && is_user_logged_in() ) {
			?>
			<input type="radio" name="user_id_payment" value="yes" checked="checked" onclick="document.getElementById('metaps-new-info').style.display='none'"><span style="padding-left:15px;"><?php echo esc_html__( 'Use Stored Card.', 'metaps-for-woocommerce' ); ?></span><br />
			<br />
			<input type="radio" name="user_id_payment" value="no" onclick="document.getElementById('metaps-new-info').style.display='block'"><span style="padding-left:15px;"><?php echo esc_html__( 'Use New Card.', 'metaps-for-woocommerce' ); ?></span><br />
				<?php
		}
		if ( 'yes' === $this->user_id_payment && '' !== $metaps_user_id && is_user_logged_in() ) {
			echo '<div id="metaps-new-info" style="display:none">';
		} else {
			echo '
			<!-- Show input boxes for new data -->
			<div id="metaps-new-info">';
		}
		$cc_form           = new WC_Payment_Gateway_CC();
		$cc_form->id       = $this->id;
		$cc_form->supports = $this->supports;
		$cc_form->form();
		echo '</div>';
		// Show the number of payments.
		$metaps_setting = get_option( 'woocommerce_metaps_cc_token_settings' );
		if ( isset( $metaps_setting['payment_time_text'] ) ) {
			echo '<label>' . esc_html( $metaps_setting['payment_time_text'] ) . '</label>';
		}
		if ( ! empty( $this->number_of_payments ) ) {
			echo '<select name="number_of_payments">';
			foreach ( $this->number_of_payments as $key => $value ) {
				echo '<option value="' . esc_attr( $value ) . '">' . esc_html( $number_payment_array[ $value ] ) . '</option>';
			}
			echo '</select>';
		}
		echo wp_kses_post( $this::mataps_javascript_code() );
	}

	/**
	 * Outputs the JavaScript code for handling Metaps payment tokenization.
	 */
	private function mataps_javascript_code() {
		$message = '
<script language="javascript">
document.getElementById("metaps_cc_token-card-cvc").addEventListener("input", metapspaymentToken);
document.getElementById("metaps_cc_token-card-number").addEventListener("input", metapspaymentToken);
document.getElementById("metaps_cc_token-card-expiry").addEventListener("input", metapspaymentToken);
var metapspaymentToken = function () {
	if(jQuery(":radio[name=payment_method]:checked").val() != "metaps_cc_token"){return;}
	if(jQuery(":radio[name="user_id_payment"]:checked").val() == "yes"){return;}
	var cr = document.getElementById("metaps_cc_token-card-number").value ;
	cr = cr.replace(/ /g, "");
	var cs = document.getElementById("metaps_cc_token-card-cvc").value ;
	var exp_my = document.getElementById("metaps_cc_token-card-expiry").value ;
	exp_my = exp_my.replace(/ /g, "");
	exp_my = exp_my.replace("/", "");
	var exp_m = exp_my.substr(0,2);
	var exp_y = exp_my.substr(2).substr(-2);
	jQuery("#place_order").prop("disabled", true);
	if(metapspayment.validateCardNumber(cr) && metapspayment.validateExpiry(exp_m,exp_y) && metapspayment.validateCSC(cs)){
		jQuery("#metaps_cc_token_id").val("");
		metapspayment.setTimeout(20000);
		metapspayment.setLang("ja");
		metapspayment.createToken({number:cr,csc:cs,exp_m:exp_m,exp_y:exp_y},metapspaymentResponseHandler);
	}
}
//}, false);
var metapspaymentResponseHandler = function(status, response) {
	var token_id = jQuery("#metaps_cc_token_id");
	if (response.error) {
	var user_id_payment = jQuery("input[name="user_id_payment"]:checked").val();
	} else {
	token_id.val(response.id);
	document.getElementById("metaps_cc_token_crno").value = response.crno ;
	document.getElementById("metaps_cc_token_r_exp_y").value = response.exp_y ;
	document.getElementById("metaps_cc_token_r_exp_m").value = response.exp_m ;
	}
	if(token_id.val() != ""){
	jQuery("#place_order").prop("disabled", false);
	}
}

jQuery(function($){
	$("#place_order").focus(function (){
		$("#metaps_cc_token-card-number").prop("disabled", true);
		$("#metaps_cc_token-card-expiry").prop("disabled", true);
		$("#metaps_cc_token-card-cvc").prop("disabled", true);
	});
	$("#place_order").blur(function (){
		$("#metaps_cc_token-card-number").prop("disabled", false);
		$("#metaps_cc_token-card-expiry").prop("disabled", false);
		$("#metaps_cc_token-card-cvc").prop("disabled", false);
	});
	$(":radio[name=payment_method]").on("change", function(){
		var checked = $(this).prop("checked");
		var id = this.id;
		$("#metaps_cc_token-card-number").val("");
		$("#metaps_cc_token-card-expiry").val("");
		$("#metaps_cc_token-card-cvc").val("");
		$("#metaps_cc_token_id").val("");
		if (id == "payment_method_metaps_cc_token"){
			if(user_id_payment = $("input[name="user_id_payment"]:checked").val() == "no") {
				$("#place_order").prop("disabled", true);
			}
		} else {
			$("#place_order").prop("disabled", false);
		}
	});
	$(":radio[name="user_id_payment"]").on("change", function(){
		$("#metaps_cc_token-card-number").val("");
		$("#metaps_cc_token-card-expiry").val("");
		$("#metaps_cc_token-card-cvc").val("");
		$("#metaps_cc_token_id").val("");
		if ($(this).val() == "new") {
			$("#place_order").prop("disabled", true);
		} else {
			$("#place_order").prop("disabled", false);
		}
	});
	$( document.body ).on( "checkout_error", function() {
		if ( $(":radio[name=payment_method]:checked").val() == "metaps_cc_token"){
			selectcard = $(":radio[name="user_id_payment"]:checked").val()
			if ( selectcard == null || selectcard == "no"){
				$("#metaps_cc_token_id").val("");
				$("#metaps_cc_token-card-number").val("");
				$("#metaps_cc_token-card-expiry").val("");
				$("#metaps_cc_token-card-cvc").val("");
				$("#metaps_cc_token-card-number").prop("disabled", false);
				$("#metaps_cc_token-card-expiry").prop("disabled", false);
				$("#metaps_cc_token-card-cvc").prop("disabled", false);
			}
		}
	});
});

</script>
<input type="hidden" name="metaps_cc_token_crno" id="metaps_cc_token_crno"/>
<input type="hidden" name="metaps_cc_token_r_exp_y" id="metaps_cc_token_r_exp_y"/>
<input type="hidden" name="metaps_cc_token_r_exp_m" id="metaps_cc_token_r_exp_m"/>
<input type="hidden" name="metaps_cc_token_id" id="metaps_cc_token_id"/>";
		';
		echo esc_js( $message );
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int  $order_id Order ID.
	 * @param bool $subscription Subscription.
	 * @return array
	 * @throws Exception If payment processing fails.
	 */
	public function process_payment( $order_id, $subscription = false ) {
		$metaps_settings = get_option( 'woocommerce_metaps_settings' );
		$prefix_order    = $metaps_settings['prefixorder'];

		$order = wc_get_order( $order_id );
		$user  = wp_get_current_user();

		// Setting $send_data.
		$setting_data = array();
		if ( isset( $user->ID ) && 0 !== $user->ID ) {
			$customer_id = $prefix_order . $user->ID;
		} else {
			$customer_id = $prefix_order . $order_id . ' - user';
		}

		// Set User id.
		if ( 'yes' === $this->user_id_payment && is_user_logged_in() ) {
			$setting_data['ip_user_id'] = $customer_id;
		}

		$setting_data['pass'] = $this->pass_code;
		// User ID payment check.
		$setting_data['store'] = '51';
		if ( 'yes' === $this->user_id_payment && 'yes' === $this->get_post( 'user_id_payment' ) && is_user_logged_in() ) {
			$setting_data['store'] = null;
		}

		$setting_data['ip'] = $this->ip_code;
		if ( 'sale' === $this->paymentaction ) {
			$setting_data['kakutei'] = '1';// capture = 1.
		} else {
			$setting_data['kakutei'] = '0';// auth = 0.
		}
		$setting_data['lang'] = '0';// Use Language 0 = Japanese, 1 = English.
		$setting_data['sid']  = $prefix_order . $order_id;

		// Number of Payment check.
		$number_of_payments = $this->get_post( 'number_of_payments' );
		if ( isset( $number_of_payments ) ) {
			if ( 21 === $number_of_payments || 80 === $number_of_payments ) {
				$setting_data['paymode'] = $number_of_payments;
			} elseif ( 100 === $number_of_payments ) {
				$setting_data['paymode'] = 10;
			} else {
				$setting_data['paymode'] = 61;
				$setting_data['incount'] = $number_of_payments;
			}
		}

		$setting_data['token'] = $this->get_post( 'metaps_cc_token_id' );
		if ( isset( $setting_data['store'] ) ) {// When not use user id payment.
			$connect_url = METAPS_CS_SALES_URL;
			$order->add_order_note( __( 'Finished to send payment data to metaps PAYMENT . ', 'metaps-for-woocommerce' ) );

			$response = $this->metaps_request->metaps_post_request( $order, $connect_url, $setting_data, $this->debug, 'yes' );

			if ( isset( $response[0] ) && substr( $response[0], 0, 2 ) === 'OK' ) {
				if ( isset( $response[1] ) ) {
					$order->set_transaction_id( $response[1] );
					$order->save();
				}

				// Update user id.
				if ( 'yes' === $this->user_id_payment ) {
					update_user_meta( $user->ID, '_metaps_user_id', $customer_id );
				}

				// Mark as processing.
				$order->update_status( 'processing', __( 'Payment received, awaiting fulfilment', 'metaps-for-woocommerce' ) );
				// Reduce stock levels.
				wc_reduce_stock_levels( $order_id );
				// Remove cart.
				WC()->cart->empty_cart();

				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);
			} else {
				$error_message = 'This order is cancelled, because of Payment error . ' . mb_convert_encoding( $response[2], 'UTF - 8', 'sjis' );
				$order->update_status( 'cancelled', __( 'This order is cancelled, because of Payment error: ', 'metaps-for-woocommerce' ) . $error_message );

				$front_error_message  = __( 'Credit card payment failed . ', 'metaps-for-woocommerce' );
				$front_error_message .= __( 'Please try again . ', 'metaps-for-woocommerce' );
				throw new Exception( esc_html( $front_error_message ) );
			}
		} else { // When use user id payment.
			$setting_data['IP_USER_ID'] = get_user_meta( $user->ID, '_metaps_user_id', true );
			$connect_url                = METAPS_CC_SALES_USER_URL;
			$response                   = $this->metaps_request->metaps_post_request( $order, $connect_url, $setting_data, $this->debug, 'yes' );
			if ( isset( $response[0] ) && substr( $response[0], 0, 2 ) === 'OK' ) {
				update_user_meta( $user->ID, '_metaps_user_id', $customer_id );
				$order->add_order_note( __( 'Finished to send payment data to metaps PAYMENT . ', 'metaps-for-woocommerce' ) );
				// Reduce stock levels.
				wc_reduce_stock_levels( $order_id );
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);
			} else {
				$error_message = __( 'This order is cancelled, because of Payment error: ', 'metaps-for-woocommerce' ) . mb_convert_encoding( $response[2], 'UTF - 8', 'sjis' );
				$order->update_status( 'cancelled', __( 'This order is cancelled, because of Payment error: ', 'metaps-for-woocommerce' ) . $error_message );
				$front_error_message = __( 'Credit card payment failed . Please try again . ', 'metaps-for-woocommerce' );
				throw new Exception( esc_html( $front_error_message ) );
			}
		}
	}

	/**
	 * Validate input fields
	 *
	 * @return bool
	 */
	public function validate_fields() {
		$token           = $this->get_post( 'metaps_cc_token_id' );
		$user_id_payment = $this->get_post( 'user_id_payment' );
		if ( isset( $user_id_payment ) && empty( $token ) && 'yes' !== $user_id_payment ) {
			wc_add_notice( __( 'Payment error:', 'metaps-for-woocommerce' ) . __( 'Enter your card details', 'metaps-for-woocommerce' ), 'error' );
			return false;
		}

		// Get the WC_Checkout object.
		$checkout = WC()->checkout();

		$billing_phone = $checkout->get_value( 'billing_phone' );
		$billing_email = $checkout->get_value( 'billing_email' );

		if ( empty( $billing_phone ) && empty( $billing_email ) ) {
			wc_add_notice( __( 'A phone number or email address is required for credit card payments . ', 'metaps-for-woocommerce' ), 'error' );
			return false;
		}
		return true;
	}

	/**
	 * Refund a charge
	 *
	 * @param  int    $order_id Order ID.
	 * @param  float  $amount Amount.
	 * @param  string $reason Reason.
	 * @return mixed bool and WP_Error
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return false;
		} elseif ( $amount !== $order->order_total ) {
			$order->add_order_note( __( 'Auto refund must total only . ', 'metaps-for-woocommerce' ) . __( 'if you need, please contact to metaps PAYMENT Support . ', 'metaps-for-woocommerce' ) );
			return new WP_Error( 'metaps_refund_error', __( 'Auto refund must total only . ', 'metaps-for-woocommerce' ) . __( 'if you need, please contact to metaps PAYMENT Support . ', 'metaps-for-woocommerce' ) );
		}
		$metaps_settings = get_option( 'woocommerce_metaps_settings' );
		$prefix_order    = $metaps_settings['prefixorder'];

		$refund_connect_url = METAPS_CC_SALES_CANCEL_URL;
		$cansel_connect_url = METAPS_CC_SALES_REFUND_URL;

		$status       = $order->get_status();
		$data['IP']   = $this->ip_code;
		$data['PASS'] = $this->pass_code;
		$data['SID']  = $prefix_order . $order_id;
		if ( 'completed' === $status ) {
			$response = $this->metaps_request->metaps_request( $data, $cansel_connect_url, $order, $this->debug );
			if ( isset( $response ) && substr( $response, 0, 10 ) === 'C - CHECK:OK' ) {
				$order->add_order_note( __( 'This order is refunded now at metaps PAYMENT . ', 'metaps-for-woocommerce' ) );
				return true;
			} elseif ( isset( $response ) && substr( $response, 0, 10 ) === 'C - CHECK:NG' ) {
				if ( substr( $response, -3, 1 ) === '2' ) {
					$order->add_order_note( __( 'This order has already auth canselled . ', 'metaps-for-woocommerce' ) . __( 'if you need, please contact to metaps PAYMENT Support . ', 'metaps-for-woocommerce' ) );
				} elseif ( substr( $response, -3, 1 ) === '3' ) {
					$order->add_order_note( __( 'This order has completed . ', 'metaps-for-woocommerce' ) . __( 'if you need, please contact to metaps PAYMENT Support . ', 'metaps-for-woocommerce' ) );
				} elseif ( substr( $response, -3, 1 ) === '4' ) {
					$order->add_order_note( __( 'This order has already canselled . ', 'metaps-for-woocommerce' ) . __( 'if you need, please contact to metaps PAYMENT Support . ', 'metaps-for-woocommerce' ) );
				}
				return new WP_Error( 'metaps_refund_error', __( 'Error has happened . ', 'metaps-for-woocommerce' ) . __( 'if you need, please contact to metaps PAYMENT Support . ', 'metaps-for-woocommerce' ) );
			} elseif ( isset( $response ) && substr( $response, 0, 10 ) === 'C - CHECK:TO' ) {
				$order->add_order_note( __( 'Expired . Status not changed . ', 'metaps-for-woocommerce' ) . __( 'if you need, please contact to metaps PAYMENT Support . ', 'metaps-for-woocommerce' ) );
				return new WP_Error( 'metaps_refund_error', __( 'Error has happened . ', 'metaps-for-woocommerce' ) . __( 'if you need, please contact to metaps PAYMENT Support . ', 'metaps-for-woocommerce' ) );
			} elseif ( isset( $response ) && substr( $response, 0, 10 ) === 'C - CHECK:ER' ) {
				$order->add_order_note( __( 'Error has happend . Status not changed . ', 'metaps-for-woocommerce' ) . __( 'if you need, please contact to metaps PAYMENT Support . ', 'metaps-for-woocommerce' ) );
				return new WP_Error( 'metaps_refund_error', __( 'Error has happened . ', 'metaps-for-woocommerce' ) . __( 'if you need, please contact to metaps PAYMENT Support . ', 'metaps-for-woocommerce' ) );
			}
		} else {
			if ( 'sale' === $this->paymentaction ) {
				$response = $this->metaps_request->metaps_request( $data, $cansel_connect_url, $order, $this->debug );
			} else {
				$response = $this->metaps_request->metaps_request( $data, $refund_connect_url, $order, $this->debug );
			}
			if ( isset( $response ) && substr( $response, 0, 10 ) === 'C - CHECK:OK' ) {
				$order->add_order_note( __( 'This order is refunded now at metaps PAYMENT . ', 'metaps-for-woocommerce' ) );
				return true;
			} elseif ( isset( $response ) && substr( $response, 0, 10 ) === 'C - CHECK:NG' ) {
				if ( substr( $response, -3, 1 ) === '1' ) {
					$order->add_order_note( __( 'This order is authorized now . ', 'metaps-for-woocommerce' ) . __( 'if you need, please contact to metaps PAYMENT Support . ', 'metaps-for-woocommerce' ) );
				} elseif ( substr( $response, -3, 1 ) === '2' ) {
					$order->add_order_note( __( 'This order has already auth canselled . ', 'metaps-for-woocommerce' ) . __( 'if you need, please contact to metaps PAYMENT Support . ', 'metaps-for-woocommerce' ) );
				} elseif ( substr( $response, -3, 1 ) === '3' ) {
					$order->add_order_note( __( 'This order has completed . ', 'metaps-for-woocommerce' ) . __( 'if you need, please contact to metaps PAYMENT Support . ', 'metaps-for-woocommerce' ) );
				} elseif ( substr( $response, -3, 1 ) === '4' ) {
					$order->add_order_note( __( 'This order has already canselled . ', 'metaps-for-woocommerce' ) . __( 'if you need, please contact to metaps PAYMENT Support . ', 'metaps-for-woocommerce' ) );
				}
				return new WP_Error( 'metaps_refund_error', __( 'Error has happened . ', 'metaps-for-woocommerce' ) . __( 'if you need, please contact to metaps PAYMENT Support . ', 'metaps-for-woocommerce' ) );
			} elseif ( isset( $response ) && substr( $response, 0, 10 ) === 'C - CHECK:TO' ) {
				$order->add_order_note( __( 'Expired . Status not changed . ', 'metaps-for-woocommerce' ) . __( 'if you need, please contact to metaps PAYMENT Support . ', 'metaps-for-woocommerce' ) );
				return new WP_Error( 'metaps_refund_error', __( 'Error has happened . ', 'metaps-for-woocommerce' ) . __( 'if you need, please contact to metaps PAYMENT Support . ', 'metaps-for-woocommerce' ) );
			} elseif ( isset( $response ) && substr( $response, 0, 10 ) === 'C - CHECK:ER' ) {
				$order->add_order_note( __( 'Error has happend . Status not changed . ', 'metaps-for-woocommerce' ) . __( 'if you need, please contact to metaps PAYMENT Support . ', 'metaps-for-woocommerce' ) );
				return new WP_Error( 'metaps_refund_error', __( 'Error has happened . ', 'metaps-for-woocommerce' ) . __( 'if you need, please contact to metaps PAYMENT Support . ', 'metaps-for-woocommerce' ) );
			}
		}
	}

	/**
	 * Get post data if set
	 *
	 * @param  string $name Name.
	 * @return string
	 */
	private function get_post( $name ) {
		$checkout = WC()->checkout();
		if ( $checkout ) {
			return $checkout->get_value( $name );
		}
		return null;
	}

	/**
	 * Set token Metaps Token JavaScript.
	 */
	public function metaps_cc_token_wp_enqueue_script() {
		if ( is_checkout() ) {
			wp_enqueue_script(
				'metaps_token_script',
				'// www.paydesign.jp/settle/token/metapsToken-min.js',
				array(),
				METAPS_FOR_WC_VERSION,
				false
			);
		}
	}

	/**
	 * Update Sale from Auth to Paydesign
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return bool
	 * @throws Exception If capturing the token fails.
	 */
	public function metaps_order_status_completed_to_capture_token( $order_id ) {
		global $woocommerce;
		$payment_setting = new WC_Gateway_METAPS_CC_TOKEN();

		if ( 'sale' !== $payment_setting->paymentaction ) {
			$metaps_setting  = get_option( 'woocommerce_metaps_cc_token_settings' );
			$metaps_settings = get_option( 'woocommerce_metaps_settings' );
			$prefix_order    = $metaps_settings['prefixorder'];

			$connect_url = METAPS_CC_SALES_COMP_URL;
			$order       = wc_get_order( $order_id );
			if ( $order && 'metaps_cc_token' === $order->payment_method ) {
				$data['IP']  = $this->ip_code;
				$data['SID'] = $prefix_order . $order_id;

				$response = $this->metaps_request->metaps_request( $data, $connect_url, $order, $this->debug );
				if ( isset( $response[0] ) && substr( $response[0], 0, 2 ) === 'OK' ) {
					return true;
				} elseif ( isset( $response ) && substr( $response, 0, 10 ) === 'C-CHECK:NG' ) {
					if ( substr( $response, -3, 1 ) === '3' ) {
						if ( $order->get_status() !== 'completed' ) {
							// Payment complete.
							$order->payment_complete();
						} elseif ( 'completed' === $order->get_status() && 'sale' === $payment_setting->paymentaction ) {
							$order->add_order_note( __( 'This order has already completed.', 'metaps-for-woocommerce' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-woocommerce' ) );
						}
						return true;
					} elseif ( substr( $response, -3, 1 ) === '2' ) {
						$order->add_order_note( __( 'This order has already auth canselled.', 'metaps-for-woocommerce' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-woocommerce' ) );
					} elseif ( substr( $response, -3, 1 ) === '4' ) {
						$order->add_order_note( __( 'This order has already canselled.', 'metaps-for-woocommerce' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-woocommerce' ) );
					}
					add_filter(
						'woocommerce_email_actions',
						function ( $email_actions ) {
							unset( $email_actions['woocommerce_order_status_completed'] );
							return $email_actions;
						},
						1
					);
					$update_post_data = array(
						'ID'          => $order_id,
						'post_status' => 'wc-processing',
					);
					wp_update_post( $update_post_data );
				} elseif ( isset( $response ) && substr( $response, 0, 10 ) === 'C-CHECK:ER' ) {
					$order->add_order_note( __( 'Error has happend. Status not changed.', 'metaps-for-woocommerce' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-woocommerce' ) );
				}
				return false;
			} else {
				return true;
			}
		} else {
			return true;
		}
	}
}
