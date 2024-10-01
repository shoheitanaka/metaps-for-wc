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
	 * Array Number of Payments
	 *
	 * @var array
	 */
	public $array_number_of_payments;

	/**
	 * EMV 3D Secure
	 *
	 * @var string
	 */
	public $emv_tds;

	/**
	 * Constructor for the gateway
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		$this->id           = 'metaps_cc';
		$this->has_fields   = false;
		$this->method_title = __( 'metaps PAYMENT Credit Card', 'metaps-for-wc' );

		// Create plugin fields and settings.
		$this->init_form_fields();
		$this->init_settings();
		$this->method_description = __( 'Allows payments by metaps PAYMENT Credit Card in Japan.', 'metaps-for-wc' );
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
			$this->title = __( 'Please set this payment at Control Panel! ', 'metaps-for-wc' ) . $this->method_title;
		}

		// Get setting values.
		foreach ( $this->settings as $key => $val ) {
			$this->$key = $val;
		}
		// Number of payments.
		$this->array_number_of_payments = array(
			'100'                => __( '1 time', 'metaps-for-wc' ),
							'80' => __( 'Revolving payment', 'metaps-for-wc' ),
			'3'  => '3' . __( 'times', 'metaps-for-wc' ),
							'5'  => '5' . __( 'times', 'metaps-for-wc' ),
			'6'                  => '6' . __( 'times', 'metaps-for-wc' ),
			'10'                 => '10' . __( 'times', 'metaps-for-wc' ),
			'12'                 => '12' . __( 'times', 'metaps-for-wc' ),
			'15'                 => '15' . __( 'times', 'metaps-for-wc' ),
			'18'                 => '18' . __( 'times', 'metaps-for-wc' ),
			'20'                 => '20' . __( 'times', 'metaps-for-wc' ),
			'24'                 => '24' . __( 'times', 'metaps-for-wc' ),
			// '21'    => __( 'Bonus One time', 'metaps-for-wc' ),
			// '2'     => '2'.__( 'times', 'metaps-for-wc' ),
			// '4'     => '4'.__( 'times', 'metaps-for-wc' ),
		);

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}
	/**
	 * Initialize Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'            => array(
				'title'       => __( 'Enable/Disable', 'metaps-for-wc' ),
				'label'       => __( 'Enable metaps PAYMENT Credit Card Payment', 'metaps-for-wc' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'title'              => array(
				'title'       => __( 'Title', 'metaps-for-wc' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'metaps-for-wc' ),
				'default'     => __( 'Credit Card (metaps)', 'metaps-for-wc' ),
			),
			'description'        => array(
				'title'       => __( 'Description', 'metaps-for-wc' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'metaps-for-wc' ),
				'default'     => __( 'Pay with your credit card via metaps PAYMENT.', 'metaps-for-wc' ),
			),
			'order_button_text'  => array(
				'title'       => __( 'Order Button Text', 'metaps-for-wc' ),
				'type'        => 'text',
				'description' => __( 'This controls the description which the user sees during checkout.', 'metaps-for-wc' ),
				'default'     => __( 'Proceed to metaps PAYMENT Credit Card', 'metaps-for-wc' ),
			),
			'ip_code'            => array(
				'title'       => __( 'IP Code', 'metaps-for-wc' ),
				'type'        => 'text',
				'description' => __( 'Enter IP Code here.', 'metaps-for-wc' ),
			),
			'pass_code'          => array(
				'title'       => __( 'IP Password', 'metaps-for-wc' ),
				'type'        => 'text',
				'description' => __( 'Enter IP Password here', 'metaps-for-wc' ),
			),
			'paymentaction'      => array(
				'title'       => __( 'Payment Action', 'woocommerce' ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'description' => __( 'Choose whether you wish to capture funds immediately or authorize payment only.', 'woocommerce' ),
				'default'     => 'sale',
				'desc_tip'    => true,
				'options'     => array(
					'sale'          => __( 'Capture', 'woocommerce' ),
					'authorization' => __( 'Authorize', 'woocommerce' ),
				),
			),
			'user_id_payment'    => array(
				'title'       => __( 'User ID Payment', 'metaps-for-wc' ),
				'id'          => 'wc-userid-payment',
				'type'        => 'checkbox',
				'label'       => __( 'User ID Payment', 'metaps-for-wc' ),
				'default'     => 'yes',
				'description' => __( 'Use the payment method of User ID payment.', 'metaps-for-wc' ),
			),
			'emv_tds'            => array(
				'title'       => __( 'EMV 3D Secure', 'metaps-for-wc' ),
				'id'          => 'wc-emv-tds',
				'type'        => 'checkbox',
				'label'       => __( 'Enable EMV 3D Secure', 'metaps-for-wc' ),
				'default'     => 'no',
				'description' => __( 'Use EMV 3D Secure for Credit Card.', 'metaps-for-wc' )
								. __( 'In order to increase your chances of passing the 3DS, it is better to always verify your address so that you are more likely to pass the screening.', 'metaps-for-wc' ),
			),
			'number_of_payments' => array(
				'title'       => __( 'Number of payments', 'metaps-for-wc' ),
				'type'        => 'multiselect',
				'class'       => 'wc-number-select',
				'description' => __( 'Choose whether you wish to capture funds immediately or authorize payment only.', 'metaps-for-wc' ),
				'desc_tip'    => true,
				'options'     => array(
					'100' => __( '1 time', 'metaps-for-wc' ),
					'80'  => __( 'Revolving payment', 'metaps-for-wc' ),
					'3'   => '3' . __( 'times', 'metaps-for-wc' ),
					'5'   => '5' . __( 'times', 'metaps-for-wc' ),
					'6'   => '6' . __( 'times', 'metaps-for-wc' ),
					'10'  => '10' . __( 'times', 'metaps-for-wc' ),
					'12'  => '12' . __( 'times', 'metaps-for-wc' ),
					'15'  => '15' . __( 'times', 'metaps-for-wc' ),
					'18'  => '18' . __( 'times', 'metaps-for-wc' ),
					'20'  => '20' . __( 'times', 'metaps-for-wc' ),
					'24'  => '24' . __( 'times', 'metaps-for-wc' ),
					// '21'    => __( 'Bonus One time', 'metaps-for-wc' ),
					// '2'     => '2'.__( 'times', 'metaps-for-wc' ),
					// '4'     => '4'.__( 'times', 'metaps-for-wc' ),
				),
			),
			'debug'              => array(
				'title'       => __( 'Debug Mode', 'metaps-for-wc' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable Debug Mode', 'metaps-for-wc' ),
				'default'     => 'no',
				'description' => __( 'Save debug data using WooCommerce logging.', 'metaps-for-wc' ),
			),
		);
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
		$number_payment_array = array(
			'100' => __( '1 time', 'metaps-for-wc' ),
			'80'  => __( 'Revolving payment', 'metaps-for-wc' ),
			'3'   => '3' . __( 'times', 'metaps-for-wc' ),
			'5'   => '5' . __( 'times', 'metaps-for-wc' ),
			'6'   => '6' . __( 'times', 'metaps-for-wc' ),
			'10'  => '10' . __( 'times', 'metaps-for-wc' ),
			'12'  => '12' . __( 'times', 'metaps-for-wc' ),
			'15'  => '15' . __( 'times', 'metaps-for-wc' ),
			'18'  => '18' . __( 'times', 'metaps-for-wc' ),
			'20'  => '20' . __( 'times', 'metaps-for-wc' ),
			'24'  => '24' . __( 'times', 'metaps-for-wc' ),
			// '21'    => __( 'Bonus One time', 'metaps-for-wc' ),
			// '2'     => '2'.__( 'times', 'metaps-for-wc' ),
			// '4'     => '4'.__( 'times', 'metaps-for-wc' ),
		);
		$metaps_user_id = get_user_meta( $user->ID, '_metaps_user_id', true );
		if ( $this->user_id_payment == 'yes' && $metaps_user_id != '' && is_user_logged_in() ) {
			echo '<input type="radio" name="select_card" value="old" checked="checked"><span style="padding-left:15px;">' . __( 'Use Stored Card.', 'metaps-for-wc' ) . '</span><br />' . PHP_EOL;
			if ( ! empty( $this->number_of_payments ) ) {
				echo '<select name="number_of_payments">';
				foreach ( $this->number_of_payments as $key => $value ) {
					echo '<option value="' . esc_attr( $value ) . '">' . esc_html( $number_payment_array[ $value ] ) . '</option>';
				}
				echo '</select>';
			}
			echo '<br />';
			echo '<input type="radio" name="select_card" value="new_credit"><span style="padding-left:15px;">' . __( 'Use New Card.', 'metaps-for-wc' ) . '</span><br />' . PHP_EOL;
		}
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int  $order_id     The order ID.
	 * @param bool $subscription Whether the payment is for a subscription.
	 * @return array The result of the payment process.
	 */
	function process_payment( $order_id, $subscription = false ) {
		$prefix_order = get_option( 'wc_metaps_prefix_order' );

		$order = wc_get_order( $order_id );
		$user  = wp_get_current_user();
		// Setting $send_data.
		$setting_data = array();
		if ( isset( $user->ID ) && $user->ID != 0 ) {
			$customer_id = $prefix_order . $user->ID;
		} else {
			$customer_id = $prefix_order . $order_id . '-user';
		}
		// Set User id.
		if ( $this->user_id_payment == 'yes' && is_user_logged_in() ) {
			$setting_data['ip_user_id'] = $customer_id;
		}

		$setting_data['pass'] = $this->pass_code;
		// User ID payment check.
			$setting_data['store'] = '51';
		if ( $this->user_id_payment == 'yes' && $this->get_post( 'select_card' ) == 'old' && is_user_logged_in() ) {
			$setting_data['store'] = null;
		}

		$setting_data['ip'] = $this->ip_code;
		if ( $this->paymentaction == 'sale' ) {
			$setting_data['kakutei'] = '1';// capture = 1 .
		} else {
			$setting_data['kakutei'] = '0';// auth = 0.
		}
		$setting_data['lang'] = '0';// Use Language 0 = Japanese, 1 = English.
		$setting_data['sid']  = $prefix_order . $order_id;

		// Number of Payment check.
		$number_of_payments = $this->get_post( 'number_of_payments' );
		if ( isset( $number_of_payments ) ) {
			if ( $number_of_payments == 21 || $number_of_payments == 80 ) {
				$setting_data['paymode'] = $number_of_payments;
			} elseif ( $number_of_payments == 100 ) {
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
			$order->add_order_note( __( 'Finished to send payment data to metaps PAYMENT.', 'metaps-for-wc' ) );

			$get_url = $this->metaps_request->get_post_to_metaps( $order, $connect_url, $setting_data, $thanks_url, $this->debug, $this->emv_tds );

			return array(
				'result'   => 'success',
				'redirect' => $get_url,
			);
		} else { // When use user id payment.
			$connect_url = METAPS_CC_SALES_USER_URL;
			$response    = $this->metaps_request->metaps_post_request( $order, $connect_url, $setting_data, $this->debug, $this->emv_tds );
			if ( isset( $response[0] ) && substr( $response[0], 0, 2 ) == 'OK' ) {
				update_user_meta( $user->ID, '_metaps_user_id', $customer_id );
				$order->add_order_note( __( 'Finished to send payment data to metaps PAYMENT.', 'metaps-for-wc' ) );
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);
			} else {
				if ( is_checkout() ) {
					wc_add_notice( __( 'Payment error:', 'metaps-for-wc' ) . mb_convert_encoding( $response[2], 'UTF-8', 'sjis' ), 'error' );
				}
				// $order->add_order_note(__('Payment error:', 'metaps-for-wc') . $setting_data['ip_user_id'].$setting_data['store']);
				$order->update_status( 'cancelled', __( 'This order is cancelled, because of Payment error.' . mb_convert_encoding( $response[2], 'UTF-8', 'sjis' ), 'metaps-for-wc' ) );
				delete_user_meta( $user->ID, '_metaps_user_id', $customer_id );
				return array(
					'result'   => 'failure',
					'message'  => 'Payment with the saved card failed. Please re-enter the card information.',
					'redirect' => wc_get_checkout_url(),
				);
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
		if ( $this->emv_tds == 'yes' ) {
			if ( empty( $_POST['billing_phone'] ) && empty( $_POST['billing_email'] ) ) {
				wc_add_notice( __( 'A phone number or email address is required for credit card payments.', 'metaps-for-wc' ), 'error' );
				return false;
			}
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
	function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return false;
		} elseif ( $amount != $order->order_total ) {
			$order->add_order_note( __( 'Auto refund must total only. ', 'metaps-for-wc' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-wc' ) );
			return new WP_Error( 'metaps_refund_error', __( 'Auto refund must total only. ', 'metaps-for-wc' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-wc' ) );
		}
		$prefix_order = get_option( 'wc_metaps_prefix_order' );

		$refund_connect_url = METAPS_CC_SALES_CANCEL_URL;
		$cansel_connect_url = METAPS_CC_SALES_REFUND_URL;

		$status       = $order->get_status();
		$data['IP']   = $this->ip_code;
		$data['PASS'] = $this->pass_code;
		$data['SID']  = $prefix_order . $order_id;
		// $order->add_order_note( 'test001' );
		if ( $status == 'completed' ) {
			$response = $this->metaps_request->metaps_request( $data, $cansel_connect_url, $order, $this->debug );
			if ( isset( $response ) && substr( $response, 0, 10 ) == 'C-CHECK:OK' ) {
				$order->add_order_note( __( 'This order is refunded now at metaps PAYMENT.', 'metaps-for-wc' ) );
				return true;
			} elseif ( isset( $response ) && substr( $response, 0, 10 ) == 'C-CHECK:NG' ) {
				if ( substr( $response, -3, 1 ) == '2' ) {
					$order->add_order_note( __( 'This order has already auth canselled.', 'metaps-for-wc' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-wc' ) );
				} elseif ( substr( $response, -3, 1 ) == '3' ) {
					$order->add_order_note( __( 'This order has completed.', 'metaps-for-wc' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-wc' ) );
				} elseif ( substr( $response, -3, 1 ) == '4' ) {
					$order->add_order_note( __( 'This order has already canselled.', 'metaps-for-wc' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-wc' ) );
				}
				return new WP_Error( 'metaps_refund_error', __( 'Error has happened. ', 'metaps-for-wc' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-wc' ) );
			} elseif ( isset( $response ) && substr( $response, 0, 10 ) == 'C-CHECK:TO' ) {
				$order->add_order_note( __( 'Expired. Status not changed.', 'metaps-for-wc' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-wc' ) );
				return new WP_Error( 'metaps_refund_error', __( 'Error has happened. ', 'metaps-for-wc' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-wc' ) );
			} elseif ( isset( $response ) && substr( $response, 0, 10 ) == 'C-CHECK:ER' ) {
				$order->add_order_note( __( 'Error has happend. Status not changed.', 'metaps-for-wc' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-wc' ) );
				return new WP_Error( 'metaps_refund_error', __( 'Error has happened. ', 'metaps-for-wc' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-wc' ) );
			}
		} else {
			if ( $this->paymentaction == 'sale' ) {
				$response = $this->metaps_request->metaps_request( $data, $cansel_connect_url, $order, $this->debug );
			} else {
				$response = $this->metaps_request->metaps_request( $data, $refund_connect_url, $order, $this->debug );
			}
			if ( isset( $response ) && substr( $response, 0, 10 ) == 'C-CHECK:OK' ) {
				$order->add_order_note( __( 'This order is refunded now at metaps PAYMENT.', 'metaps-for-wc' ) );
				return true;
			} elseif ( isset( $response ) && substr( $response, 0, 10 ) == 'C-CHECK:NG' ) {
				// $order->add_order_note( substr($response, -4 ) );
				if ( substr( $response, -3, 1 ) == '1' ) {
					$order->add_order_note( __( 'This order is authorized now.', 'metaps-for-wc' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-wc' ) );
				} elseif ( substr( $response, -3, 1 ) == '2' ) {
					$order->add_order_note( __( 'This order has already auth canselled.', 'metaps-for-wc' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-wc' ) );
				} elseif ( substr( $response, -3, 1 ) == '3' ) {
					$order->add_order_note( __( 'This order has completed.', 'metaps-for-wc' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-wc' ) );
				} elseif ( substr( $response, -3, 1 ) == '4' ) {
					$order->add_order_note( __( 'This order has already canselled.', 'metaps-for-wc' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-wc' ) );
				}
				return new WP_Error( 'metaps_refund_error', __( 'Error has happened. ', 'metaps-for-wc' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-wc' ) );
			} elseif ( isset( $response ) && substr( $response, 0, 10 ) == 'C-CHECK:TO' ) {
				$order->add_order_note( __( 'Expired. Status not changed.', 'metaps-for-wc' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-wc' ) );
				return new WP_Error( 'metaps_refund_error', __( 'Error has happened. ', 'metaps-for-wc' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-wc' ) );
			} elseif ( isset( $response ) && substr( $response, 0, 10 ) == 'C-CHECK:ER' ) {
				$order->add_order_note( __( 'Error has happend. Status not changed.', 'metaps-for-wc' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-wc' ) );
				return new WP_Error( 'metaps_refund_error', __( 'Error has happened. ', 'metaps-for-wc' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-wc' ) );
			}
		}
	}


	/**
	 * Display the receipt page.
	 *
	 * @param WC_Order $order The order object.
	 */
	function receipt_page( $order ) {
		echo '<p>' . esc_html__( 'Thank you for your order.', 'metaps-for-wc' ) . '</p>';
	}

	/**
	 * Get post data if set
	 *
	 * @param string $name The name of the POST field.
	 * @return string|null The sanitized POST field value or null if not set.
	 */
	function get_post( $name ) {
		if ( isset( $_POST['_wpnonce'] ) && isset( $_POST[ $name ] ) ) {
			return sanitize_text_field( wp_unslash( $_POST[ $name ] ) );
		}
		return null;
	}
}

/**
 * Add the gateway to woocommerce
 */
function add_wc_metaps_cc_gateway( $methods ) {
	$subscription_support_enabled = false;
	if ( class_exists( 'WC_Subscriptions_Order' ) && function_exists( 'wcs_create_renewal_order' ) ) {
		$subscription_support_enabled = true;
	}
	if ( $subscription_support_enabled ) {
		$methods[] = 'WC_Gateway_Metaps_CC_Addons';
	} else {
		$methods[] = 'WC_Gateway_Metaps_CC';
	}
	return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'add_wc_metaps_cc_gateway' );

/**
 * Update Sale from Auth to metaps.
 */
function order_status_completed_to_capture( $order_id ) {
	global $woocommerce;
	$payment_setting = new WC_Gateway_Metaps_CC();

	if ( $payment_setting->paymentaction != 'sale' ) {
		$metaps_setting = get_option( 'woocommerce_metaps_cc_settings' );
		$prefix_order   = get_option( 'wc_metaps_prefix_order' );

		$connect_url = METAPS_CC_SALES_COMP_URL;
		$order       = wc_get_order( $order_id );
		if ( $order && $order->payment_method == 'metaps_cc' ) {
			$prefix_order = get_option( 'wc_metaps_prefix_order' );
			$data['IP']   = $metaps_setting['ip_code'];
			$data['SID']  = $prefix_order . $order_id;
			include_once 'includes/class-wc-gateway-metaps-request.php';
			$metaps_request = new WC_Gateway_Metaps_Request();

			$response = $metaps_request->metaps_request( $data, $connect_url, $order );
			if ( isset( $response[0] ) && substr( $response[0], 0, 2 ) == 'OK' ) {
				return true;
			} elseif ( isset( $response ) && substr( $response, 0, 10 ) == 'C-CHECK:NG' ) {
				if ( substr( $response, -3, 1 ) == '3' ) {
					if ( $order->get_status() != 'completed' ) {
						// Payment complete
						$order->payment_complete();
						return true;
					} elseif ( $order->get_status() == 'completed' && $payment_setting->paymentaction == 'sale' ) {
						$order->add_order_note( __( 'This order has already completed.', 'metaps-for-wc' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-wc' ) );
					}
					return true;
				} elseif ( substr( $response, -3, 1 ) == '2' ) {
					$order->add_order_note( __( 'This order has already auth canselled.', 'metaps-for-wc' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-wc' ) );
				} elseif ( substr( $response, -3, 1 ) == '4' ) {
					$order->add_order_note( __( 'This order has already canselled.', 'metaps-for-wc' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-wc' ) );
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
			} elseif ( isset( $response ) && substr( $response, 0, 10 ) == 'C-CHECK:ER' ) {
				$order->add_order_note( __( 'Error has happend. Status not changed.', 'metaps-for-wc' ) . __( 'If you need, please contact to metaps PAYMENT Support.', 'metaps-for-wc' ) );
			}
			return false;
		}
		return true;
	}
}
add_action( 'woocommerce_order_status_completed', 'order_status_completed_to_capture' );


/**
 * Recieved Credit Payment complete from metaps
 */
function metaps_cc_recieved() {
	global $woocommerce;
	global $wpdb;

	if ( isset( $_GET['SEQ'] ) && isset( $_GET['DATE'] ) && isset( $_GET['SID'] ) ) {
		$pd_order_id  = $_GET['SID'];
		$prefix_order = get_option( 'wc_metaps_prefix_order' );
		$order_id     = str_replace( $prefix_order, '', $pd_order_id );
		$order        = new WC_Order( $order_id );
		if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
			$order_payment_method = $order->payment_method;
			$order_status         = $order->status;
		} else {
			$order_payment_method = $order->get_payment_method();
			$order_status         = $order->get_status();
		}
		$user = wp_get_current_user();
		if ( isset( $_GET['TIME'] ) && isset( $order_status ) && $order_status != 'processing' && $order_payment_method == 'metaps_cc' ) {
			// Mark as processing (payment complete)
			$order->update_status( 'processing', sprintf( __( 'Payment of %s was complete.', 'metaps-for-wc' ), __( 'Credit Card (metaps)', 'metaps-for-wc' ) ) );
			update_user_meta( $order->get_user_id(), '_metaps_user_id', $prefix_order . $order->get_user_id() );
			// $order->add_order_note( '_metaps_user_id'.$order->get_user_id() );
		}
		header( 'Location: ' . plugin_dir_url( __FILE__ ) . 'empty.php' );
	}
}

add_action( 'woocommerce_cart_is_empty', 'metaps_cc_recieved' );

/**
 * Recieved Credit Payment complete from metaps
 */
function metaps_cc_return() {
	global $woocommerce;
	global $wpdb;
	if ( isset( $_GET['pd'] ) == 'return' && isset( $_GET['sid'] ) ) {
		$prefix_order         = get_option( 'wc_metaps_prefix_order' );
		$order_id             = str_replace( $prefix_order, '', $_GET['sid'] );
		$order                = wc_get_order( $order_id );
		$order_payment_method = $order->get_payment_method();
		if ( $order_payment_method == 'metaps_cc' ) {
			wc_increase_stock_levels( $order_id );
			$order->update_status( 'cancelled', __( 'This order is cancelled, because of the return from metaps PAYMENT site.', 'metaps-for-wc' ) );
		}
	}
}
add_action( 'woocommerce_before_cart', 'metaps_cc_return' );

