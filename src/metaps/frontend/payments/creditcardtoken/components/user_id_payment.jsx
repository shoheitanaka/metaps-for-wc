import { sprintf, __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';
import { RadioControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { CreditCardInputControl } from './credit_card_form';

const settings = getSetting( 'metaps_cc_token_data', [] );
const user_id_payment_setting = settings.user_id_payment || [];

const UserIdPaymentSelectControl = () => {
	const [ option, setOption ] = useState( 'yes' );
	const user_id_payment_options = [
		{ label: __( 'Use Stored Card.', 'metaps-for-woocommerce' ), value: 'yes' },
		{ label: __( 'Use New Card.', 'metaps-for-woocommerce' ), value: 'no' },
	];
	
	return (
		<div className='user_id_payment'>
			{ user_id_payment_setting === 'yes' && 
			<RadioControl
				label={ __( 'User ID Payment', 'metaps-for-woocommerce' ) }
				className={ 'user_id_payment' }
				id={ 'user_id_payment' }
				selected={ option }
				options={ user_id_payment_options }
				onChange={ ( value ) => setOption( value )
				}
			/>
			}
			{ option === 'no' && <CreditCardInputControl /> }
		</div>
	);
}

export { UserIdPaymentSelectControl };