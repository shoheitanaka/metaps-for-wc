<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * metaps PAYMENT Gateway.
 *
 * Provides a metaps PAYMENT Credit Card Payment Gateway for subscriptions..
 *
 * @class       WC_Gateway_Addons_Metaps_CC
 * @extends     WC_Gateway_Metaps_CC
 * @version     1.1.24
 * @package     WooCommerce/Classes/Payment
 * @author      Artisan Workshop
 */
class WC_Gateway_Addons_Metaps_CC extends WC_Gateway_Metaps_CC {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		if ( class_exists( 'WC_Subscriptions_Order' ) ) {
			add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array( $this, 'scheduled_subscription_payment' ), 10, 2 );
		}
	}
	/**
	 * Check if order contains subscriptions.
	 *
	 * @param  int $order_id The ID of the order.
	 * @return bool
	 */
	protected function order_contains_subscription( $order_id ) {
		return function_exists( 'wcs_order_contains_subscription' ) && ( wcs_order_contains_subscription( $order_id ) || wcs_order_contains_renewal( $order_id ) );
	}

	/**
	 * Is $order_id a subscription?
	 *
	 * @param int $order_id The ID of the order.
	 * @return boolean
	 */
	protected function is_subscription( $order_id ) {
		return ( function_exists( 'wcs_order_contains_subscription' ) && ( wcs_order_contains_subscription( $order_id ) || wcs_is_subscription( $order_id ) || wcs_order_contains_renewal( $order_id ) ) );
	}

	/**
	 * Process the subscription.
	 *
	 * @param  WC_Order $order The order object.
	 * @param  string   $subscription Indicates if the payment is for a subscription.
	 * @return array
	 */
	protected function process_subscription( $order, $subscription = false ) {
		$payment_response = $this->process_subscription_payment( $order, $order->get_total() );
		return $payment_response;
	}

	/**
	 * Process the payment.
	 *
	 * @param  int    $order_id  The ID of the order.
	 * @param  string $subscription Indicates if the payment is for a subscription.
	 * @return array
	 */
	public function process_payment( $order_id, $subscription = false ) {
		// Processing subscription.
		if ( $this->is_subscription( $order_id ) ) {
			// Regular payment with force customer enabled.
			return parent::process_payment( $order_id, true );
		} else {
			return parent::process_payment( $order_id, false );
		}
	}
	/**
	 * Process_subscription_payment function.
	 *
	 * @param WC_order $order The order object.
	 * @param int      $amount (default: 0).
	 * @uses  metaps_subscriptions_payment
	 * @return bool|WP_Error
	 */
	public function process_subscription_payment( $order = '', $amount = 0 ) {
		if ( 0 === $amount ) {
			// Payment complete.
			$order->payment_complete();

			return true;
		}
		include_once 'includes/class-wc-gateway-metaps-request.php';
		$metaps_request = new WC_Gateway_Metaps_Request();

		$metaps_settings = get_option( 'woocommerce_metaps_settings' );
		$prefix_order    = $metaps_settings['prefixorder'];
		$metaps          = new WC_Gateway_Metaps_CC();

		$order_id = $order->get_id();

		// Setting $send_data.
		$setting_data               = array();
		$setting_data['ip_user_id'] = $prefix_order . $order->get_user_id();
		$setting_data['ip']         = $metaps->ip_code;
		$setting_data['pass']       = $metaps->pass_code;
		$setting_data['lang']       = '0';// Use Language 0 = Japanese, 1 = English.
		$setting_data['sid']        = $prefix_order . $order_id;
		$setting_data['paymode']    = 10;
		if ( $metaps->paymentaction == 'sale' ) {
			$setting_data['kakutei'] = '1';// capture = 1.
		} else {
			$setting_data['kakutei'] = '0';// auth = 0.
		}
		$connect_url = METAPS_CC_SALES_USER_URL;
		$response    = $metaps_request->metaps_post_request( $order, $connect_url, $setting_data, $this->emv_tds );
		if ( isset( $response[0] ) && substr( $response[0], 0, 2 ) == 'OK' ) {
			// Payment complete.
			if ( $order->get_status() != 'pending' ) {
				$order->payment_complete();
			}
		} else {
			$order->update_status( 'cancelled', __( 'This order is cancelled, because of Payment error.', 'metaps-for-wc' ) . mb_convert_encoding( $response[2], 'UTF-8', 'sjis' ) );
		}
		return true;
	}
	/**
	 * Scheduled_subscription_payment function.
	 *
	 * @param float    $amount_to_charge The amount to charge.
	 * @param WC_Order $renewal_order A WC_Order object created to record the renewal payment.
	 */
	public function scheduled_subscription_payment( $amount_to_charge, $renewal_order ) {
		$result = $this->process_subscription_payment( $renewal_order, $amount_to_charge );

		if ( is_wp_error( $result ) ) {
			$renewal_order->update_status( 'failed', sprintf( __( 'metaps Payment Transaction Failed (%s)', 'woocommerce' ), $result->get_error_message() ) );
		}
	}
}
