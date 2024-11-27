import { __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';
import { SelectControl } from '@wordpress/components';

const settings = getSetting( 'metaps_cs_data', {} );

const CVStoreSelectControl = ( props ) => {
	const cvstoreSelect = [];
	if( settings.setting_cs_sv === 'yes' ){
		cvstoreSelect.push( { label: __( 'Seven-Eleven', 'metaps-for-woocommerce' ), value: '2' } );
	}
	if( settings.setting_cs_lp === 'yes' ){
		cvstoreSelect.push( { label: __( 'Lawson, MINISTOP', 'metaps-for-woocommerce' ), value: '5' } );
	}
	if( settings.setting_cs_fm === 'yes' ){
		cvstoreSelect.push( { label: __( 'family mart', 'metaps-for-woocommerce' ), value: '3' } );
	}
	if( settings.setting_cs_ol === 'yes' ){
		cvstoreSelect.push( { label: __( 'Daily Yamazaki', 'metaps-for-woocommerce' ), value: '73' } );
	}
	if( settings.setting_cs_sm === 'yes' ){
		cvstoreSelect.push( { label: __( 'Seicomart', 'metaps-for-woocommerce' ), value: '6' } );
	}
	return (
		<SelectControl
			label={ __( 'Convenience Store', 'metaps-for-woocommerce' ) }
			className={ 'convenience' }
			id={ 'convenience' }
			size={ 'compact' }
			options={ cvstoreSelect }
			help={ __( 'Please select the payment method of %s.', 'metaps-for-woocommerce' ).replace( '%s', __( 'Convenience Store', 'metaps-for-woocommerce' ) ) }
		/>
	);
};

export { CVStoreSelectControl };