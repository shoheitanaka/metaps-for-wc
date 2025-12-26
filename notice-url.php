<?php
/**
 * Notice URL handling functionality for Metaps for WooCommerce plugin
 *
 * @package Metaps_For_WooCommerce
 */

// To avoid HTTP status 404 code.
// status_header( 200 );

if ( isset( $_GET['SEQ'] ) && isset( $_GET['DATE'] ) && isset( $_GET['SID'] ) ) {
	require '../../../wp-blog-header.php';
	global $wpdb;
	global $woocommerce;

	$pd_order_id   = $_GET['SID'];// phpcs:ignore.
	$metaps_settings      = get_option( 'woocommerce_metaps_settings' );
	$prefix_order         = $metaps_settings['prefixorder'];
	$order_id             = str_replace( $prefix_order, '', $pd_order_id );
	$current_order        = wc_get_order( $order_id );
	$order_type           = get_post_type( $order_id );
	$order_payment_method = $current_order->get_payment_method();
	$order_status         = $current_order->get_status();
	// Logger object.
	$wc_logger = new WC_Logger();
	// Add to logger.
	$get_message = '';
	foreach ( $_GET as $key => $value ) {
		$get_message .= $key . ':' . $value . ',';
	}
	/* translators: %s: GET parameters received from metaps */
	$message = sprintf( __( 'I received GET data from metaps. (%s)', 'metaps-for-woocommerce' ), $get_message );
	if ( 'shop_order' !== $order_type && 'shop_order_placehold' !== $order_type ) {
		// Add to logger.
		/* translators: %s: Order ID */
		$message = sprintf( __( 'This order number (%s) does not exist..', 'metaps-for-woocommerce' ) . $order_type, $pd_order_id );
		$wc_logger->add( 'error-metaps', $message );
		header( 'Content-Type: text/plain; charset=Shift_JIS' );
		print '9';
		exit();
	}
	if ( isset( $_GET['TIME'] ) && isset( $order_status ) && ( 'on-hold' === $order_status || 'pending' === $order_status ) ) {
		$payment_title = __( 'Credit Card (Metaps)', 'metaps-for-woocommerce' );
		if ( 'metaps_cs' === $order_payment_method || 'metaps_pe' === $order_payment_method ) {
			$email                 = WC()->mailer();
			$emails                = $email->get_emails();
			$send_processing_email = $emails['WC_Email_Customer_Processing_Order'];// require php file.
			if ( 'metaps_cs' === $order_payment_method ) {
				$payment_title = __( 'Convenience Store Payment (Metaps)', 'metaps-for-woocommerce' );
			} elseif ( 'metaps_pe' === $order_payment_method ) {
				$payment_title = __( 'Payeasey Payment (Metaps)', 'metaps-for-woocommerce' );
			}
		} else {
			$payment_cc_setting       = null;
			$payment_cc_token_setting = null;
			if ( class_exists( 'WC_Gateway_Metaps_CC' ) ) {
				$payment_cc_setting = new WC_Gateway_Metaps_CC();
			}
			if ( class_exists( 'WC_Gateway_Metaps_CC_TOKEN' ) ) {
				$payment_cc_token_setting = new WC_Gateway_Metaps_CC_TOKEN();
			}
			if ( ( isset( $payment_cc_setting->user_id_payment ) && 'yes' === $payment_cc_setting->user_id_payment ) || ( isset( $payment_cc_token_setting->user_id_payment ) && 'yes' === $payment_cc_token_setting->user_id_payment ) ) {
				update_user_meta( $current_order->get_user_id(), '_metaps_user_id', $prefix_order . $current_order->get_user_id() );
			}
		}
		if ( 'completed' !== $current_order->get_status() ) {
			// set transaction id for Approval number.
			if ( isset( $_GET['SHONIN'] ) ) {
				$current_order->payment_complete( wc_clean( wp_unslash( $_GET['SHONIN'] ) ) );// phpcs:ignore.
			} else {
				$current_order->payment_complete();
			}
			$current_order->set_status( 'processing' );
			$current_order->save();
		}

		header( 'Content-Type: text/plain; charset=Shift_JIS' );
		print '0';
	} elseif ( isset( $_GET['TIME'] ) && isset( $order_status ) && 'processing' === $order_status ) {
		/* translators: %s: order ID */
		$message = sprintf( __( 'This order (%s)  has already been paid.', 'metaps-for-woocommerce' ), $pd_order_id );
		$wc_logger->add( 'error-metaps', $message );
		header( 'Content-Type: text/plain; charset=Shift_JIS' );
		print '9';
	} elseif ( isset( $_GET['TIME'] ) && isset( $order_status ) && 'completed' === $order_status ) {
		/* translators: %s: order ID */
		$message = sprintf( __( 'This order (%s) has already completed.', 'metaps-for-woocommerce' ), $pd_order_id );
		$wc_logger->add( 'error-metaps', $message );
		header( 'Content-Type: text/plain; charset=Shift_JIS' );
		print '9';
	} elseif ( isset( $_GET['TIME'] ) && isset( $order_status ) && 'cancelled' === $order_status ) {
		/* translators: %s: order ID */
		$message = sprintf( __( 'This order (%s) has already Cancelled.', 'metaps-for-woocommerce' ), $pd_order_id );
		$wc_logger->add( 'error-metaps', $message );
		header( 'Content-Type: text/plain; charset=Shift_JIS' );
		print '9';
	} elseif ( isset( $_GET['TIME'] ) && isset( $order_status ) && 'refunded' === $order_status ) {
		/* translators: %s: order ID */
		$message = sprintf( __( 'This order (%s) has already Refunded.', 'metaps-for-woocommerce' ), $pd_order_id );
		$wc_logger->add( 'error-metaps', $message );
		header( 'Content-Type: text/plain; charset=Shift_JIS' );
		print '9';
	}
} else {
	header( 'Content-Type: text/plain; charset=Shift_JIS' );
	print '9';
}
