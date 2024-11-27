
import { sprintf, __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';
import { useEffect, RawHTML, useState } from '@wordpress/element';
import { NumberOfPaymentsSelectControl } from './components/number_of_payments';
import { UserIdPaymentSelectControl } from './components/user_id_payment';
import { getUserId } from '../hooks';

const settings = getSetting( 'metaps_cc_data', {} );
const user_id_payment_setting = settings.user_id_payment || [];

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
					let numberOfPaymentsValid;
					if ( settings.number_of_payments ){
						const num = document.getElementById( 'number_of_payments' );
						if( num ) {
							number_of_payments = num.value;
							numberOfPaymentsValid = !! number_of_payments.length;
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
				

					if ( user_id_payment === 'yes' ) {
						return {
							type: 'success',
							meta: {
								paymentMethodData: {
									number_of_payments,
									user_id_payment,
								},
							},
						};
					} else if ( numberOfPaymentsValid ) {
						return {
							type: 'success',
							meta: {
								paymentMethodData: {
									number_of_payments,
								},
							},
						};
					}

					return {
						type: 'error',
						message: __( 'There seems to be a problem. Please contact the site.', 'metaps-for-woocommerce' ),
					};
				}
				return handlePaymentProcessing();
				}
			),
		[
			onPaymentSetup,
			userSavedID
		]
	);

	return (
		<div className={ 'metaps_cc' }>
			<RawHTML>{ description }</RawHTML>
			{ user_id_payment_setting === 'yes' && isLoggedIn && 
				<UserIdPaymentSelectControl />
			}
			<NumberOfPaymentsSelectControl />
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
const MetapsCC = {
	name: "metaps_cc",
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

registerPaymentMethod( MetapsCC );
