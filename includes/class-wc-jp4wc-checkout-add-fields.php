<?php
/**
 * Add checkout addition field in WooCommerce.
 *
 * @package WC_JP4WC_Checkout_Add_Fields
 * @author   Shohei Tanaka
 * @license  GPL-3.0 https://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_JP4WC_Checkout_Add_Fields' ) ) {

	/**
	 * Class WC_JP4WC_Checkout_Add_Fields
	 *
	 * This class handles the addition of the checkout page in WooCommerce.
	 */
	class WC_JP4WC_Checkout_Add_Fields {

		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'woocommerce_init', array( $this, 'checkout_add_yomigana_fields' ) );
			add_filter( 'woocommerce_get_country_locale', array( $this, 'address_add_yomigana_fields' ), 99 );
			add_filter( 'woocommerce_address_to_edit', array( $this, 'address_sorting_edit_fields' ), 99 );
		}

		/**
		 * Add checkout
		 */
		public function checkout_add_yomigana_fields() {
			if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
				return;
			}
			woocommerce_register_additional_checkout_field(
				array(
					'id'            => 'jp4wc/yomigana-last-name',
					'label'         => __( 'Last Name Yomigana', 'metaps-for-woocommerce' ),
					'optionalLabel' => __( 'Last Name Yomigana (optional)', 'metaps-for-woocommerce' ),
					'location'      => 'address',
					'required'      => false,
					'attributes'    => array(
						'aria-label' => __( 'Last Name Yomigana', 'metaps-for-woocommerce' ),
						'title'      => __( 'Last Name Yomigana', 'metaps-for-woocommerce' ),
					),
				),
			);
			woocommerce_register_additional_checkout_field(
				array(
					'id'            => 'jp4wc/yomigana-first-name',
					'label'         => __( 'First Name Yomigana', 'metaps-for-woocommerce' ),
					'optionalLabel' => __( 'First Name Yomigana (optional)', 'metaps-for-woocommerce' ),
					'location'      => 'address',
					'required'      => true,
					'attributes'    => array(
						'aria-label' => __( 'First Name Yomigana', 'metaps-for-woocommerce' ),
						'title'      => __( 'First Name Yomigana', 'metaps-for-woocommerce' ),
					),
				),
			);
		}

		/**
		 * Add Yomigana fields to the address fields.
		 *
		 * @param array $fields The existing address fields.
		 * @return array The modified address fields.
		 */
		public function address_add_yomigana_fields( $fields ) {
			$fields['JP']['jp4wc/yomigana-last-name']  = array(
				'label'       => __( 'Last Name Yomigana', 'metaps-for-woocommerce' ),
				'placeholder' => _x( 'Last Name Yomigana', 'placeholder', 'metaps-for-woocommerce' ),
				'required'    => false,
				'class'       => array( 'form-row-first' ),
				'clear'       => true,
				'priority'    => 25,
			);
			$fields['JP']['jp4wc/yomigana-first-name'] = array(
				'label'       => __( 'First Name Yomigana', 'metaps-for-woocommerce' ),
				'placeholder' => _x( 'First Name Yomigana', 'placeholder', 'metaps-for-woocommerce' ),
				'required'    => false,
				'class'       => array( 'form-row-last' ),
				'clear'       => true,
				'priority'    => 26,
			);
			return $fields;
		}

		/**
		 * Sorting the address fields at my account edit page.
		 *
		 * @param array $fields The existing address fields.
		 * @return array The modified address fields.
		 */
		public function address_sorting_edit_fields( $fields ) {
			$type_array = array( '_wc_billing', '_wc_shipping' );

			foreach ( $type_array as $type ) {
				$first_name_position = 1;
				if ( isset( $fields[ $type . '/jp4wc/yomigana-last-name' ] ) ) {
					$insert_last_name_field          = $fields[ $type . '/jp4wc/yomigana-last-name' ];
					$insert_last_name_field['class'] = array( 'form-row-first' );
					unset( $fields[ $type . '/jp4wc/yomigana-last-name' ] );
					$fields = array_slice( $fields, 0, $first_name_position + 1, true ) +
						array( $type . '/jp4wc/yomigana-last-name' => $insert_last_name_field ) +
						array_slice( $fields, $first_name_position + 1, null, true );
				}
				if ( isset( $fields[ $type . '/jp4wc/yomigana-first-name' ] ) ) {
					$insert_first_name_field          = $fields[ $type . '/jp4wc/yomigana-first-name' ];
					$insert_first_name_field['class'] = array( 'form-row-last' );
					unset( $fields[ $type . '/jp4wc/yomigana-first-name' ] );
					$fields = array_slice( $fields, 0, $first_name_position + 2, true ) +
						array( $type . '/jp4wc/yomigana-first-name' => $insert_first_name_field ) +
						array_slice( $fields, $first_name_position + 2, null, true );
				}
			}
			return $fields;
		}
	}

	new WC_JP4WC_Checkout_Add_Fields();
}
