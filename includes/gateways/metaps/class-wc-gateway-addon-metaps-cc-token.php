<?php
/**
 * Addon Metaps Credit Card Token Payment Gateway.
 *
 * @package WooCommerce\Gateways
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class WC_Gateway_Addon_Metaps_CC_TOKEN
 *
 * Extends the Metaps Credit Card Token payment gateway for WooCommerce Subscriptions.
 * Handles tokenized credit card payments for recurring payments and subscriptions.
 *
 * @package WooCommerce\Gateways
 * @extends WC_Gateway_Metaps_CC_TOKEN
 * @since 1.0.0
 */
class WC_Gateway_Addon_Metaps_CC_TOKEN extends WC_Gateway_Metaps_CC_TOKEN {

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
	 * @param  int $order_id Order ID.
	 * @return bool
	 */
	protected function order_contains_subscription( $order_id ) {
		return function_exists( 'wcs_order_contains_subscription' ) && ( wcs_order_contains_subscription( $order_id ) || wcs_order_contains_renewal( $order_id ) );
	}

	/**
	 * Is $order_id a subscription?
	 *
	 * @param  int $order_id Order ID.
	 * @return boolean
	 */
	protected function is_subscription( $order_id ) {
		return ( function_exists( 'wcs_order_contains_subscription' ) && ( wcs_order_contains_subscription( $order_id ) || wcs_is_subscription( $order_id ) || wcs_order_contains_renewal( $order_id ) ) );
	}

	/**
	 * Process the subscription.
	 *
	 * @param  WC_Order $order Order object.
	 * @param  string   $subscription Subscription ID.
	 */
	protected function process_subscription( $order, $subscription = false ) {
		$payment_response = $this->process_subscription_payment( $order, $order->get_total() );
		return;
	}

	/**
	 * Process the payment.
	 *
	 * @param  int    $order_id Order ID.
	 * @param  string $subscription Subscription ID.
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
	 * Add process_subscription_payment function.
	 *
	 * @param WC_order $order (default: '') The order object.
	 * @param int      $amount (default: 0) The amount to charge.
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
		$metaps          = new WC_Gateway_Metaps_CC_TOKEN();

		$order_id = $order->get_id();

		// Setting $send_data.
		$setting_data               = array();
		$setting_data['ip_user_id'] = $prefix_order . $order->get_user_id();
		$setting_data['ip']         = $metaps->ip_code;
		$setting_data['pass']       = $metaps->pass_code;
		$setting_data['lang']       = '0';// Use Language 0 = Japanese, 1 = English.
		$setting_data['sid']        = $prefix_order . $order_id;
		$setting_data['paymode']    = 10;
		if ( 'sale' === $metaps->paymentaction ) {
			$setting_data['kakutei'] = '1';// capture = 1.
		} else {
			$setting_data['kakutei'] = '0';// auth = 0.
		}
		$connect_url = METAPS_CC_SALES_USER_URL;
		$response    = $metaps_request->metaps_post_request( $order, $connect_url, $setting_data );
		if ( isset( $response[0] ) && substr( $response[0], 0, 2 ) === 'OK' ) {
			// Payment complete.
			if ( $order->get_status() !== 'pending' ) {
				$order->payment_complete();
			}
		} else {
			$order->add_order_note( __( 'Payment error:', 'metaps-for-woocommerce' ) . mb_convert_encoding( $response[2], 'UTF-8', 'sjis' ) );
			$error_message = __( 'This order is cancelled, because of Payment error.', 'metaps-for-woocommerce' ) . mb_convert_encoding( $response[2], 'UTF-8', 'sjis' );
			$order->update_status( 'cancelled', $error_message );
		}
		return true;
	}
	/**
	 * Add scheduled_subscription_payment function.
	 *
	 * @param float    $amount_to_charge The amount to charge.
	 * @param WC_Order $renewal_order A WC_Order object created to record the renewal payment.
	 */
	public function scheduled_subscription_payment( $amount_to_charge, $renewal_order ) {
		$result = $this->process_subscription_payment( $renewal_order, $amount_to_charge );

		if ( is_wp_error( $result ) ) {
			/* translators: %s: error message */
			$renewal_order->update_status( 'failed', sprintf( __( 'metaps Payment Transaction Failed (%s)', 'metaps-for-woocommerce' ), $result->get_error_message() ) );
		}
	}
}
