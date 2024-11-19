<?php
/**
 * WC_Gateway_Metaps_PE class file.
 *
 * @package WooCommerce/Classes/Payment
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use ArtisanWorkshop\PluginFramework\v2_0_12 as Framework;

/**
 * Metaps PAYMENT Gateway
 *
 * Provides a metaps PAYMENT Pay Easy Payment Gateway.
 *
 * @class       WC_Gateway_Metaps_PE
 * @extends     WC_Payment_Gateway
 * @version     1.0.0
 * @package     WooCommerce/Classes/Payment
 * @author      Artisan Workshop
 */
class WC_Gateway_Metaps_PE extends WC_Payment_Gateway {

	/**
	 * IP code
	 *
	 * @var string
	 */
	public $ip_code;

	/**
	 * Pass code
	 *
	 * @var string
	 */
	public $pass_code;

	/**
	 * Payment deadline
	 *
	 * @var string
	 */
	public $payment_deadline;

	/**
	 * Payeasy Email Description
	 *
	 * @var string
	 */
	public $payeasy_email_desc;

	/**
	 * Processing Email Subject
	 *
	 * @var string
	 */
	public $processing_email_subject;

	/**
	 * Processing Email Heading
	 *
	 * @var string
	 */
	public $processing_email_heading;

	/**
	 * Processing Email Body
	 *
	 * @var string
	 */
	public $processing_email_body;

	/**
	 * Payment limit description
	 *
	 * @var string
	 */
	public $payment_limit_description;

	/**
	 * Set metaps request class
	 *
	 * @var stdClass
	 */
	public $metaps_request;

	/**
	 * Debug mode
	 *
	 * @var bool
	 */
	public $debug;

