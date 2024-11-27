<?php
/**
 * WC Gateway Metaps Endpoint
 *
 * @package Metaps
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC_Gateway_Metaps_Endpoint class.
 */
class WC_Gateway_Metaps_Endpoint {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'metaps_register_routes' ) );
	}

	/**
	 * Callback.
	 */
	public function metaps_register_routes() {
		// POST /wp-json/metaps/v1/check_payment .
		register_rest_route(
			'metaps/v1',
			'/check_payment',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'metaps_check_webhook' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Check webhook.
	 *
	 * @param WP_REST_Request $request Request object.
	 */
	public function metaps_check_webhook( $request ) {
		$get_data = $request->get_params();

		if ( isset( $get_data['SID'] ) ) {

			// Create a response object.
			$response = new WP_REST_Response();
			// Set the status code (if necessary).
			$response->set_status( 302 );
			// Set Header.
			$empty_url = METAPS_FOR_WC_URL . 'empty.php';
			$response->header( 'Location', $empty_url );

			// Get the order ID.
			$pd_order_id     = sanitize_text_field( wp_unslash( $get_data['SID'] ) );
			$metaps_settings = get_option( 'woocommerce_metaps_settings' );
			$prefix_order    = $metaps_settings['prefixorder'];
			$order_id        = str_replace( $prefix_order, '', $pd_order_id );
			$order           = wc_get_order( $order_id );

			if ( ! $order ) {
				wc_get_logger()->error( __( 'Metaps Webhook Received.', 'metaps-for-woocommerce' ) . __( 'Order not found.', 'metaps-for-woocommerce' ) . __( 'Order ID:', 'metaps-for-woocommerce' ) . $order_id, array( 'get_data' => $get_data ) );
				return $response;
			}
			if ( ! empty( $get_data['SEQ'] ) ) {
				$order->add_order_note(
					// translators: %s: Notification number(SEQ).
					sprintf( __( 'Metaps Webhook Received. Notification number(SEQ): %s', 'metaps-for-woocommerce' ), $get_data['SEQ'] )
				);
			}

			$order_status         = $order->get_status();
			$order_payment_method = $order->get_payment_method();

			// Check the payment method.
			if ( 'metaps_pe' === $order_payment_method ) {// Payeasey received from metaps.
				if ( isset( $get_data['TIME'] ) && isset( $get_data['SEQ'] ) && isset( $order_status ) && 'processing' !== $order_status ) {
					/**
					 * Payment completion (deposit) notification
					 * The received parameters are:
					 * SEQ, DATE, TIME, IP, SID, KINGAKU, CVS, SCODE, FUKA
					 */

					// Mark as processing (payment complete).
					$order->update_status(
						'processing',
						// translators: %s: Payment method name.
						sprintf( __( 'Payment of %s was complete.', 'metaps-for-woocommerce' ), __( 'Payeasey Payment (metaps)', 'metaps-for-woocommerce' ) ) .
						__( 'The site has received a payment completion (deposit) notification from Metaps.', 'metaps-for-woocommerce' )
					);
					$this->metaps_get_logger( $order_payment_method, __( ': payment complete', 'metaps-for-woocommerce' ), $get_data );
					return $response;
				} elseif ( isset( $get_data['TIME'] ) && isset( $order_status ) && 'on-hold' !== $order_status ) {
					/**
					 * Payment apply notification
					 * The received parameters are:
					 * DATE, TIME, SID, KINGAKU, CVS, SHNO, FUKA, FEE, FURL
					 */

					// Mark as on-hold.
					$order->update_status(
						'on-hold',
						// translators: %s: Payment method name.
						sprintf( __( 'Payment of %s was cancelled.', 'metaps-for-woocommerce' ), __( 'Payeasey Payment (metaps)', 'metaps-for-woocommerce' ) ) .
						__( 'The site has received a payment completion (deposit) notification from Metaps.', 'metaps-for-woocommerce' )
					);
					$this->metaps_get_logger( $order_payment_method, __( ': payment apply', 'metaps-for-woocommerce' ), $get_data );
					return $response;
				}
			} elseif ( 'metaps_cc' === $order_payment_method || 'metaps_cc_token' === $order_payment_method ) {// Credit Card received from metaps.
				if ( isset( $get_data['TIME'] ) && isset( $get_data['SEQ'] ) && isset( $order_status ) && 'processing' !== $order_status ) {
					/**
					 * Payment completion (deposit) notification
					 * The received parameters are:
					 * SEQ, DATE, TIME, SID, KINGAKU, CVS, SCODE, SHONIN, HISHIMUKE, FUKA, PAYMODE, INCOUNT, IP_USER_ID
					 */

					// Mark as processing (payment complete).
					$order->payment_complete( $get_data['SID'] );
					$order->update_status(
						'processing',
						// translators: %s is the payment method name.
						sprintf( __( 'Payment of %s was complete.', 'metaps-for-woocommerce' ), __( 'Credit Card (metaps)', 'metaps-for-woocommerce' ) ) .
						__( 'The site has received a payment completion (deposit) notification from Metaps.', 'metaps-for-woocommerce' )
					);
					if ( isset( $get_data['IP_USER_ID'] ) && ! empty( $get_data['IP_USER_ID'] ) ) {
						update_user_meta( $order->get_user_id(), '_metaps_user_id', $get_data['IP_USER_ID'] );
					}
					$this->metaps_get_logger( $order_payment_method, __( ': payment complete', 'metaps-for-woocommerce' ), $get_data );
					return $response;
				} elseif ( isset( $get_data['TIME'] ) && isset( $get_data['SHNO'] ) && isset( $order_status ) ) {
					/**
					 * Payment apply notification
					 * The received parameters are:
					 * DATE, TIME, SID, KINGAKU, CVS, SHNO, FUKA
					 */

					if ( 'on-hold' !== $order_status && 'processing' !== $order_status ) {
						// Mark as on-hold.
						$order->update_status(
							'on-hold',
							// translators: %s: Payment method name.
							sprintf( __( 'Payment of %s was complete.', 'metaps-for-woocommerce' ), __( 'Credit Card (metaps)', 'metaps-for-woocommerce' ) ) .
							__( 'The site has received a payment completion (deposit) notification from Metaps.', 'metaps-for-woocommerce' )
						);
					}
					$this->metaps_get_logger( $order_payment_method, __( ': payment apply', 'metaps-for-woocommerce' ), $get_data );
					return $response;
				}
			} elseif ( 'metaps_cs' === $order_payment_method ) {// Convinience Store payment received from metaps.
				if ( isset( $get_data['TIME'] ) && isset( $get_data['SEQ'] ) && isset( $order_status ) && 'processing' !== $order_status ) {
					/**
					 * Payment completion (deposit) notification
					 * The received parameters are:
					 * SEQ, DATE, TIME, IP, SID, KINGAKU, CVS, SCODE, FUKA
					 */

					// Mark as processing (payment complete).
					$order->payment_complete();
					$order->update_status(
						'processing',
						// translators: %s: Payment method name.
						sprintf( __( 'Payment of %s was complete.', 'metaps-for-woocommerce' ), __( 'Convenience Store Payment (metaps)', 'metaps-for-woocommerce' ) ) .
						__( 'The site has received a payment completion (deposit) notification from Metaps.', 'metaps-for-woocommerce' )
					);
					$this->metaps_get_logger( $order_payment_method, __( ': payment complete', 'metaps-for-woocommerce' ), $get_data );
					return $response;
				} elseif ( ! isset( $get_data['TIME'] ) && isset( $get_data['SEQ'] ) && isset( $order_status ) && 'cancelled' !== $order_status ) {
					/**
					 * Payment cancellation notification
					 * The received parameters are:
					 * SEQ, DATE, IP, SID, KINGAKU, FUKA
					 */

					// Mark as cancel (payment cancelled).
					$order->update_status(
						'cancelled',
						// translators: %s: Payment method name.
						sprintf( __( 'Payment of %s was cancelled.', 'metaps-for-woocommerce' ), __( 'Convenience Store Payment (metaps)', 'metaps-for-woocommerce' ) ) .
						__( 'The site has received a payment completion (deposit) notification from Metaps.', 'metaps-for-woocommerce' )
					);
					$this->metaps_get_logger( $order_payment_method, __( ': cancelled', 'metaps-for-woocommerce' ), $get_data );
					return $response;
				} elseif ( isset( $get_data['TIME'] ) && isset( $get_data['FURL'] ) && isset( $order_status ) ) {
					/**
					 * Payment apply notification
					 * The received parameters are:
					 * DATE, TIME, SID, KINGAKU, CVS, SHNO, FUKA, FEE, FURL
					 */

					if ( 'on-hold' !== $order_status ) {
						// Mark as on-hold.
						$order->update_status(
							'on-hold',
							// translators: %s: Payment method name.
							sprintf( __( 'Payment of %s was applied.', 'metaps-for-woocommerce' ), __( 'Convenience Store Payment (metaps)', 'metaps-for-woocommerce' ) ) .
							__( 'The site has received a payment completion (deposit) notification from Metaps.', 'metaps-for-woocommerce' )
						);
					}
					$this->metaps_get_logger( $order_payment_method, __( ': payment apply', 'metaps-for-woocommerce' ), $get_data );
					return $response;
				}
			}
			wc_get_logger()->error( 'Metaps Webhook Received. Something is wrong.', array( 'get_data' => $get_data ) );
			return $response;
		} else {
			wc_get_logger()->error( 'Metaps Webhook Received. No order ID.', array( 'get_data' => $get_data ) );
			return new WP_REST_Response( $request, 400 );
		}
	}

	/**
	 * Log Metaps webhook data if debugging is enabled.
	 *
	 * @param string $peyment_method The payment method.
	 * @param string $add_message    The log message.
	 * @param array  $get_data       The data received from the webhook.
	 */
	private static function metaps_get_logger( $peyment_method, $add_message, $get_data ) {
		$peyment_method_settings = get_option( 'woocommerce_' . $peyment_method . '_settings' );
		if ( isset( $peyment_method_settings['debug'] ) && 'yes' === $peyment_method_settings['debug'] ) {
			$message  = __( 'Metaps Webhook Received.', 'metaps-for-woocommerce' ) . "\n";
			$message .= __( 'Payment Method: ', 'metaps-for-woocommerce' ) . $peyment_method . $add_message . "\n";
			wc_get_logger()->debug( $message, array( 'get_data' => $get_data ) );
		}
	}
}
