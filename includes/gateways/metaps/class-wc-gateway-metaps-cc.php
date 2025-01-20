<?php
/**
 * WC_Gateway_Metaps_CC class file.
 *
 * @package WooCommerce/Classes/Payment
 */

use ArtisanWorkshop\PluginFramework\v2_0_12 as Framework;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC_Gateway_Metaps_CC class.
 *
 * Handles Metaps Credit Card payments in WooCommerce.
 */
class WC_Gateway_Metaps_CC extends WC_Payment_Gateway {

	/**
	 * Framework.
	 *
	 * @var stdClass
	 */
	public $jp4wc_framework;

	/**
	 * Debug mode
	 *
	 * @var string
	 */
	public $debug;

	/**
	 * Test mode
	 *
	 * @var string
	 */
	public $test_mode;

	/**
	 * Set metaps request class
	 *
	 * @var stdClass
	 */
	public $metaps_request;

	/**
	 * Payment methods
	 *
	 * @var array
	 */
	public $payment_methods;

	/**
	 * Payment settings
	 *
	 * @var string
	 */
	public $settings;

	/**
	 * IP Code
	 *
	 * @var string
	 */
	public $ip_code;

	/**
	 * Pass Code
	 *
	 * @var string
	 */
	public $pass_code;

	/**
	 * Payment Action
	 *
	 * @var string
	 */
	public $paymentaction;

	/**
	 * User ID Payment
	 *
	 * @var string
	 */
	public $user_id_payment;

	/**
	 * Number of Payments
	 *
	 * @var array
	 */
	public $number_of_payments;

	/**
	 * EMV 3D Secure
	 *
	 * @var string
	 */
	public $emv_tds;

	/**
	 * Array Number of Payments
	 *
	 * @var array
	 */
	public $array_number_of_payments;

	/**
	 * Constructor for the gateway
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		$this->id           = 'metaps_cc';
		$this->has_fields   = false;
		$this->method_title = __( 'metaps PAYMENT Credit Card', 'metaps-for-woocommerce' );

		// Create plugin fields and settings.
		$this->init_form_fields();
		$this->init_settings();
		$this->method_description = __( 'Allows payments by metaps PAYMENT Credit Card in Japan.', 'metaps-for-woocommerce' );
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
			'refunds',
		);

		$this->jp4wc_framework = new Framework\JP4WC_Framework();

		include_once 'includes/class-wc-gateway-metaps-request.php';
		$this->metaps_request = new WC_Gateway_Metaps_Request();

		// When no save setting error at chackout page.
		if ( is_null( $this->title ) ) {
			$this->title = __( 'Please set this payment at Control Panel! ', 'metaps-for-woocommerce' ) . $this->method_title;
		}

		// Get setting values.
		foreach ( $this->settings as $key => $val ) {
			$this->$key = $val;
		}

		// Set number of payments.
		$this->array_number_of_payments = array(
			'100' => __( '1 time', 'metaps-for-woocommerce' ),
			'80'  => __( 'Revolving payment', 'metaps-for-woocommerce' ),
			'3'   => '3' . __( 'times', 'metaps-for-woocommerce' ),
			'5'   => '5' . __( 'times', 'metaps-for-woocommerce' ),
			'6'   => '6' . __( 'times', 'metaps-for-woocommerce' ),
			'10'  => '10' . __( 'times', 'metaps-for-woocommerce' ),
			'12'  => '12' . __( 'times', 'metaps-for-woocommerce' ),
			'15'  => '15' . __( 'times', 'metaps-for-woocommerce' ),
			'18'  => '18' . __( 'times', 'metaps-for-woocommerce' ),
			'20'  => '20' . __( 'times', 'metaps-for-woocommerce' ),
			'24'  => '24' . __( 'times', 'metaps-for-woocommerce' ),
		);

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_order_status_completed', array( $this, 'order_status_completed_to_capture' ) );
		add_action( 'woocommerce_before_cart', array( $this, 'metaps_cc_return' ) );
	}

	/**
	 * Initialize Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'            => array(
				'title'       => __( 'Enable/Disable', 'metaps-for-woocommerce' ),
				'label'       => __( 'Enable metaps PAYMENT Credit Card Payment', 'metaps-for-woocommerce' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'title'              => array(
				'title'       => __( 'Title', 'metaps-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'metaps-for-woocommerce' ),
				'default'     => __( 'Credit Card (metaps)', 'metaps-for-woocommerce' ),
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
			'emv_tds'            => array(
				'title'       => __( 'EMV 3D Secure', 'metaps-for-woocommerce' ),
				'id'          => 'wc-emv-tds',
				'type'        => 'checkbox',
				'label'       => __( 'Enable EMV 3D Secure', 'metaps-for-woocommerce' ),
				'default'     => 'no',
				'description' => __( 'Use EMV 3D Secure for Credit Card.', 'metaps-for-woocommerce' )
								. __( 'In order to increase your chances of passing the 3DS, it is better to always verify your address so that you are more likely to pass the screening.', 'metaps-for-woocommerce' ),
			),
			'number_of_payments' => array(
				'title'       => __( 'Number of payments', 'metaps-for-woocommerce' ),
				'type'        => 'multiselect',
				'class'       => 'wc-number-select',
				'description' => __( 'Please select the number of installments available.', 'metaps-for-woocommerce' ),
				'desc_tip'    => true,
				'options'     => array(
					'100' => __( '1 time', 'metaps-for-woocommerce' ),
					'80'  => __( 'Revolving payment', 'metaps-for-woocommerce' ),
					'3'   => '3' . __( 'times', 'metaps-for-woocommerce' ),
					'5'   => '5' . __( 'times', 'metaps-for-woocommerce' ),
					'6'   => '6' . __( 'times', 'metaps-for-woocommerce' ),
					'10'  => '10' . __( 'times', 'metaps-for-woocommerce' ),
					'12'  => '12' . __( 'times', 'metaps-for-woocommerce' ),
					'15'  => '15' . __( 'times', 'metaps-for-woocommerce' ),
					'18'  => '18' . __( 'times', 'metaps-for-woocommerce' ),
					'20'  => '20' . __( 'times', 'metaps-for-woocommerce' ),
					'24'  => '24' . __( 'times', 'metaps-for-woocommerce' ),
				),
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
	 * Get the number of payments.
	 *
	 * @return array The array of number of payments.
	 */
	public function get_number_payments() {
		return $this->array_number_of_payments;
	}

