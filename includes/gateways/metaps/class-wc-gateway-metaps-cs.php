<?php
/**
 * WC_Gateway_Metaps_CS class file.
 *
 * Handles Metaps Convini Store payments in WooCommerce.
 *
 * @package Metaps_For_WC
 */

use ArtisanWorkshop\PluginFramework\v2_0_12 as Framework;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC_Gateway_Metaps_CS class.
 *
 * Handles Metaps Convini Store payments in WooCommerce.
 */
class WC_Gateway_Metaps_CS extends WC_Payment_Gateway {


	/**
	 * Framework.
	 *
	 * @var stdClass
	 */
	public $jp4wc_framework;

	/**
	 * Debug mode
	 *
	 * @var string
	 */
	public $debug;

	/**
	 * Test mode
	 *
	 * @var string
	 */
	public $test_mode;

	/**
	 * Set metaps request class
	 *
	 * @var stdClass
	 */
	public $metaps_request;

	/**
	 * Convenience stores
	 *
	 * @var array
	 */
	public $cs_stores;

	/**
	 * IP Code
	 *
	 * @var string
	 */
	public $ip_code;

	/**
	 * Pass Code
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
	 * Processing email subject
	 *
	 * @var string
	 */
	public $processing_email_subject;

	/**
	 * Processing email heading
	 *
	 * @var string
	 */
	public $processing_email_heading;

	/**
	 * Processing email body
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
	 * Setting for Convenience Store
	 *
	 * @var string
	 */
	public $setting_cs_sv;

	/**
	 * Setting for Convenience Store
	 *
	 * @var string
	 */
	public $setting_cs_lp;

	/**
	 * Setting for Convenience Store
	 *
	 * @var string
	 */
	public $setting_cs_fm;

	/**
	 * Setting for Convenience Store
	 *
	 * @var string
	 */
	public $setting_cs_ol;

	/**
	 * Setting for Convenience Store
	 *
	 * @var string
	 */
	public $setting_cs_sm;

