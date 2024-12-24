<?php
/**
 * Plugin Name: Metaps for WooCommerce
 * Framework Name: Artisan Workshop FrameWork for WooCommerce
 * Framework Version : 2.0.12
 * Author: Artisan Workshop
 * Author URI: https://wc.artws.info/
 * Text Domain: metaps-for-woocommerce
 *
 * @category JP4WC_Framework
 * @package MetapsForWooCommerce
 * @author Artisan Workshop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return apply_filters(
	'jp4wc_framework_config',
	array(
		// translators: %s: payment method name.
		'description_check_pattern'   => __( 'Please check it if you want to use %s.', 'metaps-for-woocommerce' ),
		// translators: %s: payment method name.
		'description_payment_pattern' => __( 'Please check it if you want to use the payment method of %s.', 'metaps-for-woocommerce' ),
		// translators: %s: input field name.
		'description_input_pattern'   => __( 'Please input %s.', 'metaps-for-woocommerce' ),
		// translators: %s: selection option.
		'description_select_pattern'  => __( 'Please select one from these as %s.', 'metaps-for-woocommerce' ),
		'support_notice_01'           => __( 'Need support?', 'metaps-for-woocommerce' ),
		// translators: %s: support forum link.
		'support_notice_02'           => __( 'If you are having problems with this plugin, talk about them in the <a href="%s" target="_blank" title="Pro Version">Support forum</a>.', 'metaps-for-woocommerce' ),
		// translators: %1$s: Site Construction Support service link, %2$s: Maintenance Support service link.
		'support_notice_03'           => __( 'If you need professional support, please consider about <a href="%1$s" target="_blank" title="Site Construction Support service">Site Construction Support service</a> or <a href="%2$s" target="_blank" title="Maintenance Support service">Maintenance Support service</a>.', 'metaps-for-woocommerce' ),
		'pro_notice_01'               => __( 'Pro version', 'metaps-for-woocommerce' ),
		// translators: %s: pro version link.
		'pro_notice_02'               => __( 'The pro version is available <a href="%s" target="_blank" title="Support forum">here</a>.', 'metaps-for-woocommerce' ),
		'pro_notice_03'               => __( 'The pro version includes support for bulletin boards. Please consider purchasing the pro version.', 'metaps-for-woocommerce' ),
		'update_notice_01'            => __( 'Finished Latest Update, WordPress and WooCommerce?', 'metaps-for-woocommerce' ),
		// translators: %s: support forum link.
		'update_notice_02'            => __( 'One the security, latest update is the most important thing. If you need site maintenance support, please consider about <a href="%s" target="_blank" title="Support forum">Site Maintenance Support service</a>.', 'metaps-for-woocommerce' ),
		'community_info_01'           => __( 'Where is the study group of Woocommerce in Japan?', 'metaps-for-woocommerce' ),
		// translators: %s: Tokyo WooCommerce Meetup link.
		'community_info_02'           => __( '<a href="%s" target="_blank" title="Tokyo WooCommerce Meetup">Tokyo WooCommerce Meetup</a>.', 'metaps-for-woocommerce' ),
		// translators: %s: Kansai WooCommerce Meetup link.
		'community_info_03'           => __( '<a href="%s" target="_blank" title="Kansai WooCommerce Meetup">Kansai WooCommerce Meetup</a>.', 'metaps-for-woocommerce' ),
		'community_info_04'           => __( 'Join Us!', 'metaps-for-woocommerce' ),
		'author_info_01'              => __( 'Created by', 'metaps-for-woocommerce' ),
		'author_info_02'              => __( 'WooCommerce Doc in Japanese', 'metaps-for-woocommerce' ),
		'framework_version'           => '2.0.12',
	)
);
