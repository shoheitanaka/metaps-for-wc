import { __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';
import { RawHTML } from '@wordpress/element';

const settings = getSetting( 'metaps_pe_data', {} );

const defaultLabel = __(
	'Pay-easy',
	'metaps-for-wc'
);

const label = decodeEntities( settings.title ) || defaultLabel;

const description = decodeEntities( settings.description ) || '';

/**
 * Content component
 */
const Content = () => {
	return (
		<div className={ 'metaps_pe' }>
			<RawHTML>{ description }</RawHTML>
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
const MetapsPE = {
	name: "metaps_pe",
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

registerPaymentMethod( MetapsPE );