	/**
	 * Constructor for the gateway.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		$this->id           = 'metaps_cs';
		$this->has_fields   = false;
		$this->method_title = __( 'metaps PAYMENT Convenience Store', 'metaps-for-woocommerce' );

		// Create plugin fields and settings.
		$this->init_form_fields();
		$this->init_settings();
		$this->method_description = __( 'Allows payments by metaps PAYMENT Convenience Store in Japan.', 'metaps-for-woocommerce' );
		$this->supports           = array(
			'products',
			'refunds',
		);

		$this->jp4wc_framework = new Framework\JP4WC_Framework();

		include_once 'includes/class-wc-gateway-metaps-request.php';
		$this->metaps_request = new WC_Gateway_Metaps_Request();

		// When no save setting error at chackout page.
		if ( is_null( $this->title ) ) {
			$this->title = __( 'Please set this payment at Control Panel! ', 'metaps-for-woocommerce' ) . $this->method_title;
		}

		// Get setting values.
		foreach ( $this->settings as $key => $val ) {
			$this->$key = $val;
		}

		// Set Convenience Store.
		$this->cs_stores = array();
		$cs_stores       = array();
		if ( isset( $this->setting_cs_sv ) && 'yes' === $this->setting_cs_sv ) {
			$cs_stores['2'] = __( 'Seven-Eleven', 'metaps-for-woocommerce' );
		}
		if ( isset( $this->setting_cs_fm ) && 'yes' === $this->setting_cs_fm ) {
			$cs_stores['3'] = __( 'family mart', 'metaps-for-woocommerce' );
		}
		if ( isset( $this->setting_cs_ol ) && 'yes' === $this->setting_cs_ol ) {
			$cs_stores['73'] = __( 'Daily Yamazaki', 'metaps-for-woocommerce' );
		}
		if ( isset( $this->setting_cs_lp ) && 'yes' === $this->setting_cs_lp ) {
			$cs_stores['5'] = __( 'Lawson, MINISTOP', 'metaps-for-woocommerce' );
		}
		if ( 'yes' === $this->setting_cs_sm && isset( $this->setting_cs_sm ) ) {
			$cs_stores['6'] = __( 'Seicomart', 'metaps-for-woocommerce' );
		}
		$this->cs_stores = $cs_stores;

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		// Customer Emails.
		add_action( 'woocommerce_email_before_order_table', array( $this, 'pd_email_instructions' ), 10, 3 );
		// Add content to the order detail For Convenient Infomation.
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'metaps_cs_detail' ), 10, 1 );
		// Add content to the WC emails For Convenient Infomation.
		add_filter( 'woocommerce_email_subject_customer_processing_order', array( $this, 'change_email_subject_metaps_cs' ), 1, 2 );
		add_filter( 'woocommerce_email_heading_customer_processing_order', array( $this, 'change_email_heading_metaps_cs' ), 1, 2 );
		add_action( 'woocommerce_email_before_order_table', array( $this, 'change_email_instructions_cs' ), 1, 2 );
	}
	/**
	 * Initialize Gateway Settings Form Fields.
	 */
	public function init_form_fields() {

		$this->form_fields = array(
			'enabled'                   => array(
				'title'       => __( 'Enable/Disable', 'metaps-for-woocommerce' ),
				'label'       => __( 'Enable metaps PAYMENT Convenience Store Payment', 'metaps-for-woocommerce' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'title'                     => array(
				'title'       => __( 'Title', 'metaps-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'metaps-for-woocommerce' ),
				'default'     => __( 'Convenience Store Payment (metaps)', 'metaps-for-woocommerce' ),
			),
			'description'               => array(
				'title'       => __( 'Description', 'metaps-for-woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'metaps-for-woocommerce' ),
				'default'     => __( 'Pay at Convenience Store via metaps PAYMENT.', 'metaps-for-woocommerce' ),
			),
			'order_button_text'         => array(
				'title'       => __( 'Order Button Text', 'metaps-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the description which the user sees during checkout.', 'metaps-for-woocommerce' ),
				'default'     => __( 'Proceed to metaps PAYMENT Convenience Store Payment', 'metaps-for-woocommerce' ),
			),
			'ip_code'                   => array(
				'title'       => __( 'IP Code', 'metaps-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter IP Code here.', 'metaps-for-woocommerce' ),
			),
			'pass_code'                 => array(
				'title'       => __( 'IP Password', 'metaps-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter IP Password here', 'metaps-for-woocommerce' ),
			),
			'setting_cs_sv'             => array(
				'id'      => 'wc-metaps-cs-sv',
				'type'    => 'checkbox',
				'label'   => __( 'Seven-Eleven', 'metaps-for-woocommerce' ),
				'default' => 'yes',
			),
			'setting_cs_lp'             => array(
				'title'   => __( 'Convenience Payments', 'metaps-for-woocommerce' ),
				'id'      => 'wc-metaps-cs-lp',
				'type'    => 'checkbox',
				'label'   => __( 'Lawson, MINISTOP', 'metaps-for-woocommerce' ),
				'default' => 'yes',
			),
			'setting_cs_fm'             => array(
				'id'      => 'wc-metaps-cs-fm',
				'type'    => 'checkbox',
				'label'   => __( 'family mart', 'metaps-for-woocommerce' ),
				'default' => 'yes',
			),
			'setting_cs_sm'             => array(
				'id'      => 'wc-metaps-cs-sm',
				'type'    => 'checkbox',
				'label'   => __( 'Seicomart', 'metaps-for-woocommerce' ),
				'default' => 'yes',
			),
			'setting_cs_ol'             => array(
				'id'      => 'wc-metaps-cs-ol',
				'type'    => 'checkbox',
				'label'   => __( 'Daily Yamazaki', 'metaps-for-woocommerce' ),
				'default' => 'yes',
			),
			'payment_deadline'          => array(
				'title'       => __( 'Due date for payment', 'metaps-for-woocommerce' ),
				'type'        => 'select',
				'description' => __( 'Select the days term of due date for payment', 'metaps-for-woocommerce' ),
				'options'     => array(
					'5'  => '5' . __( 'days', 'metaps-for-woocommerce' ),
					'7'  => '7' . __( 'days', 'metaps-for-woocommerce' ),
					'10' => '10' . __( 'days', 'metaps-for-woocommerce' ),
					'15' => '15' . __( 'days', 'metaps-for-woocommerce' ),
					'30' => '30' . __( 'days', 'metaps-for-woocommerce' ),
					'60' => '60' . __( 'days', 'metaps-for-woocommerce' ),
				),
			),
			'processing_email_subject'  => array(
				'title'       => __( 'Email Subject when complete payment check', 'metaps-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'send e-mail subject when check metaps PAYMENT after customer paid.', 'metaps-for-woocommerce' ),
				'default'     => __( 'Payment Complete by CS', 'metaps-for-woocommerce' ),
			),
			'processing_email_heading'  => array(
				'title'       => __( 'Email Heading when complete payment check', 'metaps-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'send e-mail heading when check metaps PAYMENT after customer paid.', 'metaps-for-woocommerce' ),
				'default'     => __( 'Thank you for your payment', 'metaps-for-woocommerce' ),
			),
			'processing_email_body'     => array(
				'title'       => __( 'Email body when complete payment check', 'metaps-for-woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'send e-mail Body when check metaps PAYMENT after customer paid.', 'metaps-for-woocommerce' ),
				'default'     => __( 'I checked your payment. Thank you. I will ship your order as soon as possible.', 'metaps-for-woocommerce' ),
			),
			'payment_limit_description' => array(
				'title'       => __( 'Explain Payment limit date', 'metaps-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Explain Payment limite date in New order E-mail.', 'metaps-for-woocommerce' ),
				'default'     => __( 'The payment deadline is 10 days from completed the order.', 'metaps-for-woocommerce' ),
			),
			'debug'                     => array(
				'title'       => __( 'Debug Mode', 'metaps-for-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable Debug Mode', 'metaps-for-woocommerce' ),
				'default'     => 'no',
				'description' => __( 'Save debug data using WooCommerce logging.', 'metaps-for-woocommerce' ),
			),
		);
	}

	/**
	 * Display a select dropdown for convenience stores.
	 */
	public function cs_select() {
		?><select name="convenience">
		<?php foreach ( $this->cs_stores as $num => $value ) { ?>
			<option value="<?php echo esc_html( $num ); ?>"><?php echo esc_html( $value ); ?></option>
		<?php } ?>
		</select>
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
		<?php } ?>
		<fieldset  style="padding-left: 40px;">
		<?php $this->cs_select(); ?>
		</fieldset>
		<?php
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id The ID of the order to be processed.
	 * @return array|bool The result of the payment processing.
	 * @throws Exception If the payment fails.
	 */
	public function process_payment( $order_id ) {
		$metaps_settings = get_option( 'woocommerce_metaps_settings' );
		$prefix_order    = $metaps_settings['prefixorder'];

		$order = wc_get_order( $order_id );
		// Setting $send_data.
		$setting_data = array();

		$setting_data['ip']    = $this->ip_code;
		$setting_data['sid']   = $prefix_order . $order_id;
		$setting_data['store'] = $this->get_post( 'convenience' );
		// Set Payment limit date.
		$kigen                 = mktime( 0, 0, 0, gmdate( 'm' ), gmdate( 'd' ) + $this->payment_deadline, gmdate( 'Y' ) );
		$setting_data['kigen'] = gmdate( 'Ymd', $kigen );

		$connect_url = METAPS_CS_SALES_URL;
		$response    = $this->metaps_request->metaps_post_request( $order, $connect_url, $setting_data, $this->debug );
		if ( isset( $response[0] ) && substr( $response[0], 0, 2 ) === 'OK' ) {
			if ( isset( $setting_data['store'] ) ) {
				$order->add_meta_data( '_metaps_cvs_id', wc_clean( $setting_data['store'] ), true );
				$order->save_meta_data();
			}
			if ( isset( $response[3] ) ) {
				$order->set_transaction_id( $response[3] );
			}
			if ( isset( $response[6] ) ) {
				$order->add_meta_data( '_metaps_payment_url', wc_clean( $response[6] ), true );
				$order->save_meta_data();
			}
			if ( 1 === $setting_data['store'] ) {
				$cvs_trans_title = __( 'Receipt number : ', 'metaps-for-woocommerce' );
			} elseif ( 2 === $setting_data['store'] ) {
				$cvs_trans_title = __( 'Payment slip number : ', 'metaps-for-woocommerce' );
			} elseif ( 3 === $setting_data['store'] ) {
				$cvs_trans_title = __( 'Company code - Order Number : ', 'metaps-for-woocommerce' );
			} elseif ( 73 === $setting_data['store'] ) {
				$cvs_trans_title = __( 'Online payment number : ', 'metaps-for-woocommerce' );
			}
			$order->add_order_note( $cvs_trans_title . $response[3] );
			if ( isset( $response[6] ) ) {
				$order->add_order_note( __( 'Confirmation URL : ', 'metaps-for-woocommerce' ) . $response[6] );
			}

			$order->update_status( 'on-hold', __( 'This order is complete for pay.', 'metaps-for-woocommerce' ) );
			// Reduce stock levels.
			wc_reduce_stock_levels( $order_id );

			// Remove cart.
			WC()->cart->empty_cart();

			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		} else {
			$error_message = __( 'This order is cancelled, because of Payment error.', 'metaps-for-woocommerce' );
			if ( isset( $response[2] ) ) {
				$error_message = $error_message . ' ' . mb_convert_encoding( $response[2], 'UTF-8', 'sjis' );
			}
			$order->update_status( 'cancelled', $error_message );

			$front_error_message  = __( 'Convenience store payment failed.', 'metaps-for-woocommerce' );
			$front_error_message .= __( 'Please try again.', 'metaps-for-woocommerce' );
			throw new Exception( esc_html( $front_error_message ) );
		}
	}

	/**
	 * Refund a charge
	 *
	 * @param  int    $order_id  The ID of the order to be refunded.
	 * @param  float  $amount  The amount to be refunded.
	 * @param  string $reason  The reason for the refund.
	 * @return bool
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return false;
		}
		$metaps_settings = get_option( 'woocommerce_metaps_settings' );
		$prefix_order    = $metaps_settings['prefixorder'];

		$cansel_connect_url = METAPS_CS_CANCEL_URL;

		$status        = $order->get_status();
		$data['IP']    = $this->ip_code;
		$data['SID']   = $prefix_order . $order_id;
		$data['STORE'] = $order->get_meta( '_metaps_cvs_id', true );
		$response      = $this->metaps_request->metaps_request( $data, $cansel_connect_url, $order, $this->debug );
		if ( substr( $response, 0, 10 ) === 'C-CHECK:OK' && $amount === $order->get_total() ) {
			return true;
		}
		return false;
	}

	/**
	 * Display the receipt page.
	 *
	 * @param WC_Order $order The order object.
	 */
	public function receipt_page( $order ) {
		echo '<p>' . esc_html__( 'Thank you for your order.', 'metaps-for-woocommerce' ) . '</p>';
	}

	/**
	 * Add content to the WC emails For Convenient Infomation.
	 *
	 * @access public
	 * @param  WC_Order $order The order object.
	 * @param  bool     $sent_to_admin Whether the email is being sent to an admin.
	 * @param  bool     $plain_text Whether the email is in plain text format.
	 * @return void
	 */
	public function pd_email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		sleep( 1 );
		$payment_method = $order->get_payment_method();
		$status         = $order->get_status();
		if ( ! $sent_to_admin && 'metaps_cs' === $payment_method && 'on-hold' === $status ) {
			$this->metaps_cs_details( $order->get_id() );
		}
	}

	/**
	 * Get Convini Payment details and place into a list format
	 *
	 * @param int $order_id The order ID.
	 */
	private function metaps_cs_details( $order_id = '' ) {
		$cvs            = $this->cs_stores;
		$order          = wc_get_order( $order_id );
		$cvs_id         = $order->get_meta( '_metaps_cvs_id', true );
		$payment_url    = $order->get_meta( '_metaps_payment_url', true );
		$transaction_id = $order->get_transaction_id();

		if ( 1 === $cvs_id ) {
			$cvs_trans_title = __( 'Receipt number : ', 'metaps-for-woocommerce' );
		} elseif ( 2 === $cvs_id ) {
			$cvs_trans_title = __( 'Payment slip number : ', 'metaps-for-woocommerce' );
		} elseif ( 3 === $cvs_id ) {
			$cvs_trans_title = __( 'Company code - Order Number : ', 'metaps-for-woocommerce' );
		} elseif ( 73 === $cvs_id ) {
			$cvs_trans_title = __( 'Online payment number : ', 'metaps-for-woocommerce' );
		}

		echo esc_html__( 'CVS Name : ', 'metaps-for-woocommerce' ) . esc_html( $cvs[ $cvs_id ] ) . '<br />' . PHP_EOL;
		echo esc_html( $cvs_trans_title . $transaction_id ) . '<br />' . PHP_EOL;
		echo esc_html__( 'How to Pay via CVS expalin URL : ', 'metaps-for-woocommerce' ) . esc_html( $payment_url ) . '<br />' . PHP_EOL;
		if ( isset( $this->payment_limit_description ) ) {
			echo esc_html__( 'Payment limit term : ', 'metaps-for-woocommerce' ) . esc_html( $this->payment_limit_description );
		}
	}

	/**
	 * Get Convini Payment details and place into a list format
	 *
	 * @param WP_Order $order The order object.
	 */
	public function metaps_cs_detail( $order ) {
		if ( $order->get_payment_method() === $this->id ) {
			$cs_stores['2']  = __( 'Seven-Eleven', 'metaps-for-woocommerce' );
			$cs_stores['3']  = __( 'family mart', 'metaps-for-woocommerce' );
			$cs_stores['5']  = __( 'Lawson, MINISTOP', 'metaps-for-woocommerce' );
			$cs_stores['6']  = __( 'Seicomart', 'metaps-for-woocommerce' );
			$cs_stores['73'] = __( 'Daily Yamazaki', 'metaps-for-woocommerce' );

			$cvs_trans_title['2']  = __( 'Payment slip number', 'metaps-for-woocommerce' );
			$cvs_trans_title['3']  = __( 'Company code - Order Number', 'metaps-for-woocommerce' );
			$cvs_trans_title['5']  = __( 'Receipt number', 'metaps-for-woocommerce' );
			$cvs_trans_title['6']  = __( 'Receipt number', 'metaps-for-woocommerce' );
			$cvs_trans_title['73'] = __( 'Online payment number', 'metaps-for-woocommerce' );

			$payment_setting           = get_option( 'woocommerce_metaps_cs_settings' );
			$payment_limit_description = $payment_setting['payment_limit_description'];
			$order_id                  = $order->get_id();
			$order                     = wc_get_order( $order_id );
			$cvs_id                    = $order->get_meta( '_metaps_cvs_id', true );
			$payment_url               = $order->get_meta( '_metaps_payment_url', true );
			$transaction_id            = $order->get_transaction_id();

			if ( $order->get_payment_method() === 'metaps_cs' ) {
				echo '<header class="title"><h3>' . esc_html__( 'Payment Detail', 'metaps-for-woocommerce' ) . '</h3></header>';
				echo '<table class="shop_table order_details">';
				echo '<tr><th>' . esc_html__( 'CVS Payment', 'metaps-for-woocommerce' ) . '</th><td>' . esc_html( $cs_stores[ $cvs_id ] ) . '</td></tr>' . PHP_EOL;
				echo '<tr><th>' . esc_html( $cvs_trans_title[ $cvs_id ] ) . '</th><td>' . esc_html( $transaction_id ) . '</td></tr>' . PHP_EOL;
				echo '<tr><th>' . esc_html__( 'Payment URL', 'metaps-for-woocommerce' ) . '</th><td><a href="' . esc_html( $payment_url ) . '" target="_blank">' . esc_html__( 'Pay from here.', 'metaps-for-woocommerce' ) . '</a></td></tr>' . PHP_EOL;
				if ( isset( $payment_limit_description ) ) {
					echo '<tr><th>' . esc_html__( 'Payment limit term', 'metaps-for-woocommerce' ) . '</th><td>' . esc_html( $payment_limit_description ) . '</td></tr>' . PHP_EOL;
				}
				echo '</table>';
			}
		}
	}

	/**
	 * Change the email subject for Metaps CS payment method when the order status is processing.
	 *
	 * @param string   $subject The original email subject.
	 * @param WC_Order $order   The order object.
	 * @return string The modified email subject.
	 */
	public function change_email_subject_metaps_cs( $subject, $order ) {
		$payment_method = $order->get_payment_method();
		$status         = $order->get_status();
		if ( 'metaps_cs' === $payment_method && 'processing' === $status ) {
			$payment_setting = get_option( 'woocommerce_metaps_cs_settings' );
			$subject         = $payment_setting['processing_email_subject'];
		}
		return $subject;
	}

	/**
	 * Change the email heading for Metaps CS payment method when the order status is processing.
	 *
	 * @param string   $heading The original email heading.
	 * @param WC_Order $order   The order object.
	 * @return string The modified email heading.
	 */
	public function change_email_heading_metaps_cs( $heading, $order ) {
		$payment_method = $order->get_payment_method();
		$status         = $order->get_status();
		if ( 'metaps_cs' === $payment_method && 'processing' === $status ) {
			$payment_setting = get_option( 'woocommerce_metaps_cs_settings' );
			$heading         = $payment_setting['processing_email_heading'];
		}
		return $heading;
	}

	/**
	 * Change the email instructions for Metaps CS payment method when the order status is processing.
	 *
	 * @param WC_Order $order The order object.
	 */
	public function change_email_instructions_cs( $order ) {
		$payment_method = $order->get_payment_method();
		$status         = $order->get_status();
		if ( 'metaps_cs' === $payment_method && 'processing' === $status ) {
			$payment_setting = get_option( 'woocommerce_metaps_cs_settings' );
			if ( isset( $payment_setting['processing_email_body'] ) ) {
				echo esc_html( $payment_setting['processing_email_body'] );
			}
		}
	}

	/**
	 * Get post data if set
	 *
	 * @param string $name The name of the POST field.
	 * @return string|null The sanitized POST field value or null if not set.
	 */
	public function get_post( $name ) {
		// Get the WC_Checkout object.
		$checkout = WC()->checkout();
		return $checkout->get_value( $name );
	}
}
