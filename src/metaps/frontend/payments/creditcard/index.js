
import { sprintf, __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';
import { useEffect, RawHTML } from '@wordpress/element';
import { NumberOfPaymentsSelectControl } from './components/number_of_payments';
import { UserIdPaymentSelectControl } from './components/user_id_payment';

const settings = getSetting( 'metaps_cc_data', {} );
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
					const number_of_payments = document.getElementById( 'number_of_payments' ).value;
					const customDataIsValid = !! number_of_payments.length;

					if ( customDataIsValid ) {
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
						message: __( 'There seems to be a problem. Please contact the site.', 'metaps-for-wc' ),
					};
				}
				return handlePaymentProcessing();
				}
			),
		[
			onPaymentSetup,
		]
	);

	return (
		<div className={ 'metaps_cc' }>
			<RawHTML>{ description }</RawHTML>
			{ user_id_payment_setting === 'yes' && 
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
