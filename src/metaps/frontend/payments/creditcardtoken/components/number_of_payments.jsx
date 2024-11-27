import { sprintf, __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';
import { SelectControl } from '@wordpress/components';

const settings = getSetting( 'metaps_cc_token_data', {} );

const NumberOfPaymentsSelectControl = () => {
	const numberOfPaymentsSelect = [];
	const numberOfPayments = settings.number_of_payments;
	for (let i = 0; i < numberOfPayments.length; i++) {
		numberOfPaymentsSelect.push( { label: numberOfPayments[i].value, value: numberOfPayments[i].id } );
	}
	const NumberOfPaymentsSelectControl = <SelectControl
		label={ __( 'Number of Payments', 'metaps-for-woocommerce' ) }
		className={ 'number_of_payments' }
		labelPosition={ 'side' }
		id={ 'number_of_payments' }
		size={ 'compact' }
		options={ numberOfPaymentsSelect }
		/>;

	return (
		<div className='number_of_payments'>
			{ numberOfPayments && NumberOfPaymentsSelectControl }
		</div>
	);
};

export { NumberOfPaymentsSelectControl };