	/**
	 * UI - Payment page fields for metaps PAYMENT Payment.
	 */
	public function payment_fields() {
		// Description of payment method from settings.
		if ( $this->description ) {
			echo '<p>' . esc_html( $this->description ) . '</p>';
		}
		$user                 = wp_get_current_user();
		$number_payment_array = $this->get_number_payments();
		$metaps_user_id       = get_user_meta( $user->ID, '_metaps_user_id', true );
		if ( 'yes' === $this->user_id_payment && '' !== $metaps_user_id && is_user_logged_in() ) {
			echo '<input type="radio" name="user_id_payment" value="yes" checked="checked"><span style="padding-left:15px;">' . esc_html__( 'Use Stored Card.', 'metaps-for-woocommerce' ) . '</span><br />' . PHP_EOL;
			if ( ! empty( $this->number_of_payments ) ) {
				echo '<select name="number_of_payments">';
				foreach ( $this->number_of_payments as $key => $value ) {
					echo '<option value="' . esc_attr( $value ) . '">' . esc_html( $number_payment_array[ $value ] ) . '</option>';
				}
				echo '</select>';
			}
			echo '<br />';
			echo '<input type="radio" name="user_id_payment" value="no"><span style="padding-left:15px;">' . esc_html__( 'Use New Card.', 'metaps-for-woocommerce' ) . '</span><br />' . PHP_EOL;
		}
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int  $order_id     The order ID.
	 * @param bool $subscription Whether the payment is for a subscription.
	 * @return array The result of the payment process.
	 * @throws Exception If there is a payment error.
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
			$customer_id = $prefix_order . $order_id . '-user';
		}
		// Set User id.
		if ( 'yes' === $this->user_id_payment && is_user_logged_in() ) {
			$setting_data['ip_user_id'] = $customer_id;
		}

		$setting_data['pass'] = $this->pass_code;
		// User ID payment check.
		$setting_data['store'] = '51';
		if ( 'yes' === $this->user_id_payment && $this->get_post( 'user_id_payment' ) === 'yes' && is_user_logged_in() ) {
			$setting_data['store'] = null;
		}

