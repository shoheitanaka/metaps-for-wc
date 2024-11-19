import { sprintf, __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';
import { useEffect, RawHTML } from '@wordpress/element';
import { NumberOfPaymentsSelectControl } from './components/number_of_payments';
import { UserIdPaymentSelectControl } from './components/user_id_payment';
import { CreditCardInputControl } from './components/credit_card_form';

/**
 * Internal dependencies
 */
import './index.scss';

const settings = getSetting( 'metaps_cc_token_data', {} );
const user_id_payment_setting = settings.user_id_payment || [];

const defaultLabel = __(
	'Credit Card',
	'metaps-for-wc'
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
	useEffect(
		() => 
			onPaymentSetup( () => {
				async function handlePaymentProcessing() {
					const num = document.getElementById( 'number_of_payments' );
					const number_of_payments = '';
					let customDataIsValid = '';
					if( num ) {
						const number_of_payments = num.value;
						customDataIsValid = !! number_of_payments.length;
					}
					const metaps_cc_token_id = document.getElementById( 'metaps_cc_token_id' ).value;
					const metapsCCTokenIsValid = !! metaps_cc_token_id.length;
					const metaps_cc_token_crno = document.getElementById( 'metaps_cc_token_crno' ).value;
					const metaps_cc_token_exp_y = document.getElementById( 'metaps_cc_token_exp_y' ).value;
					const metaps_cc_token_exp_m = document.getElementById( 'metaps_cc_token_exp_m' ).value;

					if ( customDataIsValid !== undefined && metapsCCTokenIsValid ) {
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
						message: __( 'Your credit card information has not been entered correctly. Please check the number of digits, etc.', 'metaps-for-wc' ),
					};
				}
				return handlePaymentProcessing();
			}
		),
	[
		onPaymentSetup,
	] );

	return (
		<div className={ 'metaps_cc_token' }>
			<RawHTML>{ description }</RawHTML>
			{ user_id_payment_setting === 'yes' && 
				<UserIdPaymentSelectControl />
			}
			{ user_id_payment_setting !== 'yes' &&
				<CreditCardInputControl />
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
