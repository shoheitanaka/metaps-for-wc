import { __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';
import { useEffect, RawHTML, useState } from '@wordpress/element';
import { NumberOfPaymentsSelectControl } from './components/number_of_payments';
import { CreditCardInputControl } from './components/credit_card_form';
import { UserIdPaymentSelectControl } from './components/user_id_payment';
import { getUserId } from '../hooks';

/**
 * Internal dependencies
 */
import './index.scss';

const settings = getSetting( 'metaps_cc_token_data', {} );
let user_id_payment_setting = settings.user_id_payment || [];
const numberOfPayments = settings.number_of_payments;

const defaultLabel = __(
	'Credit Card',
	'metaps-for-woocommerce'
);

const label = decodeEntities( settings.title ) || defaultLabel;
const description = decodeEntities( settings.description ) || '';

/**
 * Content component
 *
 * @param {*} props Props from payment API.
 */
const Content = ( props ) => {
	const { eventRegistration } = props;
	const { onPaymentSetup } = eventRegistration;
	const { userSavedID, isLoggedIn } = getUserId();

	useEffect(
		() => 
			onPaymentSetup( () => {
				async function handlePaymentProcessing() {
					let number_of_payments;
					if ( settings.number_of_payments ){
						const num = document.getElementById( 'number_of_payments' );
						if( num ) {
							number_of_payments = num.value;
						}
					}

					if( userSavedID !== '' ){
						user_id_payment_setting = 'yes';
					} else {
						user_id_payment_setting = 'no';
					}

					const selectedUserIdPaymentData = document.querySelector('input[name="user_id_payment"]:checked');
					let user_id_payment;
					if( selectedUserIdPaymentData ){
						user_id_payment = selectedUserIdPaymentData.value;
					}
				
					const metaps_cc_token_id_data = document.getElementById( 'metaps_cc_token_id' );
					let metapsCCTokenIsValid;
					let metaps_cc_token_id;
					if( metaps_cc_token_id_data ){
						metapsCCTokenIsValid = !! metaps_cc_token_id_data.value.length;
						metaps_cc_token_id = metaps_cc_token_id_data.value;
					}
					const metaps_cc_token_crno_data = document.getElementById( 'metaps_cc_token_crno' );
					const metaps_cc_token_exp_y_data = document.getElementById( 'metaps_cc_token_exp_y' );
					const metaps_cc_token_exp_m_data = document.getElementById( 'metaps_cc_token_exp_m' );
					let metaps_cc_token_crno;
					if( metaps_cc_token_crno_data ){
						metaps_cc_token_crno = metaps_cc_token_crno_data.value;
					}
					let metaps_cc_token_exp_y;
					if( metaps_cc_token_exp_y_data ){
						metaps_cc_token_exp_y = metaps_cc_token_exp_y_data.value;
					}
					let metaps_cc_token_exp_m;
					if( metaps_cc_token_exp_m_data ){
						metaps_cc_token_exp_m = metaps_cc_token_exp_m_data.value;
					}

					if( user_id_payment === 'yes' ){
						return {
							type: 'success',
							meta: {
								paymentMethodData: {
									number_of_payments,
									user_id_payment,
								},
							},
						};
					} else if ( metapsCCTokenIsValid === true ) {
						return {
							type: 'success',
							meta: {
								paymentMethodData: {
									number_of_payments,
									metaps_cc_token_id,
									metaps_cc_token_crno,
									metaps_cc_token_exp_y,
									metaps_cc_token_exp_m,
								},
							},
						};
					}

					return {
						type: 'error',
						message: __( 'Your credit card information has not been entered correctly. Please check the number of digits, etc.', 'metaps-for-woocommerce' ),
					};
				}
				return handlePaymentProcessing();
			}
		),
	[
		onPaymentSetup,
		userSavedID
	] );

	return (
		<div className={ 'metaps_cc_token' }>
			<RawHTML>{ description }</RawHTML>
			{ user_id_payment_setting === 'yes' && isLoggedIn && 
				<UserIdPaymentSelectControl />
			}
			{ user_id_payment_setting !== 'yes' &&
				<CreditCardInputControl />
			}
			{ numberOfPayments &&
				<NumberOfPaymentsSelectControl />
			}
		</div>
	);
};

/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = ( props ) => {
	const { PaymentMethodLabel } = props.components;
	return <PaymentMethodLabel text={ label } />;
};

/**
 * MetapsCC payment method config object.
 */
const MetapsCCToken = {
	name: "metaps_cc_token",
	label: <Label />,
	content: <Content />,
	edit: <Content />,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: settings.supports,
		icons: true,
	},
};

registerPaymentMethod( MetapsCCToken );