	/**
	 * Constructor for the gateway.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->id           = 'metaps_pe';
		$this->has_fields   = false;
		$this->method_title = __( 'metaps PAYMENT Pay Easy', 'metaps-for-wc' );

		// Create plugin fields and settings.
		$this->init_form_fields();
		$this->init_settings();
		$this->method_description = __( 'Allows payments by metaps PAYMENT Pay Easy in Japan.', 'metaps-for-wc' );
		if ( is_null( $this->title ) ) {
			$this->title = __( 'Please set this payment at Control Panel! ', 'metaps-for-wc' ) . $this->method_title;
		}

		include_once 'includes/class-wc-gateway-metaps-request.php';
		$this->metaps_request = new WC_Gateway_Metaps_Request();

		// Get setting values.
		foreach ( $this->settings as $key => $val ) {
			$this->$key = $val;
		}

		// Actions and filters.
		add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		// Customer Emails - Processing Order.
		add_action( 'woocommerce_email_before_order_table', array( &$this, 'email_instructions' ), 10, 3 );
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'metaps_pe_detail' ), 10, 1 );
		add_filter( 'woocommerce_email_subject_customer_processing_order', array( $this, 'change_email_subject_metaps_pe' ), 1, 2 );
		add_filter( 'woocommerce_email_heading_customer_processing_order', array( $this, 'change_email_heading_metaps_pe' ), 1, 2 );
		add_action( 'woocommerce_email_before_order_table', array( $this, 'change_email_instructions_pe' ), 1, 2 );
	}

	/**
	 * Initialize Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'                   => array(
				'title'       => __( 'Enable/Disable', 'metaps-for-wc' ),
				'label'       => __( 'Enable metaps PAYMENT Pay Easy Payment', 'metaps-for-wc' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'title'                     => array(
				'title'       => __( 'Title', 'metaps-for-wc' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'metaps-for-wc' ),
				'default'     => __( 'Pay Easy Payment (metaps)', 'metaps-for-wc' ),
			),
			'description'               => array(
				'title'       => __( 'Description', 'metaps-for-wc' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'metaps-for-wc' ),
				'default'     => __( 'Pay with your Pay Easy via metaps PAYMENT.', 'metaps-for-wc' ),
			),
			'order_button_text'         => array(
				'title'       => __( 'Order Button Text', 'metaps-for-wc' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'metaps-for-wc' ),
				'default'     => __( 'Proceed to metaps PAYMENT Pay Easy', 'metaps-for-wc' ),
			),
			'ip_code'                   => array(
				'title'       => __( 'IP Code', 'metaps-for-wc' ),
				'type'        => 'text',
				'description' => __( 'Enter IP Code here.', 'metaps-for-wc' ),
			),
			'pass_code'                 => array(
				'title'       => __( 'IP Password', 'metaps-for-wc' ),
				'type'        => 'text',
				'description' => __( 'Enter IP Password here', 'metaps-for-wc' ),
			),
			'payment_deadline'          => array(
				'title'       => __( 'Due date for payment', 'metaps-for-wc' ),
				'type'        => 'select',
				'description' => __( 'Select the days term of due date for payment', 'metaps-for-wc' ),
				'options'     => array(
					'5'  => '5' . __( 'days', 'metaps-for-wc' ),
					'7'  => '7' . __( 'days', 'metaps-for-wc' ),
					'10' => '10' . __( 'days', 'metaps-for-wc' ),
					'15' => '15' . __( 'days', 'metaps-for-wc' ),
					'30' => '30' . __( 'days', 'metaps-for-wc' ),
					'60' => '60' . __( 'days', 'metaps-for-wc' ),
				),
			),
			'payeasy_email_desc'        => array(
				'title'       => __( 'Explain the Pay-Easy method in Email', 'metaps-for-wc' ),
				'type'        => 'textarea',
				'description' => __( 'This explains the Pay-Easy method of payment in Email, how to use.', 'metaps-for-wc' ),
			),
			'processing_email_subject'  => array(
				'title'       => __( 'Email Subject when complete payment check', 'metaps-for-wc' ),
				'type'        => 'text',
				'description' => __( 'send e-mail subject when check metaps PAYMENT after customer paid.', 'metaps-for-wc' ),
				'default'     => __( 'Payment Complete by Pay-easy', 'metaps-for-wc' ),
			),
			'processing_email_heading'  => array(
				'title'       => __( 'Email Heading when complete payment check', 'metaps-for-wc' ),
				'type'        => 'text',
				'description' => __( 'send e-mail heading when check metaps PAYMENT after customer paid.', 'metaps-for-wc' ),
				'default'     => __( 'Thank you for your payment', 'metaps-for-wc' ),
			),
			'processing_email_body'     => array(
				'title'       => __( 'Email body when complete payment check', 'metaps-for-wc' ),
				'type'        => 'textarea',
				'description' => __( 'send e-mail Body when check metaps PAYMENT after customer paid.', 'metaps-for-wc' ),
				'default'     => __( 'I checked your payment. Thank you. I will ship your order as soon as possible.', 'metaps-for-wc' ),
			),
			'payment_limit_description' => array(
				'title'       => __( 'Explain Payment limit date', 'metaps-for-wc' ),
				'type'        => 'text',
				'description' => __( 'Explain Payment limite date in New order E-mail.', 'metaps-for-wc' ),
				'default'     => __( 'The payment deadline is 10 days from completed the order.', 'metaps-for-wc' ),
			),
			'debug'                     => array(
				'title'       => __( 'Debug Mode', 'metaps-for-wc' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable Debug Mode', 'metaps-for-wc' ),
				'default'     => 'no',
				'description' => __( 'Save debug data using WooCommerce logging.', 'metaps-for-wc' ),
			),
		);
	}

	/**
	 * UI - Admin Panel Options
	 */
	public function admin_options() {
		?>
		<h3><?php esc_html_e( 'metaps PAYMENT Pay Easy Payment', 'metaps-for-wc' ); ?></h3>
		<table class="form-table">
		<?php $this->generate_settings_html(); ?>
		</table>
		<?php
	}
	/**
	 * UI - Payment page fields for metaps PAYMENT Payment.
	 */
	public function payment_fields() {
		// Description of payment method from settings.
		if ( $this->description ) {
			?>
		<p><?php echo esc_html( $this->description ); ?></p>
			<?php
		}
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id The order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$metaps_settings = get_option( 'woocommerce_metaps_settings' );
		$prefix_order    = $metaps_settings['prefixorder'];
		$order           = wc_get_order( $order_id );
		$user            = wp_get_current_user();
		if ( $order->user_id ) {
			$customer_id = $prefix_order . $user->ID;
		} else {
			$customer_id = $prefix_order . $order_id . '-user';
		}
		// Setting $send_data.
		$setting_data = array();

		$setting_data['ip']    = $this->ip_code;
		$setting_data['sid']   = $prefix_order . $order_id;
		$setting_data['store'] = '84';
		// Set Payment limit date.
		$kigen                 = mktime( 0, 0, 0, date_i18n( 'm' ), date_i18n( 'd' ) + $this->payment_deadline, date_i18n( 'Y' ) );
		$setting_data['kigen'] = date_i18n( 'Ymd', $kigen );
		$connect_url           = METAPS_CS_SALES_URL;
		$response              = $this->metaps_request->metaps_post_request( $order, $connect_url, $setting_data );
		if ( isset( $response[0] ) && substr( $response[0], 0, 2 ) === 'OK' ) {
			if ( isset( $response[3] ) ) {
				$order->set_transaction_id( $response[3] );
			}
			if ( isset( $response[6] ) ) {
				$order->add_meta_data( '_metaps_payment_url', wc_clean( $response[6] ), true );
				$order->save_meta_data();
				$order->add_order_note(
					__( 'Housing agency code : ', 'metaps-for-wc' ) . substr( $response[3], 0, 5 ) .
					', ' . __( 'Customer Number : ', 'metaps-for-wc' ) . substr( $response[3], 6, 20 ) .
					', ' . __( 'Authorization number : ', 'metaps-for-wc' ) . substr( $response[3], 27, 6 ) .
					', ' . __( 'Confirmation URL : ', 'metaps-for-wc' ) . $response[6]
				);
			}

			$order->update_status( 'on-hold', __( 'This order is complete for pay.', 'metaps-for-wc' ) );
			// Reduce stock levels.
			$order->reduce_order_stock();

			// Remove cart.
			WC()->cart->empty_cart();

			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		} else {
			$order->update_status( 'cancelled', __( 'This order is cancelled, because of Payment error.', 'metaps-for-wc' ) . ' ' . mb_convert_encoding( $response[2], 'UTF-8', 'auto' ) );
			if ( is_checkout() ) {
				wc_add_notice( __( 'Payment error:', 'metaps-for-wc' ) . mb_convert_encoding( $response[2], 'UTF-8', 'auto' ), 'error' );
			}
			return;
		}
	}

