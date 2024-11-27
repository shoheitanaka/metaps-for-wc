<?php
/**
 * Plugin Name: Metaps Payments for WooCommerce
 * Plugin URI: https://www.wordpress.org/plugins/metaps-for-woocommerce/
 * Description: Metaps for WooCommerce is a WooCommerce payment extention plugin.
 * Version: 0.9.1
 * Author: Shohei Tanaka
 * Author URI: https://wc.artws.info/
 * Text Domain: metaps-for-woocommerce
 * Domain Path: /i18n
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * WC requires at least: 8.0.0
 * WC tested up to: 9.4.2
 * Requires at least: 6.5.0
 * Tested up to: 6.7.1
 *
 * @package Metaps_for_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'METAPS_PLUGIN_FILE' ) ) {
	define( 'METAPS_PLUGIN_FILE', __FILE__ );
}

if ( ! class_exists( 'WC_Metaps_Payments' ) ) :

	/**
	 * Main class for Metaps for WooCommerce plugin.
	 *
	 * Handles the initialization and setup of the plugin.
	 */
	class WC_Metaps_Payments {
		/**
		 * Metaps PAYMENT version.
		 *
		 * @var string
		 */
		public $version = '0.9.1';

		/**
		 * Metaps PAYMENT for WooCommerce Framework version.
		 *
		 * @var string
		 */
		public $framework_version = '2.0.12';

		/**
		 * The single instance of the class.
		 *
		 * @var object
		 */
		protected static $instance = null;

		/**
		 * Metaps PAYMENT for WooCommerce Constructor.
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {
			// rated appeal.
			add_action( 'wp_ajax_metaps_for_wc_rated', array( __CLASS__, 'metaps_for_wc_rated' ) );
			add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
			// handle HPOS compatibility.
			add_action( 'before_woocommerce_init', array( $this, 'metaps_handle_hpos_compatibility' ) );
			add_action( 'init', array( $this, 'register_user_meta_metaps_user_id' ) );
		}

		/**
		 * Get class instance.
		 *
		 * @return object Instance.
		 */
		public static function instance() {
			if ( null === static::$instance ) {
				static::$instance = new static();
			}
			return static::$instance;
		}

		/**
		 * Init the feature plugin, only if we can detect WooCommerce.
		 *
		 * @since   2.0.0
		 * @version 2.0.0
		 */
		public function init() {
			$this->define_constants();
			register_deactivation_hook( METAPS_FOR_WC_PLUGIN_FILE, array( $this, 'on_deactivation' ) );
			$this->registers_wc_blocks();
			add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ), 20 );
		}

		/**
		 * Flush rewrite rules on deactivate.
		 *
		 * @return void
		 */
		public function on_deactivation() {
			flush_rewrite_rules();
		}

		/**
		 * Setup plugin once all other plugins are loaded.
		 *
		 * @return void
		 */
		public function on_plugins_loaded() {
			$this->load_plugin_textdomain();
			$this->includes();
		}

		/**
		 * Define Constants.
		 */
		protected function define_constants() {
			// metaps PAYMENT for WooCommerce version.
			$this->define( 'METAPS_FOR_WC_URL', plugin_dir_url( __FILE__ ) );
			$this->define( 'METAPS_FOR_WC_DIR', plugin_dir_path( __FILE__ ) );
			$this->define( 'METAPS_FOR_WC_ABSPATH', __DIR__ . '/' );
			$this->define( 'METAPS_FOR_WC_INCLUDES_PATH', METAPS_FOR_WC_ABSPATH . 'includes/' );
			$this->define( 'METAPS_FOR_WC_PLUGIN_FILE', __FILE__ );
			$this->define( 'METAPS_FOR_WC_VERSION', $this->version );
			$this->define( 'METAPS_FOR_WC_FRAMEWORK_VERSION', $this->framework_version );
			// Config Setting.
			$this->define( 'METAPS_URL', 'https://www.paydesign.jp/settle/' );
			// Credit Card.
			$this->define( 'METAPS_CC_SALES_URL', METAPS_URL . 'settle3/bp3.dll' );
			$this->define( 'METAPS_CC_SALES_USER_URL', METAPS_URL . 'settlex/credit2.dll' );
			$this->define( 'METAPS_CC_SALES_COMP_URL', METAPS_URL . 'Fixation/crDkakutei.dll' );
			$this->define( 'METAPS_CC_SALES_CANCEL_URL', METAPS_URL . 'Fixation/canauthp.dll' );
			$this->define( 'METAPS_CC_SALES_REFUND_URL', METAPS_URL . 'Fixation/cantorip.dll' );
			$this->define( 'METAPS_CC_SALES_AUTH_URL', METAPS_URL . 'inquiry/reskaricr.dll' );
			$this->define( 'METAPS_CC_SALES_CHECK_URL', METAPS_URL . 'inquiry/result3.dll' );
			// Convenience Store.
			$this->define( 'METAPS_CS_SALES_URL', METAPS_URL . 'settle2/ubp3.dll' );
			$this->define( 'METAPS_CS_CANCEL_URL', METAPS_URL . 'Fixation/can_cvs.dll' );
		}

		/**
		 * Define constant if not already set.
		 *
		 * @param string      $name  Constant name.
		 * @param string|bool $value Constant value.
		 */
		protected function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Registers WooCommerce Blocks integration.
		 */
		protected function registers_wc_blocks() {
			// Registers WooCommerce Blocks integration.
			add_action( 'woocommerce_blocks_loaded', array( __CLASS__, 'woocommerce_gateway_metaps_cc_woocommerce_block_support' ) );
		}
		/**
		 * Load Localisation files.
		 */
		protected function load_plugin_textdomain() {
			load_plugin_textdomain( 'metaps-for-woocommerce', false, basename( __DIR__ ) . '/i18n' );
		}

		/**
		 * Include JP4WC classes.
		 */
		private function includes() {
			// Load framework.
			$version_text = 'v' . str_replace( '.', '_', METAPS_FOR_WC_FRAMEWORK_VERSION );
			if ( ! class_exists( '\\ArtisanWorkshop\\PluginFramework\\' . $version_text . '\\JP4WC_Framework' ) ) {
				include_once METAPS_FOR_WC_INCLUDES_PATH . 'jp4wc-framework/class-jp4wc-framework.php';
			}
			// Admin Setting Screen.
			include_once METAPS_FOR_WC_INCLUDES_PATH . 'admin/class-wc-admin-screen-metaps.php';
			new ArtisanWorkshop\Metaps\Admin\WC_Admin_Screen_Metaps();

			// Admin Notice
			// require_once METAPS_FOR_WC_INCLUDES_PATH . 'gateways/metaps/class-wc-metaps-admin-notices.php';.

			// Metaps gateway for endpoint.
			include_once METAPS_FOR_WC_INCLUDES_PATH . 'gateways/metaps/includes/class-wc-gateway-metaps-endpoint.php';
			new WC_Gateway_Metaps_Endpoint();

			// metaps PAYMENT Payment Gateway.
			$metaps_settings = get_option( 'woocommerce_metaps_settings' );
			if ( isset( $metaps_settings['creditcardcheck'] ) && $metaps_settings['creditcardcheck'] ) {
				include_once METAPS_FOR_WC_INCLUDES_PATH . 'gateways/metaps/class-wc-gateway-metaps-cc.php'; // Credit Card.
				// Credit Card Subscription.
				include_once METAPS_FOR_WC_INCLUDES_PATH . 'gateways/metaps/class-wc-gateway-addon-metaps-cc.php';
				add_filter( 'woocommerce_payment_gateways', array( $this, 'add_wc_metaps_cc_gateway' ) );
			}
			if ( isset( $metaps_settings['creditcardtokencheck'] ) && $metaps_settings['creditcardtokencheck'] ) {
				include_once METAPS_FOR_WC_INCLUDES_PATH . 'gateways/metaps/class-wc-gateway-metaps-cc-token.php'; // Credit Card with Token.
				// Credit Card Token Subscription.
				// include_once METAPS_FOR_WC_INCLUDES_PATH . 'gateways/metaps/class-wc-gateway-addon-metaps-cc-token.php'; // Credit Card with Token Subscription.
				add_filter( 'woocommerce_payment_gateways', array( $this, 'add_wc_metaps_cc_token_gateway' ) );
			}
			if ( isset( $metaps_settings['conveniencepaymentscheck'] ) && $metaps_settings['conveniencepaymentscheck'] ) {
				include_once METAPS_FOR_WC_INCLUDES_PATH . 'gateways/metaps/class-wc-gateway-metaps-cs.php'; // Convenience store.
				add_filter( 'woocommerce_payment_gateways', array( $this, 'add_wc_metaps_cs_gateway' ) );
			}
			if ( isset( $metaps_settings['payeasypaymentcheck'] ) && $metaps_settings['payeasypaymentcheck'] ) {
				include_once METAPS_FOR_WC_INCLUDES_PATH . 'gateways/metaps/class-wc-gateway-metaps-pe.php'; // Pay-Easy.
				add_filter( 'woocommerce_payment_gateways', array( $this, 'add_wc_metaps_pe_gateway' ) );
				include_once METAPS_FOR_WC_INCLUDES_PATH . 'class-wc-jp4wc-checkout-add-fields.php'; // Pay-Easy Checkout Add Fields.
			}
		}

		/**
		 * Add the metaps Credit Card Payment gateway to woocommerce
		 *
		 * @param  array $methods The payment methods.
		 */
		public function add_wc_metaps_cc_token_gateway( $methods ) {
			$subscription_support_enabled = false;
			if ( class_exists( 'WC_Subscriptions_Order' ) && function_exists( 'wcs_create_renewal_order' ) ) {
				$subscription_support_enabled = true;
			}
			if ( $subscription_support_enabled ) {
				$methods[] = 'WC_Addon_Gateway_METAPS_CC_TOKEN';
			} else {
				$methods[] = 'WC_Gateway_METAPS_CC_TOKEN';
			}
			return $methods;
		}

		/**
		 * Add the metaps Credit Card Payment gateway to woocommerce
		 *
		 * @param  array $methods The payment methods.
		 */
		public function add_wc_metaps_cc_gateway( $methods ) {
			$subscription_support_enabled = false;
			if ( class_exists( 'WC_Subscriptions_Order' ) && function_exists( 'wcs_create_renewal_order' ) ) {
				$subscription_support_enabled = true;
			}
			if ( $subscription_support_enabled ) {
				$methods[] = 'WC_Gateway_Metaps_CC_Addons';
			} else {
				$methods[] = 'WC_Gateway_Metaps_CC';
			}
			return $methods;
		}

		/**
		 * Add the metaps Convenience Payment gateway to woocommerce
		 *
		 * @param  array $methods The payment methods.
		 */
		public function add_wc_metaps_cs_gateway( $methods ) {
			$methods[] = 'WC_Gateway_Metaps_CS';
			return $methods;
		}

		/**
		 * Add the metaps Pay-easy Payment gateway to woocommerce
		 *
		 * @param  array $methods The payment methods.
		 */
		public function add_wc_metaps_pe_gateway( $methods ) {
			$methods[] = 'WC_Gateway_Metaps_PE';
			return $methods;
		}

		/**
		 * Change the admin footer text on WooCommerce for Japan admin pages.
		 *
		 * @since  1.1
		 * @param  string $footer_text The existing footer text.
		 * @return string
		 */
		public function admin_footer_text( $footer_text ) {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return null;
			}
			$current_screen      = get_current_screen();
			$metaps_for_wc_pages = 'woocommerce_page_settings-metaps';
			// Check to make sure we're on a WooCommerce admin page.
			if ( isset( $current_screen->id ) && $current_screen->id === $metaps_for_wc_pages ) {
				if ( ! get_option( 'metaps_for_wc_footer_text_rated' ) ) {
					// translators: %1$s and %2$s are placeholders for the opening and closing anchor tags respectively.
					$footer_text = sprintf( __( 'If you like <strong>metaps PAYMENT for WooCommerce.</strong> please leave us a %1$s&#9733;&#9733;&#9733;&#9733;&#9733;%2$s rating. A huge thanks in advance!', 'metaps-for-woocommerce' ), '<a href="https://wordpress.org/support/plugin/woocommerce-for-japan/reviews/#postform" target="_blank" class="wc4jp-rating-link" data-rated="' . esc_attr__( 'Thanks :)', 'woocommerce-for-japan' ) . '">', '</a>' );
					wc_enqueue_js(
						"
						jQuery( 'a.wc4jp-rating-link' ).click( function() {
							jQuery.post( '" . WC()->ajax_url() . "', { action: 'metaps_for_wc_rated' } );
							jQuery( this ).parent().text( jQuery( this ).data( 'rated' ) );
						});
					"
					);
				} else {
					$footer_text = __( 'Thank you for selling with WooCommerce for metaps PAYMENT.', 'metaps-for-woocommerce' );
				}
			}
			return $footer_text;
		}

		/**
		 * Triggered when clicking the rating footer.
		 */
		public static function metaps_for_wc_rated() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				die( -1 );
			}

			update_option( 'metaps_for_wc_admin_footer_text_rated', 1 );
			die();
		}

		/**
		 * Declares HPOS compatibility if the plugin is compatible with HPOS.
		 *
		 * @internal
		 *
		 * @since 2.6.0
		 */
		public function metaps_handle_hpos_compatibility() {

			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				$slug = dirname( plugin_basename( __FILE__ ) );
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', trailingslashit( $slug ) . $slug . '.php', true );
			}
		}

		/**
		 * Registers WooCommerce Blocks integration for metaps Payments.
		 */
		public static function woocommerce_gateway_metaps_cc_woocommerce_block_support() {
			if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
				$metaps_settings = get_option( 'woocommerce_metaps_settings' );
				// Credit Card.
				if ( isset( $metaps_settings['creditcardcheck'] ) && $metaps_settings['creditcardcheck'] ) {
					require_once 'includes/gateways/metaps/blocks/class-wc-metaps-cc-payments-blocks-support.php';
					add_action(
						'woocommerce_blocks_payment_method_type_registration',
						function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
							$payment_method_registry->register( new WC_Metaps_CC_Payments_Blocks_Support() );
						}
					);
				}
				// Credit Card Token.
				if ( isset( $metaps_settings['creditcardtokencheck'] ) && $metaps_settings['creditcardtokencheck'] ) {
					require_once 'includes/gateways/metaps/blocks/class-wc-metaps-cc-token-payments-blocks-support.php';
					add_action(
						'woocommerce_blocks_payment_method_type_registration',
						function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
							$payment_method_registry->register( new WC_Metaps_CC_Token_Payments_Blocks_Support() );
						}
					);
				}
				// Convenience Store.
				if ( isset( $metaps_settings['conveniencepaymentscheck'] ) && $metaps_settings['conveniencepaymentscheck'] ) {
					require_once 'includes/gateways/metaps/blocks/class-wc-metaps-cs-payments-blocks-support.php';
					add_action(
						'woocommerce_blocks_payment_method_type_registration',
						function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
							$payment_method_registry->register( new WC_Metaps_CS_Payments_Blocks_Support() );
						}
					);
				}
				// Pay-Easy.
				if ( isset( $metaps_settings['payeasypaymentcheck'] ) && $metaps_settings['payeasypaymentcheck'] ) {
					require_once 'includes/gateways/metaps/blocks/class-wc-metaps-pe-payments-blocks-support.php';
					add_action(
						'woocommerce_blocks_payment_method_type_registration',
						function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
							$payment_method_registry->register( new WC_Metaps_PE_Payments_Blocks_Support() );
						}
					);
				}
			}
		}

		/**
		 * Load plugin functions.
		 */
		public static function metaps_for_wc_plugin() {
			if ( self::is_woocommerce_active() && class_exists( 'WooCommerce' ) ) {
				self::instance()->init();
			} else {
				add_action( 'admin_notices', array( __CLASS__, 'metaps_for_wc_fallback_notice' ) );
			}
		}

		/**
		 * Display fallback notice if WooCommerce is not active.
		 */
		public static function metaps_for_wc_fallback_notice() {
			?>
			<div class="error">
				<ul>
					<li><?php esc_html_e( 'metaps PAYMENT for WooCommerce is enabled but not effective. It requires WooCommerce in order to work.', 'woocommerce-for-japan' ); ?></li>
				</ul>
			</div>
			<?php
		}

		/**
		 * Check if WooCommerce is active.
		 */
		public static function is_woocommerce_active() {
			if ( ! isset( $active_plugins ) ) {
				$active_plugins = (array) get_option( 'active_plugins', array() );

				if ( is_multisite() ) {
					$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
				}
			}
			return in_array( 'woocommerce/woocommerce.php', $active_plugins, true ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins );
		}

		/**
		 * Register user meta fields.
		 */
		public function register_user_meta_metaps_user_id() {
			register_meta(
				'user',
				'_metaps_user_id',
				array(
					'type'          => 'string',
					'description'   => 'metaps User ID',
					'single'        => true,
					'show_in_rest'  => true, // This will be available via the REST API.
					'auth_callback' => function () {
						return is_user_logged_in();
					},
				)
			);
		}
	}

endif;

add_action( 'plugins_loaded', array( 'WC_Metaps_Payments', 'metaps_for_wc_plugin' ) );
