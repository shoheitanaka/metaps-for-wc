<?php
/**
 * Plugin Name: Metaps for WooCommerce
 * Plugin URI: https://www.wordpress.org/plugins/metaps-for-wc/
 * Description: Metaps for WooCommerce is a WooCommerce payment extention plugin.
 * Version: 0.9.0
 * Author: Shohei Tanaka
 * Author URI: https://wc.artws.info/
 * Text Domain: metaps-for-wc
 * Domain Path: /languages
 * 
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * 
 * WC requires at least: 8.0.0
 * WC tested up to: 8.0.0
 * Requires at least: 6.0
 * Tested up to: 6.5.2
 */

 defined( 'ABSPATH' ) || exit;

 if ( ! defined( 'METAPS_PLUGIN_FILE' ) ) {
	define( 'METAPS_PLUGIN_FILE', __FILE__ );
}

if ( ! class_exists( 'WC_Metaps_Payments' ) ) :

	class WC_Metaps_Payments{

		/**
		 * metaps PAYMENT version.
		 *
		 * @var string
		 */
		public $version = '0.9.0';

		/**
		 * metaps PAYMENT for WooCommerce Framework version.
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
		 * metaps PAYMENT for WooCommerce Constructor.
		 * @access public
		 * @return WC_Metaps_Payments
		 */
		public function __construct() {
			// rated appeal
			add_action( 'wp_ajax_metaps_for_wc_rated', array( __CLASS__, 'metaps_for_wc_rated') );
			add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
			// handle HPOS compatibility
			add_action( 'before_woocommerce_init', [ $this, 'metaps_handle_hpos_compatibility' ] );
	
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
		 * @since 2.0.0
		 * @version 2.0.0
		 */
		public function init() {
			$this->define_constants();
			register_deactivation_hook( METAPS_FOR_WC_PLUGIN_FILE, array( $this, 'on_deactivation' ) );
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
			// metaps PAYMENT for WooCommerce version
			$this->define( 'METAPS_FOR_WC_URL', plugin_dir_url( __FILE__ ) );
			$this->define( 'METAPS_FOR_WC_DIR', plugin_dir_path( __FILE__ ) );
			$this->define( 'METAPS_FOR_WC_ABSPATH', dirname( __FILE__ ) . '/' );
			$this->define( 'METAPS_FOR_WC_INCLUDES_PATH', METAPS_FOR_WC_ABSPATH . 'includes/' );
			$this->define( 'METAPS_FOR_WC_PLUGIN_FILE', __FILE__ );
			$this->define( 'METAPS_FOR_WC_VERSION', $this->version );
			$this->define( 'METAPS_FOR_WC_FRAMEWORK_VERSION', $this->framework_version );
			// Config Setting
			$this->define( 'METAPS_FOR_WC_URL', 'https://www.paydesign.jp/settle/' );
			// Credit Card
			$this->define( 'METAPS_FOR_WC_CC_SALES_URL', METAPS_FOR_WC_URL.'settle3/bp3.dll' );
			$this->define( 'METAPS_FOR_WC_CC_SALES_USER_URL', METAPS_FOR_WC_URL.'settlex/credit2.dll' );
			$this->define( 'METAPS_FOR_WC_CC_SALES_COMP_URL', METAPS_FOR_WC_URL.'Fixation/crDkakutei.dll' );
			$this->define( 'METAPS_FOR_WC_CC_SALES_CANCEL_URL', METAPS_FOR_WC_URL.'Fixation/canauthp.dll' );
			$this->define( 'METAPS_FOR_WC_CC_SALES_REFUND_URL', METAPS_FOR_WC_URL.'Fixation/cantorip.dll' );
			$this->define( 'METAPS_FOR_WC_CC_SALES_AUTH_URL', METAPS_FOR_WC_URL.'inquiry/reskaricr.dll' );
			$this->define( 'METAPS_FOR_WC_CC_SALES_CHECK_URL', METAPS_FOR_WC_URL.'inquiry/result3.dll' );
			// Convenience Store
			$this->define( 'METAPS_FOR_WC_CS_SALES_URL', METAPS_FOR_WC_URL.'settle2/ubp3.dll' );
			$this->define( 'METAPS_FOR_WC_CS_CANCEL_URL', METAPS_FOR_WC_URL.'Fixation/can_cvs.dll' );
/*			$this->define( 'M4WC_ABSPATH', dirname( __FILE__ ) . '/' );
			$this->define( 'M4WC_URL_PATH', plugins_url( '/', __FILE__ ) );
			$this->define( 'M4WC_INCLUDES_PATH', M4WC_ABSPATH . 'includes/' );
			$this->define( 'M4WC_PLUGIN_FILE', __FILE__ );
			$this->define( 'M4WC_VERSION', $this->version );
			$this->define( 'M4WC_FRAMEWORK_VERSION', $this->framework_version );
			// Config Setting
			$this->define( 'M4WC_URL', 'https://www.paydesign.jp/settle/' );
			// Credit Card
			$this->define( 'PAYDESIGN_CC_SALES_URL', M4WC_URL.'settle3/bp3.dll' );
			$this->define( 'PAYDESIGN_CC_SALES_USER_URL', M4WC_URL.'settlex/credit2.dll' );
			$this->define( 'PAYDESIGN_CC_SALES_COMP_URL', M4WC_URL.'Fixation/crDkakutei.dll' );
			$this->define( 'PAYDESIGN_CC_SALES_CANCEL_URL', M4WC_URL.'Fixation/canauthp.dll' );
			$this->define( 'PAYDESIGN_CC_SALES_REFUND_URL', M4WC_URL.'Fixation/cantorip.dll' );
			$this->define( 'PAYDESIGN_CC_SALES_AUTH_URL', M4WC_URL.'inquiry/reskaricr.dll' );
			$this->define( 'PAYDESIGN_CC_SALES_CHECK_URL', M4WC_URL.'inquiry/result3.dll' );
			// Convenience Store
			$this->define( 'PAYDESIGN_CS_SALES_URL', M4WC_URL.'settle2/ubp3.dll' );
			$this->define( 'PAYDESIGN_CS_CANCEL_URL', M4WC_URL.'Fixation/can_cvs.dll' );*/
		}
	
		/**
		 * Load Localisation files.
		 */
		protected function load_plugin_textdomain() {
			load_plugin_textdomain( 'metaps-for-wc', false, basename( dirname( __FILE__ ) ) . '/i18n' );
		}
	
		/**
		 * Include JP4WC classes.
		 */
		private function includes() {
			//load framework
			$version_text = 'v'.str_replace('.', '_', METAPS_FOR_WC_FRAMEWORK_VERSION);
			if ( ! class_exists( '\\ArtisanWorkshop\\PluginFramework\\'.$version_text.'\\JP4WC_Plugin' ) ) {
				require_once METAPS_FOR_WC_INCLUDES_PATH . 'jp4wc-framework/class-jp4wc-framework.php';
			}
			// Admin Setting Screen
			require_once METAPS_FOR_WC_INCLUDES_PATH . 'admin/class-wc-admin-screen-metaps.php';
			new ArtisanWorkshop\Metaps\Admin\WC_Admin_Screen_Metaps();

			// Admin Notice
//			require_once METAPS_FOR_WC_INCLUDES_PATH . 'gateways/metaps/class-wc-metaps-admin-notices.php';
			// metaps PAYMENT Payment Gateway
			$metaps_settings = get_option('woocommerce_metaps_settings');
			if( isset( $metaps_settings['creditcardcheck'] ) && $metaps_settings['creditcardcheck'] ){
				include_once( METAPS_FOR_WC_INCLUDES_PATH . 'gateways/metaps/class-wc-gateway-metaps-cc.php' ); // Credit Card
				include_once( METAPS_FOR_WC_INCLUDES_PATH . 'gateways/metaps/class-wc-gateway-addon-metaps-cc.php' ); // Credit Card Subscription
			}
			if( isset( $metaps_settings['creditcardtokencheck'] ) && $metaps_settings['creditcardtokencheck'] ){
				include_once( METAPS_FOR_WC_INCLUDES_PATH . 'gateways/metaps/class-wc-gateway-metaps-cc-token.php' ); // Credit Card with Token
				include_once( METAPS_FOR_WC_INCLUDES_PATH . 'gateways/metaps/class-wc-gateway-addon-metaps-cc-token.php' ); // Credit Card with Token Subscription
			}
			if( isset( $metaps_settings['conveniencepaymentscheck'] ) && $metaps_settings['conveniencepaymentscheck'] ) include_once( METAPS_FOR_WC_INCLUDES_PATH . 'gateways/metaps/class-wc-gateway-metaps-cs.php' ); // Convenience store
			if( isset( $metaps_settings['payeasypaymentcheck'] ) && $metaps_settings['payeasypaymentcheck'] ) include_once( METAPS_FOR_WC_INCLUDES_PATH . 'gateways/metaps/class-wc-gateway-metaps-pe.php' ); // Pay-Easy
		}
	
		/**
		 * Change the admin footer text on WooCommerce for Japan admin pages.
		 *
		 * @since  1.1
		 * @param  string $footer_text
		 * @return string
		 */
		public function admin_footer_text( $footer_text ) {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return null;
			}
			$current_screen = get_current_screen();
			$metaps_for_wc_pages = 'woocommerce_page_settings-metaps';
			// Check to make sure we're on a WooCommerce admin page
			if ( isset( $current_screen->id ) && $current_screen->id == $metaps_for_wc_pages ) {
				if ( ! get_option( 'metaps_for_wc_footer_text_rated' ) ) {
					$footer_text = sprintf( __( 'If you like <strong>metaps PAYMENT for WooCommerce.</strong> please leave us a %s&#9733;&#9733;&#9733;&#9733;&#9733;%s rating. A huge thanks in advance!', 'woo-paydesign' ), '<a href="https://wordpress.org/support/plugin/woocommerce-for-japan/reviews/#postform" target="_blank" class="wc4jp-rating-link" data-rated="' . esc_attr__( 'Thanks :)', 'woocommerce-for-japan' ) . '">', '</a>' );
					wc_enqueue_js( "
						jQuery( 'a.wc4jp-rating-link' ).click( function() {
							jQuery.post( '" . WC()->ajax_url() . "', { action: 'metaps_for_wc_rated' } );
							jQuery( this ).parent().text( jQuery( this ).data( 'rated' ) );
						});
					" );
				}else{
					$footer_text = __( 'Thank you for selling with WooCommerce for metaps PAYMENT.', 'woo-paydesign' );
				}
			}
			return $footer_text;
		}

		/**
		 * Triggered when clicking the rating footer.
		 */
		public static function metaps_for_wc_rated() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				die(-1);
			}
	
			update_option( 'metaps_for_wc_admin_footer_text_rated', 1 );
			die();
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
		 * Declares HPOS compatibility if the plugin is compatible with HPOS.
		 *
		 * @internal
		 *
		 * @since 2.6.0
		 */
		public function metaps_handle_hpos_compatibility() {
	
			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				$slug = dirname( plugin_basename( __FILE__ ) );
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables',trailingslashit( $slug ) . $slug . '.php' , true );
			}
		}
	}
	
endif;

/**
 * Load plugin functions.
 */
add_action( 'plugins_loaded', 'metaps_for_wc_plugin');

function metaps_for_wc_plugin() {
	if ( is_woocommerce_active() && class_exists( 'WooCommerce' ) ) {
		WC_Metaps_Payments::instance()->init();
	} else {
		add_action( 'admin_notices', 'metaps_for_wc_fallback_notice' );
	}
}

function metaps_for_wc_fallback_notice() {
	?>
    <div class="error">
        <ul>
            <li><?php echo __( 'metaps PAYMENT for WooCommerce is enabled but not effective. It requires WooCommerce in order to work.', 'woocommerce-for-japan' );?></li>
        </ul>
    </div>
	<?php
}

/**
 * WC Detection
 */
if ( ! function_exists( 'is_woocommerce_active' ) ) {
	function is_woocommerce_active() {
		if ( ! isset($active_plugins) ) {
			$active_plugins = (array) get_option( 'active_plugins', array() );

			if ( is_multisite() )
				$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}
		return in_array( 'woocommerce/woocommerce.php', $active_plugins ) || array_key_exists( 'woocommerce/woocommerce.php',$active_plugins );
	}
}
