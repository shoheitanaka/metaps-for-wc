<?php
/**
 * Metaps Pay-easy Payments Blocks integration file.
 *
 * @package Metaps_For_WC
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Metaps Pay-easy Payments Blocks integration.
 *
 * @since 1.0.0
 */
final class WC_Metaps_PE_Payments_Blocks_Support extends AbstractPaymentMethodType {

	/**
	 * The gateway instance.
	 *
	 * @var WC_Gateway_Metaps_PE
	 */
	private $gateway;

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'metaps_pe';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_metaps_pe_settings', array() );
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
		$script_path       = 'includes/gateways/metaps/asset/frontend/payments/payeasy.js';
		$script_asset_path = METAPS_FOR_WC_INCLUDES_PATH . 'gateways/metaps/asset/frontend/payments/payeasy.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => '1.0.0',
			);
		$script_url        = METAPS_FOR_WC_URL . $script_path;

		wp_register_script(
			'wc-metaps-pe-payments-blocks',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'wc-metaps-pe-payments-blocks', 'metaps-for-woocommerce', METAPS_FOR_WC_DIR . 'i18n/' );
		}

		return array( 'wc-metaps-pe-payments-blocks' );
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return array(
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'supports'    => array_filter( $this->gateway->supports, array( $this->gateway, 'supports' ) ),
		);
	}
}
