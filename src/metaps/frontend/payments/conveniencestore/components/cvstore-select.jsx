import { __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';
import { SelectControl } from '@wordpress/components';

const settings = getSetting( 'metaps_cs_data', {} );

const CVStoreSelectControl = ( props ) => {
	const cvstoreSelect = [];
	if( settings.setting_cs_sv === 'yes' ){
		cvstoreSelect.push( { label: __( 'Seven-Eleven', 'metaps-for-wc' ), value: '2' } );
	}
	if( settings.setting_cs_lp === 'yes' ){
		cvstoreSelect.push( { label: __( 'Lawson, MINISTOP', 'metaps-for-wc' ), value: '5' } );
	}
	if( settings.setting_cs_fm === 'yes' ){
		cvstoreSelect.push( { label: __( 'family mart', 'metaps-for-wc' ), value: '3' } );
	}
	if( settings.setting_cs_ol === 'yes' ){
		cvstoreSelect.push( { label: __( 'Daily Yamazaki', 'metaps-for-wc' ), value: '73' } );
	}
	if( settings.setting_cs_sm === 'yes' ){
		cvstoreSelect.push( { label: __( 'Seicomart', 'metaps-for-wc' ), value: '6' } );
	}
	return (
		<SelectControl
			label={ __( 'Convenience Store', 'metaps-for-wc' ) }
			className={ 'convenience' }
			id={ 'convenience' }
			size={ 'compact' }
			options={ cvstoreSelect }
			help={ __( 'Please select the payment method of %s.', 'metaps-for-wc' ).replace( '%s', __( 'Convenience Store', 'metaps-for-wc' ) ) }
		/>
	);
};

export { CVStoreSelectControl };