<?php
/**
 * Admin Screen Metaps
 *
 * @category Payment_Gateway
 * @package  Metaps_For_WC
 * @author   Shohei Tanaka
 * @license  GPL-3.0 https://www.gnu.org/licenses/gpl-3.0.html
 * @version  0.9.3
 * @link     https://github.com/artisanworkshop/metaps-for-wc
 * @php      8.2.0
 */

namespace ArtisanWorkshop\Metaps\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Admin Screen Metaps.
 *
 * This class handles the admin screen for Metaps settings in WooCommerce.
 *
 * @category Payment_Gateway
 * @package  Metaps_For_WC
 * @author   Shohei Tanaka
 * @license  GPL-3.0 https://www.gnu.org/licenses/gpl-3.0.html
 */
class WC_Admin_Screen_Metaps {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'wc_admin_metaps_menu' ), 55 );
		// use WC Admin pages.
		add_action( 'admin_menu', array( $this, 'wc_admin_connect_metaps_pages' ) );
		add_action( 'init', array( $this, 'metaps_for_wc_settings' ) );
		/**
		 * Enqueue style and script.
		 */
		add_action( 'admin_enqueue_scripts', array( $this, 'wc_admin_metaps_settings_page_enqueue_style_script' ) );
	}

	/**
	 * Get screen id.
	 *
	 * @since 1.0.0
	 */
	public function get_screen_id() {
		return 'woocommerce_page_settings-metaps';
	}

	/**
	 * Add Metaps menu
	 */
	public function wc_admin_metaps_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Metaps Setting', 'metaps-for-woocommerce' ),
			__( 'Metaps Setting', 'metaps-for-woocommerce' ),
			'manage_woocommerce',
			'settings-metaps',
			array( $this, 'wc_admin_metaps_settings' )
		);
	}

	/**
	 * Metaps Setting
	 */
	public function wc_admin_metaps_settings() {
		printf(
			'<div class="wrap woocommerce"><div id="metaps-settings">%s</div></div>',
			esc_html__( 'Loading…', 'metaps-for-woocommerce' )
		);
	}

	/**
	 * Use WooCommerce Admin pages to display the WooCommerce Admin header
	 * and to load WooCommerce CSS and JS files.
	 *
	 * Reference: https://developer.woocommerce.com/docs/integrating-admin-pages-into-woocommerce-extensions/
	 */
	public function wc_admin_connect_metaps_pages() {
		if ( function_exists( 'wc_admin_connect_page' ) ) {
			wc_admin_connect_page(
				array(
					'id'        => $this->get_screen_id(),
					'screen_id' => $this->get_screen_id(),
					'title'     => __( 'Metaps Setting', 'metaps-for-woocommerce' ),
				)
			);
		}
	}

	/**
	 * Registers the setting and defines its type and default value.
	 */
	public function metaps_for_wc_settings() {
		$default = array(
			'prefixorder'              => '',
			'creditcardcheck'          => false,
			'creditcardtokencheck'     => false,
			'conveniencepaymentscheck' => false,
			'payeasypaymentcheck'      => false,
		);
		$schema  = array(
			'type'       => 'object',
			'properties' => array(
				'prefixorder'              => array(
					'type' => 'string',
				),
				'creditcardcheck'          => array(
					'type' => 'boolean',
				),
				'creditcardtokencheck'     => array(
					'type' => 'boolean',
				),
				'conveniencepaymentscheck' => array(
					'type' => 'boolean',
				),
				'payeasypaymentcheck'      => array(
					'type' => 'boolean',
				),
			),
		);

		register_setting(
			'options',
			'woocommerce_metaps_settings',
			array(
				'type'              => 'object',
				'default'           => $default,
				'sanitize_callback' => array( $this, 'metaps_for_wc_sanitize_settings' ),
				'show_in_rest'      => array(
					'schema' => $schema,
				),
			)
		);
	}

	/**
	 * Sanitize Metaps settings.
	 *
	 * @param mixed $input The input to sanitize.
	 * @return string Sanitized input.
	 */
	public function metaps_for_wc_sanitize_settings( $input ) {
		$input = (object) $input;
		$input = array_map( 'sanitize_text_field', (array) $input );
		return $input;
	}

	/**
	 * Enqueue style and script.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function wc_admin_metaps_settings_page_enqueue_style_script() {
		$screen = get_current_screen();
		if ( $this->get_screen_id() === $screen->id ) {
			$asset_file = METAPS_FOR_WC_DIR . 'includes/gateways/metaps/asset/admin/settings.asset.php';

			if ( ! file_exists( $asset_file ) ) {
				return;
			}

			$asset = include $asset_file;

			wp_enqueue_style(
				'metaps-for-wc-admin-style',
				METAPS_FOR_WC_URL . 'includes/gateways/metaps/asset/admin/settings.css',
				array_filter(
					$asset['dependencies'],
					function ( $style ) {
						return wp_style_is( $style, 'registered' );
					}
				),
				$asset['version'],
			);

			wp_enqueue_script(
				'metaps-for-wc-admin-script',
				METAPS_FOR_WC_URL . 'includes/gateways/metaps/asset/admin/settings.js',
				$asset['dependencies'],
				$asset['version'],
				array(
					'in_footer' => true,
				)
			);
			// Set translations.
			wp_set_script_translations(
				'metaps-for-wc-admin-script',
				'metaps-for-woocommerce',
				METAPS_FOR_WC_DIR . 'i18n'
			);

		}
	}
}
