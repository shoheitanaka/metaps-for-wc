<?php
/**
 * Metaps for WooCommerce Uninstall
 *
 * @version 1.0.0
 * @package Metaps_For_WooCommerce
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Exit if uninstall not called from WordPress.
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Deletes Metaps options and files.
 */
function metaps_for_woocommerce_delete_plugin() {
	// delete option settings.
	$metaps_methods = array(
		'cc-token', // cc: Credit Card Token.
		'cc', // cc: Credit Card.
		'cs', // Convenience store.
		'pe', // Pay-Easy.
	);
	foreach ( $metaps_methods as $metaps_method ) {
		$setting_method = 'woocommerce_metaps_' . $metaps_method . '_settings';
		delete_option( $setting_method );
	}
	delete_option( 'woocommerce_metaps_settings' );
}

metaps_for_woocommerce_delete_plugin();
