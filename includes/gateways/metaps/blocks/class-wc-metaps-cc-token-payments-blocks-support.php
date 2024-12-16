<?php
/**
 * Metaps Credit Card Token Payments Blocks integration file.
 *
 * @package Metaps_For_WC
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Metaps Credit Card Payments Blocks integration.
 *
 * @since 1.0.3
 */
final class WC_Metaps_CC_Token_Payments_Blocks_Support extends AbstractPaymentMethodType {

	/**
	 * The gateway instance.
	 *
	 * @var WC_Gateway_Metaps_CC_Token
	 */
	private $gateway;

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'metaps_cc_token';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_metaps_cc_token_settings', array() );
		$gateways       = WC()->payment_gateways->payment_gateways();
		$this->gateway  = $gateways[ $this->name ];
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->gateway->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$script_path       = 'includes/gateways/metaps/asset/frontend/payments/creditcardtoken.js';
		$script_asset_path = METAPS_FOR_WC_INCLUDES_PATH . 'gateways/metaps/asset/frontend/payments/creditcardtoken.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => '1.0.0',
			);
		$script_url        = METAPS_FOR_WC_URL . $script_path;

		wp_register_script(
			'wc-metaps-cc-token-payments-blocks',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
		if ( is_checkout() ) {
			wp_enqueue_style(
				'wc-metaps-cc-token-payments-blocks',
				METAPS_FOR_WC_URL . 'includes/gateways/metaps/asset/frontend/payments/creditcardtoken.css',
				array(),
				$script_asset['version']
			);
		}

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'wc-metaps-cc-token-payments-blocks', 'metaps-for-woocommerce', METAPS_FOR_WC_DIR . 'i18n/' );
		}

		return array( 'wc-metaps-cc-token-payments-blocks' );
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		$number_of_payments = $this->get_setting( 'number_of_payments' );
		if ( empty( $number_of_payments ) ) {
			$number_of_payments = array();
		}
		$array                  = $this->gateway->array_number_of_payments;
		$set_number_of_payments = array();
		foreach ( $number_of_payments as $value ) {
			$set_number_of_payments[] = array(
				'id'    => $value,
				'value' => $array[ $value ],
			);
		}
		return array(
			'title'              => $this->get_setting( 'title' ),
			'description'        => $this->get_setting( 'description' ),
			'user_id_payment'    => $this->get_setting( 'user_id_payment' ),
			'payment_time_text'  => $this->get_setting( 'payment_time_text' ),
			'number_of_payments' => $set_number_of_payments,
			'supports'           => array_filter( $this->gateway->supports, array( $this->gateway, 'supports' ) ),
		);
	}
}
