import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { store as noticesStore } from '@wordpress/notices';
import { useEffect, useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';

const useSettings = () => {
	const [ prefixorder, setPrefixOrder ] = useState();
	const [ creditcardcheck, setCreditCardCheck ] = useState();
    const [ creditcardtokencheck, setCreditCardTokenCheck ] = useState();
    const [ conveniencepaymentscheck, setConveniencePaymentsCheck ] = useState();
    const [ payeasypaymentcheck, setPayEasyPaymentCheck ] = useState();

	const { createSuccessNotice } = useDispatch( noticesStore );
	useEffect( () => {
		apiFetch( { path: '/wp/v2/settings' } ).then( ( settings ) => {
			setPrefixOrder( settings.woocommerce_metaps_settings.prefixorder );
			setCreditCardCheck( settings.woocommerce_metaps_settings.creditcardcheck );
            setCreditCardTokenCheck( settings.woocommerce_metaps_settings.creditcardtokencheck );
            setConveniencePaymentsCheck( settings.woocommerce_metaps_settings.conveniencepaymentscheck );
            setPayEasyPaymentCheck( settings.woocommerce_metaps_settings.payeasypaymentcheck );
		} );
	}, [] );

	const saveSettings = () => {
		apiFetch( {
			path: '/wp/v2/settings',
			method: 'POST',
			data: {
				woocommerce_metaps_settings: {
					prefixorder,
                    creditcardcheck,
                    creditcardtokencheck,
                    conveniencepaymentscheck,
                    payeasypaymentcheck,
				},
			},
		} ).then( () => {
			createSuccessNotice(
				__( 'Settings saved.', 'metaps-for-wc' )
			);
		} );
	};

	return {
        prefixorder,
		setPrefixOrder,
        creditcardcheck,
        setCreditCardCheck,
        creditcardtokencheck,
        setCreditCardTokenCheck,
        conveniencepaymentscheck,
        setConveniencePaymentsCheck,
        payeasypaymentcheck,
        setPayEasyPaymentCheck,
		saveSettings,
	};
};

export default useSettings;
