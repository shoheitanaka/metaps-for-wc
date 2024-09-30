import { __ } from '@wordpress/i18n';
import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalHeading as Heading,
	Button,
	Panel,
	PanelBody,
	PanelRow,
} from '@wordpress/components';
import { useSettings } from '../hooks';
import { Notices } from './notices';
import { 
    PrefixOrderTextControl,
    CreditCardCheckControl,
    CreditCardTokenCheckControl,
    ConveniencePaymentsCheckControl,
    PayEasyPaymentCheckControl,
} from './controls';

const SettingsTitle = () => {
	return (
		<Heading level={ 1 }>
			{ __( 'Metaps Payments settings', 'metaps-for-wc' ) }
		</Heading>
	);
};

const SaveButton = ( { onClick } ) => {
	return (
		<Button variant="primary" onClick={ onClick } __next40pxDefaultSize>
			{ __( 'Save', 'metaps-for-wc' ) }
		</Button>
	);
};

const SettingsPage = () => {
	const {
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
	} = useSettings();

    return (
		<>
			<SettingsTitle />
			<Notices />
            <Panel>
            <PanelBody
                title={ __( 'metaps Payment Initial Setting', 'metaps-for-wc' ) }
                className={ 'metaps-panels' }
                initialOpen={ true }
            >
                <PanelRow>
                    <PrefixOrderTextControl
						value={ prefixorder }
						onChange={ ( value ) => setPrefixOrder( value ) }
					/>
                </PanelRow>
                <PanelRow>
                    <div>
                        { __( 'Payment completion notification', 'metaps-for-wc' ) }<br/>
                        <span>{ __( 'Please use following url for URL for payment completion notification.', 'metaps-for-wc' ) }</span>
                    </div>
                </PanelRow>
            </PanelBody>
            <PanelBody
                title={ __( 'metaps Payment Payment Method', 'metaps-for-wc' ) }
                className={ 'metaps-payment-method' }
                initialOpen={ true }
            >
                <PanelRow>
                    <CreditCardCheckControl
                        value={ creditcardcheck }
                        onChange={ ( value ) => setCreditCardCheck( value ) }
                    />
                </PanelRow>
                <PanelRow>
                    <CreditCardTokenCheckControl
                        value={ creditcardtokencheck }
                        onChange={ ( value ) => setCreditCardTokenCheck( value ) }
                    />
                </PanelRow>
                <PanelRow>
                    <ConveniencePaymentsCheckControl 
                        value={ conveniencepaymentscheck }
                        onChange={ ( value ) => setConveniencePaymentsCheck( value ) }
                    />
                </PanelRow>
                <PanelRow>
                    <PayEasyPaymentCheckControl
                        value={ payeasypaymentcheck }
                        onChange={ ( value ) => setPayEasyPaymentCheck( value ) }
                    />
                </PanelRow>
            </PanelBody>
			</Panel>
			<SaveButton onClick={ saveSettings } />
		</>
	);
};
export { SettingsPage };
