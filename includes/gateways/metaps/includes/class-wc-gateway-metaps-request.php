<?php
/**
 * WC Gateway Metaps Request Class
 *
 * This file contains the class WC_Gateway_Metaps_Request which handles requests to Metaps.
 *
 * @category PaymentGateway
 * @package  MetapsForWC
 * @license  GPL-3.0
 * @link     https://wc.artws.info/
 */

use ArtisanWorkshop\PluginFramework\v2_0_12 as Framework;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Generates requests to send to metaps
 *
 * @category PaymentGateway
 * @package  MetapsForWC
 * @author   Shohei Tanaka <cs@artws.info>
 * @license  GPL-3.0
 * @link     https://wc.artws.info/
 */
class WC_Gateway_Metaps_Request {
	/**
	 * Framework.
	 *
	 * @var object
	 */
	public $jp4wc_framework;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->jp4wc_framework = new Framework\JP4WC_Framework();
	}

	/**
	 * Get metaps PAYMENT Args for passing to PP
	 *
	 * @param WC_Order $order order data.
	 * @param string   $connect_url connect URL.
	 * @param array    $setting setting data.
	 * @param string   $thanks_url thanks URL.
	 * @param string   $debug debug mode.
	 * @param string   $emv_tds 3D Secure mode.
	 * @return string URL for metaps.
	 */
	public function get_post_to_metaps( $order, $connect_url, $setting, $thanks_url = null, $debug = 'yes', $emv_tds = 'no' ) {
		global $woocommerce;
		// Set States Information.
		$states = WC()->countries->get_allowed_country_states();

		$post_data['OKURL'] = $thanks_url;
		$post_data['RT']    = $woocommerce->cart->get_cart_url() . '?pd=return&sid=' . $setting['sid'];
		// Customer parameter.
		$post_data = $this->metaps_address( $post_data, $order, $states );
		$post_data = $this->metaps_setting( $post_data, $order, $setting );
		if ( 'yes' === $emv_tds ) {
			$post_data = $this->emv_tds_parameter( $post_data, $order );
		}
		$get_source = http_build_query( $post_data );
		$get_url    = $connect_url . '?' . $get_source;

		$this->metaps_set_log( $connect_url, $order, $post_data, $debug );

		// GET URL.
		return $get_url;
	}

	/**
	 * Set User Information
	 *
	 * @param array    $post_data post data.
	 * @param WP_order $order    order data.
	 * @param array    $states State data.
	 * @return array post data
	 */
	public function metaps_address( $post_data, $order, $states ) {
		$post_data['MAIL']          = $order->get_billing_email();
		$post_data['NAME1']         = mb_convert_encoding( $order->get_billing_last_name(), 'SJIS' );
		$post_data['NAME2']         = mb_convert_encoding( $order->get_billing_first_name(), 'SJIS' );
		$post_data['YUBIN1']        = str_replace( '-', '', $order->get_billing_postcode() );
		$state                      = $states['JP'][ $order->get_billing_state() ];
		$post_data['ADR1']          = mb_convert_encoding( $state . $order->get_billing_city(), 'SJIS' );
		$post_data['TEL']           = substr( str_replace( '-', '', $order->get_billing_phone() ), 0, 11 );
		$billing_address_1          = $order->get_billing_address_1();
		$billing_address_2          = $order->get_billing_address_2();
		$billing_yomigana_last_name = $order->get_meta( '_wc_billing/jp4wc/yomigana-last-name', true );
		if ( ! $billing_yomigana_last_name ) {
			$billing_yomigana_last_name = $order->get_meta( $order->get_id(), '_billing_yomigana_last_name', true );
		}
		$billing_yomigana_first_name = $order->get_meta( '_wc_billing/jp4wc/yomigana-first-name', true );
		if ( ! $billing_yomigana_first_name ) {
			$billing_yomigana_first_name = $order->get_meta( $order->get_id(), '_billing_yomigana_first_name', true );
		}
		if ( strlen( $post_data['NAME1'] ) > 20 ) {
			$post_data['NAME1'] = substr( $post_data['NAME1'], 0, 20 );
		}
		if ( strlen( $post_data['NAME2'] ) > 20 ) {
			$post_data['NAME2'] = substr( $post_data['NAME2'], 0, 20 );
		}
		if ( $billing_yomigana_last_name && $billing_yomigana_first_name ) {
			$post_data['KANA1'] = mb_convert_encoding( $billing_yomigana_last_name, 'SJIS' );
			if ( strlen( $post_data['KANA1'] ) > 20 ) {
				$post_data['KANA1'] = substr( $post_data['KANA1'], 0, 20 );
			}
			$post_data['KANA2'] = mb_convert_encoding( $billing_yomigana_first_name, 'SJIS' );
			if ( strlen( $post_data['KANA2'] ) > 20 ) {
				$post_data['KANA2'] = substr( $post_data['KANA2'], 0, 20 );
			}
		}
		if ( strlen( $post_data['YUBIN1'] ) > 3 ) {
			$post_data['YUBIN2'] = substr( $post_data['YUBIN1'], -4 );
			$post_data['YUBIN1'] = substr( $post_data['YUBIN1'], 0, 3 );
		}
		if ( strlen( $post_data['ADR1'] ) > 50 ) {
			$post_data['ADR1'] = substr( $post_data['ADR1'], 0, 50 );
		}
		if ( isset( $billing_address_2 ) ) {
			$post_data['ADR2'] = mb_convert_encoding( $billing_address_1 . $billing_address_2, 'SJIS' );
		} else {
			$post_data['ADR2'] = mb_convert_encoding( $billing_address_1, 'SJIS' );
		}
		if ( strlen( $post_data['ADR2'] ) > 50 ) {
			$post_data['ADR2'] = mb_convert_encoding( substr( $post_data['ADR2'], 0, 50 ), 'SJIS', 'SJIS' );
		}

		return $post_data;
	}

	/**
	 * Set User Information for 3D Secure
	 *
	 * @param array    $post_data      post data.
	 * @param WP_order $order order data.
	 * @return array $post_data
	 */
	public function emv_tds_parameter( $post_data, $order ) {
		// ISO 3166-1 alpha-2 to numeric mapping.
		$iso_codes    = array(
			'AF' => '004',
			'AL' => '008',
			'DZ' => '012',
			'AS' => '016',
			'AD' => '020',
			'AO' => '024',
			'AG' => '028',
			'AR' => '032',
			'AM' => '051',
			'AU' => '036',
			'AT' => '040',
			'AZ' => '031',
			'BS' => '044',
			'BH' => '048',
			'BD' => '050',
			'BB' => '052',
			'BY' => '112',
			'BE' => '056',
			'BZ' => '084',
			'BJ' => '204',
			'BT' => '064',
			'BO' => '068',
			'BA' => '070',
			'BW' => '072',
			'BR' => '076',
			'BN' => '096',
			'BG' => '100',
			'BF' => '854',
			'BI' => '108',
			'KH' => '116',
			'CM' => '120',
			'CA' => '124',
			'CV' => '132',
			'CF' => '140',
			'TD' => '148',
			'CL' => '152',
			'CN' => '156',
			'CO' => '170',
			'KM' => '174',
			'CG' => '178',
			'CD' => '180',
			'CR' => '188',
			'CI' => '384',
			'HR' => '191',
			'CU' => '192',
			'CY' => '196',
			'CZ' => '203',
			'DK' => '208',
			'DJ' => '262',
			'DM' => '212',
			'DO' => '214',
			'EC' => '218',
			'EG' => '818',
			'SV' => '222',
			'GQ' => '226',
			'ER' => '232',
			'EE' => '233',
			'ET' => '231',
			'FJ' => '242',
			'FI' => '246',
			'FR' => '250',
			'GA' => '266',
			'GM' => '270',
			'GE' => '268',
			'DE' => '276',
			'GH' => '288',
			'GR' => '300',
			'GD' => '308',
			'GT' => '320',
			'GN' => '324',
			'GW' => '624',
			'GY' => '328',
			'HT' => '332',
			'HN' => '340',
			'HU' => '348',
			'IS' => '352',
			'IN' => '356',
			'ID' => '360',
			'IR' => '364',
			'IQ' => '368',
			'IE' => '372',
			'IL' => '376',
			'IT' => '380',
			'JM' => '388',
			'JP' => '392',
			'JO' => '400',
			'KZ' => '398',
			'KE' => '404',
			'KI' => '296',
			'KP' => '408',
			'KR' => '410',
			'KW' => '414',
			'KG' => '417',
			'LA' => '418',
			'LV' => '428',
			'LB' => '422',
			'LS' => '426',
			'LR' => '430',
			'LY' => '434',
			'LI' => '438',
			'LT' => '440',
			'LU' => '442',
			'MG' => '450',
			'MW' => '454',
			'MY' => '458',
			'MV' => '462',
			'ML' => '466',
			'MT' => '470',
			'MH' => '584',
			'MR' => '478',
			'MU' => '480',
			'MX' => '484',
			'FM' => '583',
			'MD' => '498',
			'MC' => '492',
			'MN' => '496',
			'ME' => '499',
			'MA' => '504',
			'MZ' => '508',
			'MM' => '104',
			'NA' => '516',
			'NR' => '520',
			'NP' => '524',
			'NL' => '528',
			'NZ' => '554',
			'NI' => '558',
			'NE' => '562',
			'NG' => '566',
			'NO' => '578',
			'OM' => '512',
			'PK' => '586',
			'PW' => '585',
			'PA' => '591',
			'PG' => '598',
			'PY' => '600',
			'PE' => '604',
			'PH' => '608',
			'PL' => '616',
			'PT' => '620',
			'QA' => '634',
			'RO' => '642',
			'RU' => '643',
			'RW' => '646',
			'KN' => '659',
			'LC' => '662',
			'VC' => '670',
			'WS' => '882',
			'SM' => '674',
			'ST' => '678',
			'SA' => '682',
			'SN' => '686',
			'RS' => '688',
			'SC' => '690',
			'SL' => '694',
			'SG' => '702',
			'SK' => '703',
			'SI' => '705',
			'SB' => '090',
			'SO' => '706',
			'ZA' => '710',
			'ES' => '724',
			'LK' => '144',
			'SD' => '729',
			'SR' => '740',
			'SZ' => '748',
			'SE' => '752',
			'CH' => '756',
			'SY' => '760',
			'TW' => '158',
			'TJ' => '762',
			'TZ' => '834',
			'TH' => '764',
			'TL' => '626',
			'TG' => '768',
			'TO' => '776',
			'TT' => '780',
			'TN' => '788',
			'TR' => '792',
			'TM' => '795',
			'UG' => '800',
			'UA' => '804',
			'AE' => '784',
			'GB' => '826',
			'US' => '840',
			'UY' => '858',
			'UZ' => '860',
			'VU' => '548',
			'VE' => '862',
			'VN' => '704',
			'YE' => '887',
			'ZM' => '894',
			'ZW' => '716',
		);
		$country_code = $order->get_billing_country();
		if ( isset( $iso_codes[ $country_code ] ) ) {
			$post_data['BILL_ADDR_COUNTRY'] = $iso_codes[ $country_code ];
		} else {
			$post_data['BILL_ADDR_COUNTRY'] = '392';// Japan code.
		}
		$post_data['BILL_ADDR_STATE'] = substr( $order->get_billing_state(), 2 );
		$post_data['BILL_ADDR_ZIP']   = str_replace( '-', '', $order->get_billing_postcode() );
		$billing_city                 = $order->get_billing_city();
		$billing_address_1            = $order->get_billing_address_1();
		$post_data['BILL_ADDR_CITY']  = mb_convert_encoding( $billing_city, 'SJIS' );
		$post_data['BILL_ADDR_LINE']  = mb_convert_encoding( $billing_address_1, 'SJIS' );
		$countries                    = new WC_Countries();
		$post_data['TEL_COUNTRY']     = substr( $countries->get_country_calling_code( $country_code ), 1 );

		return $post_data;
	}

	/**
	 * Set Setting Information
	 *
	 * @param array    $post_data post data.
	 * @param WP_order $order order data.
	 * @param array    $setting setting data.
	 * @return array $post_data post data.
	 */
	public function metaps_setting( $post_data, $order, $setting ) {

		// set post data.
		$post_data['IP']  = $setting['ip'];
		$post_data['SID'] = $setting['sid'];
		if ( isset( $setting['kakutei'] ) ) {
			$post_data['KAKUTEI'] = $setting['kakutei'];
		}
		if ( isset( $setting['pass'] ) ) {
			$post_data['PASS'] = $setting['pass'];
		}
		if ( isset( $setting['store'] ) ) {
			$post_data['STORE'] = $setting['store'];
		}
		if ( isset( $setting['lang'] ) ) {
			$post_data['LANG'] = $setting['lang'];
		}
		if ( isset( $setting['ip_user_id'] ) ) {
			$post_data['IP_USER_ID'] = $setting['ip_user_id'];
		}
		// Set Products Name.
		$order_items = $order->get_items();
		foreach ( $order_items as $item_key => $item_values ) {
			$item_name[] = mb_convert_encoding( $item_values->get_name(), 'SJIS' );
		}
		$post_data['N1'] = mb_convert_encoding( substr( $item_name[0], 0, 50 ), 'SJIS', 'SJIS' );
		$post_data['K1'] = $order->get_total();

		// Convenience parameter.
		if ( isset( $setting['kigen'] ) ) {
			$post_data['KIGEN'] = $setting['kigen'];
		}
		// Token parameter.
		if ( isset( $setting['token'] ) ) {
			$post_data['TOKEN'] = $setting['token'];
		}

		if ( isset( $setting['paymode'] ) ) {
			$post_data['PAYMODE'] = $setting['paymode'];
			if ( isset( $setting['incount'] ) ) {
				$post_data['INCOUNT'] = $setting['incount'];
			}
		}

		return $post_data;
	}

	/**
	 * Send the request to metaps's API for URL
	 *
	 * @param array  $data post data.
	 * @param string $connect_url connect URL.
	 * @param object $order order data.
	 * @param string $debug debug mode.
	 * @return string response_url
	 */
	public function metaps_request( $data, $connect_url, $order, $debug = 'yes' ) {
		$get_source = http_build_query( $data );
		$get_url    = $connect_url . '?' . $get_source;
		$response   = wp_remote_get( $get_url );
		if ( is_wp_error( $response ) ) {
			return '';
		}
		$response = wp_remote_retrieve_body( $response );

		$this->metaps_set_log( $connect_url, $order, $data, 'request', $debug );
		$this->metaps_set_log( $connect_url, $order, $response, 'response', $debug );

		return $response;
	}

	/**
	 * Send the request to metaps's API to module
	 *
	 * @param WP_Order $order order data.
	 * @param string   $connect_url connect URL.
	 * @param array    $setting setting data.
	 * @param string   $debug debug mode.
	 * @param string   $emv_tds 3D Secure mode.
	 * @return string response data.
	 */
	public function metaps_post_request( $order, $connect_url, $setting, $debug = 'yes', $emv_tds = 'no' ) {
		// Set States Information.
		$states = WC()->countries->get_allowed_country_states();

		$post_data = array();
		$post_data = $this->metaps_setting( $post_data, $order, $setting );
		$post_data = $this->metaps_address( $post_data, $order, $states );
		if ( 'yes' === $emv_tds ) {
			$post_data = $this->emv_tds_parameter( $post_data, $order );
		}

		$get_source = http_build_query( $post_data );
		$get_url    = $connect_url . '?' . $get_source;
		$response   = file( $get_url );

		$this->metaps_set_log( $connect_url, $order, $post_data, 'request', $debug );
		$this->metaps_set_log( $connect_url, $order, $response, 'response', $debug );

		return $response;
	}

	/**
	 * Set log for debug
	 *
	 * @param string $connect_url connect URL.
	 * @param object $order order data.
	 * @param array  $data post data.
	 * @param string $type type.
	 * @param string $debug debug mode.
	 * @return void
	 */
	public function metaps_set_log( $connect_url, $order, $data, $type, $debug ) {
		// Save debug send data.
		$send_message = 'connect URL : ' . $connect_url . "\n";
		if ( ! is_null( $order ) ) {
			// translators: %s is the type of data being sent.
			$send_message .= sprintf( __( 'This %s send data for order ID:', 'metaps-for-wc' ), $type ) . $order->get_id() . "\n";
		}
		$request_array = array();
		foreach ( $data as $key => $value ) {
			$request_array[ $key ] = mb_convert_encoding( $value, 'UTF-8', 'SJIS' );
		}
		// translators: %s is the type of data being sent.
		$send_message .= sprintf( __( 'The %s post data is shown below.', 'metaps-for-wc' ), $type ) . "\n" . $this->jp4wc_framework->jp4wc_array_to_message( $request_array );
		$this->jp4wc_framework->jp4wc_debug_log( $send_message, $debug, 'wc-metaps' );
	}
}