	/**
	 * Check payment details for valid format
	 */
	public function validate_fields() {
		$zenkaku_array = array(
			'billing_last_name'           => __( 'Last name', 'woocommerce' ),
			'billing_first_name'          => __( 'First name', 'woocommerce' ),
			'billing_yomigana_last_name'  => __( 'Last Name (Yomigana)', 'woocommerce-for-japan' ),
			'billing_yomigana_first_name' => __( 'First Name (Yomigana)', 'woocommerce-for-japan' ),
			'billing_city'                => __( 'Town / City', 'woocommerce-for-japan' ),
		);

		$flag = true;

		foreach ( $zenkaku_array as $key => $value ) {
			if ( $this->get_post( $key ) ) {
				$key = $this->get_post( $key );
				if ( $this->is_zenkaku( $key, false ) === false ) {
					// translators: %s: Field name that must be in Zenkaku.
					wc_add_notice( sprintf( __( 'ERROR : %s must be Zenkaku when you use Payeasy Payment.', 'metaps-for-wc' ), $value ), 'error' );
					$flag = false;
				}
			}
		}
		return $flag;
	}

	/**
	 * Check if the given text is in Zenkaku (full-width) characters.
	 *
	 * @param string $text The text to check.
	 * @param bool   $katakana Whether to check for Zenkaku Katakana characters.
	 * @return bool True if the text is in Zenkaku, false otherwise.
	 */
	public function is_zenkaku( $text, $katakana = false ) {
		$len = strlen( $text );
		// UTF-8の場合は全角を3文字カウントするので「* 3」にする.
		$mblen = mb_strlen( $text, 'UTF-8' ) * 3;
		if ( $len !== $mblen ) {
			return false;
		} elseif ( $katakana ) {
			if ( preg_match( '/^[ァ-ヾ]+$/u', $text ) ) {
				return true;
			} else {
				return false;
			}
		} else {
			return true;
		}
	}

