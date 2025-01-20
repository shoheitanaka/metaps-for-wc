<?php
/**
 * WC_Metaps_CS_Payments_Blocks_Support class file.
 *
 * @package  Metaps_For_WooCommerce
 * @category Payment_Gateway
 * @author   Shohei Tanaka
 * @license  GPL-3.0 https://www.gnu.org/licenses/gpl-3.0.html
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Metaps Convenience Store Payments Blocks integration.
 *
 * @since 0.9.3
 */
final class WC_Metaps_CS_Payments_Blocks_Support extends AbstractPaymentMethodType {

	/**
	 * The gateway instance.
	 *
	 * @var WC_Gateway_Metaps_CS
	 */
	private $gateway;

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'metaps_cs';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_metaps_cs_settings', array() );
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
		$script_path       = 'includes/gateways/metaps/asset/frontend/payments/conveniencestore.js';
		$script_asset_path = METAPS_FOR_WC_INCLUDES_PATH . 'gateways/metaps/asset/frontend/payments/conveniencestore.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => '1.0.0',
			);
		$script_url        = METAPS_FOR_WC_URL . $script_path;

		wp_register_script(
			'wc-metaps-cs-payments-blocks',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
		if ( is_checkout() ) {
			wp_enqueue_style(
				'wc-metaps-cs-payments-blocks',
				METAPS_FOR_WC_URL . 'includes/gateways/metaps/asset/frontend/payments/conveniencestore.css',
				array(),
				$script_asset['version'],
			);
		}

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'wc-metaps-cs-payments-blocks', 'metaps-for-woocommerce', METAPS_FOR_WC_DIR . 'i18n/' );
		}

		return array( 'wc-metaps-cs-payments-blocks' );
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return array(
			'title'         => $this->get_setting( 'title' ),
			'description'   => $this->get_setting( 'description' ),
			'setting_cs_sv' => $this->get_setting( 'setting_cs_sv' ),
			'setting_cs_lp' => $this->get_setting( 'setting_cs_lp' ),
			'setting_cs_fm' => $this->get_setting( 'setting_cs_fm' ),
			'setting_cs_sm' => $this->get_setting( 'setting_cs_sm' ),
			'setting_cs_ol' => $this->get_setting( 'setting_cs_ol' ),
			'supports'      => array_filter( $this->gateway->supports, array( $this->gateway, 'supports' ) ),
		);
	}
}