		$setting_data['ip'] = $this->ip_code;
		if ( 'sale' === $this->paymentaction ) {
			$setting_data['kakutei'] = '1';// capture = 1 .
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

		if ( isset( $setting_data['store'] ) ) {// When not use user id payment.
			$connect_url = METAPS_CC_SALES_URL;
			$thanks_url  = $this->get_return_url( $order );
			// Reduce stock levels.
			wc_reduce_stock_levels( $order_id );
			$order->update_status( 'on-hold', __( 'Finished to send payment data to metaps PAYMENT.', 'metaps-for-woocommerce' ) . ' ' . __( 'Awaiting payment confirmation from metaps PAYMENT.', 'metaps-for-woocommerce' ) );
			$get_url = $this->metaps_request->get_post_to_metaps( $order, $connect_url, $setting_data, $thanks_url, $this->debug, $this->emv_tds );

			return array(
				'result'   => 'success',
				'redirect' => $get_url,
			);
		} else { // When use user id payment.
			$connect_url = METAPS_CC_SALES_USER_URL;
			$response    = $this->metaps_request->metaps_post_request( $order, $connect_url, $setting_data, $this->debug, 'no' );
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
				if ( is_checkout() ) {
					wc_add_notice( __( 'Payment error:', 'metaps-for-woocommerce' ) . mb_convert_encoding( $response[2], 'UTF-8', 'sjis' ), 'error' );
				}
				$error_message = mb_convert_encoding( $response[2], 'UTF-8', 'sjis' );
				$order->update_status( 'cancelled', __( 'This order is cancelled, because of Payment error: ', 'metaps-for-woocommerce' ) . $error_message );
				delete_user_meta( $user->ID, '_metaps_user_id', $customer_id );

				$front_error_message  = __( 'Credit card payment failed.', 'metaps-for-woocommerce' );
				$front_error_message .= __( 'Please try again.', 'metaps-for-woocommerce' );
				throw new Exception( esc_html( $front_error_message ) );
			}
		}
	}

	/**
	 * Validate frontend fields.
	 *
	 * Validate payment fields on the frontend.
	 *
	 * @return bool
	 */
	public function validate_fields() {
		// Get the WC_Checkout object.
		$checkout = WC()->checkout();

		$billing_phone = $checkout->get_value( 'billing_phone' );
		$billing_email = $checkout->get_value( 'billing_email' );

		if ( empty( $billing_phone ) && empty( $billing_email ) ) {
			wc_add_notice( __( 'A phone number or email address is required for credit card payments.', 'metaps-for-woocommerce' ), 'error' );
			return false;
		}
	}

	/**
	 * Refund a charge.
	 *
	 * @param int    $order_id The order ID.
	 * @param float  $amount   The amount to refund.
	 * @param string $reason   The reason for the refund.
	 * @return bool|WP_Error True or false based on success, or a WP_Error object.
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return false;
		} elseif ( $amount !== $order->order_total ) {
			$order->add_order_note( __( 'Auto refund must total only. ', 'metaps-for-woocommerce' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-woocommerce' ) );
			return new WP_Error( 'metaps_refund_error', __( 'Auto refund must total only. ', 'metaps-for-woocommerce' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-woocommerce' ) );
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
			if ( isset( $response ) && substr( $response, 0, 10 ) === 'C-CHECK:OK' ) {
				$order->add_order_note( __( 'This order is refunded now at metaps PAYMENT.', 'metaps-for-woocommerce' ) );
				return true;
			} elseif ( isset( $response ) && substr( $response, 0, 10 ) === 'C-CHECK:NG' ) {
				if ( substr( $response, -3, 1 ) === '2' ) {
					$order->add_order_note( __( 'This order has already auth canselled.', 'metaps-for-woocommerce' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-woocommerce' ) );
				} elseif ( substr( $response, -3, 1 ) === '3' ) {
					$order->add_order_note( __( 'This order has completed.', 'metaps-for-woocommerce' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-woocommerce' ) );
				} elseif ( substr( $response, -3, 1 ) === '4' ) {
					$order->add_order_note( __( 'This order has already canselled.', 'metaps-for-woocommerce' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-woocommerce' ) );
				}
				return new WP_Error( 'metaps_refund_error', __( 'Error has happened. ', 'metaps-for-woocommerce' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-woocommerce' ) );
			} elseif ( isset( $response ) && substr( $response, 0, 10 ) === 'C-CHECK:TO' ) {
				$order->add_order_note( __( 'Expired. Status not changed.', 'metaps-for-woocommerce' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-woocommerce' ) );
				return new WP_Error( 'metaps_refund_error', __( 'Error has happened. ', 'metaps-for-woocommerce' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-woocommerce' ) );
			} elseif ( isset( $response ) && substr( $response, 0, 10 ) === 'C-CHECK:ER' ) {
				$order->add_order_note( __( 'Error has happend. Status not changed.', 'metaps-for-woocommerce' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-woocommerce' ) );
				return new WP_Error( 'metaps_refund_error', __( 'Error has happened. ', 'metaps-for-woocommerce' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-woocommerce' ) );
			}
		} else {
			if ( 'sale' === $this->paymentaction ) {
				$response = $this->metaps_request->metaps_request( $data, $cansel_connect_url, $order, $this->debug );
			} else {
				$response = $this->metaps_request->metaps_request( $data, $refund_connect_url, $order, $this->debug );
			}
			if ( isset( $response ) && substr( $response, 0, 10 ) === 'C-CHECK:OK' ) {
				$order->add_order_note( __( 'This order is refunded now at metaps PAYMENT.', 'metaps-for-woocommerce' ) );
				return true;
			} elseif ( isset( $response ) && substr( $response, 0, 10 ) === 'C-CHECK:NG' ) {
				if ( substr( $response, -3, 1 ) === '1' ) {
					$order->add_order_note( __( 'This order is authorized now.', 'metaps-for-woocommerce' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-woocommerce' ) );
				} elseif ( substr( $response, -3, 1 ) === '2' ) {
					$order->add_order_note( __( 'This order has already auth canselled.', 'metaps-for-woocommerce' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-woocommerce' ) );
				} elseif ( substr( $response, -3, 1 ) === '3' ) {
					$order->add_order_note( __( 'This order has completed.', 'metaps-for-woocommerce' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-woocommerce' ) );
				} elseif ( substr( $response, -3, 1 ) === '4' ) {
					$order->add_order_note( __( 'This order has already canselled.', 'metaps-for-woocommerce' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-woocommerce' ) );
				}
				return new WP_Error( 'metaps_refund_error', __( 'Error has happened. ', 'metaps-for-woocommerce' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-woocommerce' ) );
			} elseif ( isset( $response ) && substr( $response, 0, 10 ) === 'C-CHECK:TO' ) {
				$order->add_order_note( __( 'Expired. Status not changed.', 'metaps-for-woocommerce' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-woocommerce' ) );
				return new WP_Error( 'metaps_refund_error', __( 'Error has happened. ', 'metaps-for-woocommerce' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-woocommerce' ) );
			} elseif ( isset( $response ) && substr( $response, 0, 10 ) === 'C-CHECK:ER' ) {
				$order->add_order_note( __( 'Error has happend. Status not changed.', 'metaps-for-woocommerce' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-woocommerce' ) );
				return new WP_Error( 'metaps_refund_error', __( 'Error has happened. ', 'metaps-for-woocommerce' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-woocommerce' ) );
			}
		}
	}

	/**
	 * Get post data if set
	 *
	 * @param string $name The name of the POST field.
	 * @return string|null The sanitized POST field value or null if not set.
	 */
	public function get_post( $name ) {
		// Get the WC_Checkout object.
		$checkout = WC()->checkout();
		return $checkout->get_value( $name );
	}

	/**
	 * Update Sale from Auth to metaps.
	 *
	 * @param int $order_id The order ID.
	 */
	public function order_status_completed_to_capture( $order_id ) {
		if ( 'sale' !== $this->paymentaction ) {
			$metaps_setting  = get_option( 'woocommerce_metaps_cc_settings' );
			$metaps_settings = get_option( 'woocommerce_metaps_settings' );
			$prefix_order    = $metaps_settings['prefixorder'];

			$connect_url = METAPS_CC_SALES_COMP_URL;
			$order       = wc_get_order( $order_id );
			if ( $order && 'metaps_cc' === $order->payment_method ) {
				$data['IP']  = $metaps_setting['ip_code'];
				$data['SID'] = $prefix_order . $order_id;
				include_once 'includes/class-wc-gateway-metaps-request.php';
				$metaps_request = new WC_Gateway_Metaps_Request();

				$response = $metaps_request->metaps_request( $data, $connect_url, $order );
				if ( isset( $response[0] ) && substr( $response[0], 0, 2 ) === 'OK' ) {
					return true;
				} elseif ( isset( $response ) && substr( $response, 0, 10 ) === 'C-CHECK:NG' ) {
					if ( substr( $response, -3, 1 ) === '3' ) {
						if ( $order->get_status() !== 'completed' ) {
							// Payment complete.
							$order->payment_complete();
							return true;
						} elseif ( 'completed' === $order->get_status() && 'sale' === $this->paymentaction ) {
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
			}
			return true;
		}
	}

	/**
	 * Recieved Credit Payment complete from metaps
	 */
	public function metaps_cc_return() {
		if ( isset( $_GET['pd'] ) && 'return' === $_GET['pd'] && isset( $_GET['sid'] ) ) {// phpcs:ignore
			$metaps_settings      = get_option( 'woocommerce_metaps_settings' );
			$prefix_order         = $metaps_settings['prefixorder'];
			$order_id             = str_replace( $prefix_order, '', sanitize_text_field( wp_unslash( $_GET['sid'] ) ) );// phpcs:ignore
			$order                = wc_get_order( $order_id );
			$order_payment_method = $order->get_payment_method();
			if ( 'metaps_cc' === $order_payment_method ) {
				wc_increase_stock_levels( $order_id );
				$order->update_status( 'cancelled', __( 'This order is cancelled, because of the return from metaps PAYMENT site.', 'metaps-for-woocommerce' ) );
			}
		}
	}
}