	/**
	 * Add content to the WC emails For Convenient Infomation.
	 *
	 * @access public
	 * @param WC_Order $order Order object.
	 * @param bool     $sent_to_admin If the email is being sent to the admin.
	 * @param bool     $plain_text If the email is being sent in plain text.
	 * @return void
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		$payment_method = $order->get_payment_method();
		$status         = $order->get_status();
		$order_id       = $order->get_id();
		if ( ! $sent_to_admin && 'metaps_pe' === $payment_method && 'on-hold' === $status ) {
			$this->metaps_pe_details( $order_id );
		}
	}

	/**
	 * Get Pay Easy details and place into a list format
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	private function metaps_pe_details( $order_id = '' ) {
		$order       = wc_get_order( $order_id );
		$payment_url = $order->get_meta( '_metaps_payment_url', true );

		echo esc_html__( 'Payment Information URL : ', 'metaps-for-wc' ) . esc_url( $payment_url ) . '<br />' . PHP_EOL;
		if ( isset( $this->payeasy_email_desc ) ) {
			echo esc_html( $this->payeasy_email_desc );
		}
	}

	/**
	 * Process a refund if supported
	 *
	 * @param  int    $order_id Order ID.
	 * @param  float  $amount Amount to refund.
	 * @param  string $reason Reason for refund.
	 * @return  boolean True or false based on success, or a WP_Error object.
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );
		return false;
	}

	/**
	 * Get Payeasy Payment details and place into a list format
	 *
	 * @param WC_Order $order Order object.
	 */
	public function metaps_pe_detail( $order ) {
		if ( $order->get_payment_method() === $this->id ) {
			$order_id = $order->get_id();

			$payment_setting           = get_option( 'woocommerce_metaps_pe_settings' );
			$payment_limit_description = $payment_setting['payment_limit_description'];
			$payment_url               = $order->get_meta( '_metaps_payment_url', true );
			$transaction_id            = $order->get_transaction_id();

			if ( $order->get_payment_method() === 'metaps_pe' ) {
				echo '<header class="title"><h3>' . esc_html__( 'Payment Detail', 'metaps-for-wc' ) . '</h3></header>';
				echo '<table class="shop_table order_details">';
				echo '<tr><th>' . esc_html__( 'Payment Detail', 'metaps-for-wc' ) . '</th>
		<td>' . esc_html__( 'Housing agency code : ', 'metaps-for-wc' ) . esc_html( substr( $transaction_id, 0, 5 ) ) . '<br />'
				. esc_html__( 'Customer Number : ', 'metaps-for-wc' ) . esc_html( substr( $transaction_id, 6, 20 ) ) . '<br />'
				. esc_html__( 'Authorization number : ', 'metaps-for-wc' ) . esc_html( substr( $transaction_id, 27, 6 ) ) . '<br /></td></tr>'
				. PHP_EOL;
				echo '<tr><th>' . esc_html__( 'Payment URL', 'metaps-for-wc' ) . '</th>
		<td><a href="' . esc_url( $payment_url ) . '" target="_blank">' . esc_html__( 'Pay from here.', 'metaps-for-wc' ) . '</a></td></tr>' . PHP_EOL;
				if ( isset( $payment_limit_description ) ) {
					echo '<tr><th>' . esc_html__( 'Payment limit term', 'metaps-for-wc' ) . '</th><td>' . esc_html( $payment_limit_description ) . '</td></tr>' . PHP_EOL;
				}
				echo '</table>';
			}
		}
	}

	// E-mail Subject and heading and body Change when processing in this Payment.
	/**
	 * Change email subject for metaps PE.
	 *
	 * @param string   $subject The email subject.
	 * @param WC_Order $order   The order object.
	 * @return string The modified email subject.
	 */
	public function change_email_subject_metaps_pe( $subject, $order ) {
		$payment_method = $order->get_payment_method();
		$status         = $order->get_status();
		if ( 'metaps_pe' === $payment_method && 'processing' === $status ) {
			$payment_setting = get_option( 'woocommerce_metaps_pe_settings' );
			$subject         = $payment_setting['processing_email_subject'];
		}
		return $subject;
	}

	/**
	 * Change email heading for metaps PE.
	 *
	 * @param string   $heading The email heading.
	 * @param WC_Order $order   The order object.
	 * @return string The modified email heading.
	 */
	public function change_email_heading_metaps_pe( $heading, $order ) {
		$payment_method = $order->get_payment_method();
		$status         = $order->get_status();
		if ( 'metaps_pe' === $payment_method && 'processing' === $status ) {
			$payment_setting = get_option( 'woocommerce_metaps_pe_settings' );
			$heading         = $payment_setting['processing_email_heading'];
		}
		return $heading;
	}

	/**
	 * Change email instructions for metaps PE.
	 *
	 * @param WC_Order $order Order object.
	 * @param bool     $sent_to_admin If the email is being sent to the admin.
	 */
	public function change_email_instructions_pe( $order, $sent_to_admin ) {
		$payment_method = $order->get_payment_method();
		$status         = $order->get_status();
		if ( 'metaps_pe' === $payment_method && 'processing' === $status ) {
			$payment_setting = get_option( 'woocommerce_metaps_pe_settings' );
			echo esc_html( $payment_setting['processing_email_body'] );
		}
	}

	/**
	 * Get post data if set
	 *
	 * @param string $name Name of the post data.
	 * @return string|null
	 */
	private function get_post( $name ) {
		if ( isset( $_POST[ $name ] ) && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'woocommerce-process_checkout' ) ) {
			return sanitize_text_field( wp_unslash( $_POST[ $name ] ) );
		}
		return null;
	}
}
