import { __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';
import { useEffect, RawHTML } from '@wordpress/element';
import { CVStoreSelectControl } from './components/cvstore-select';

/**
 * Internal dependencies
 */
import './index.scss';

const settings = getSetting( 'metaps_cs_data', {} );

const defaultLabel = __(
	'Convenience store',
	'metaps-for-wc'
);

const label = decodeEntities( settings.title ) || defaultLabel;

const description = decodeEntities( settings.description ) || '';

/**
 * Content component
 */
const Content = ( props ) => {
	const { eventRegistration } = props;
	const { onPaymentSetup } = eventRegistration;
	useEffect(
		() => 
			onPaymentSetup( () => {
				async function handlePaymentProcessing() {
					const convenience = document.getElementById( 'convenience' ).value;
					const customDataIsValid = !! convenience.length;

						if ( customDataIsValid ) {
							return {
								type: 'success',
								meta: {
									paymentMethodData: {
										convenience,
									},
								},
							};
						}

						return {
							type: 'error',
							message: __( 'Convenience store is not selected properly.', 'metaps-for-wc' ),
						};
					}
				return handlePaymentProcessing();
				}
		 	),
		// Unsubscribes when this component is unmounted.
		[
			onPaymentSetup,
		]
	);

	return (
		<div className={ 'metaps_cs' }>
			<RawHTML>{ description }</RawHTML>
			<CVStoreSelectControl />
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
const MetapsCS = {
	name: "metaps_cs",
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

registerPaymentMethod( MetapsCS );
