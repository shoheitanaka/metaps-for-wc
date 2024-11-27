import { __ } from '@wordpress/i18n';
import { TextControl } from '@wordpress/components';
import { useEffect,useState, useRef } from '@wordpress/element';

const CreditCardInputControl = ( prof ) => {
	const [cardNumber, setCardNumber] = useState('');
	const [expiryDate, setExpiryDate] = useState('');
	const [securityCode, setSecurityCode] = useState('');

	const tokenIdRef = useRef(null);
    const cardNumberTokenRef = useRef(null);
    const expYRef = useRef(null);
    const expMRef = useRef(null);

    // Load external script
	useEffect(() => {
		const script = document.createElement('script');
		script.src = "//www.paydesign.jp/settle/token/metapsToken-min.js?ver=1.0.0";
		script.id = "metaps_token_script-js";
		document.body.appendChild(script);

		return () => {
			document.body.removeChild(script);
		};
	}, []);
	
    // Event handler function
	const metapspaymentToken = () => {
		let cr = cardNumber.replace(/ /g, '');
		let cs = securityCode;
		let exp_my = expiryDate.replace(/ /g, '').replace('/', '');
		let exp_m = exp_my.slice(0, 2);
		let exp_y = exp_my.slice(2).slice(-2);

		if (window.metapspayment.validateCardNumber(cr) && 
			window.metapspayment.validateExpiry(exp_m, exp_y) && 
			window.metapspayment.validateCSC(cs)) {
				
			if (tokenIdRef.current) tokenIdRef.current.value = '';
			window.metapspayment.setTimeout(20000);
			window.metapspayment.setLang("ja");
			window.metapspayment.createToken({ number: cr, csc: cs, exp_m: exp_m, exp_y: exp_y }, metapspaymentResponseHandler);
		}
	};

	// Response handler function
	const metapspaymentResponseHandler = (status, response) => {
		if (tokenIdRef.current) tokenIdRef.current.value = response.id;
		if (cardNumberTokenRef.current) cardNumberTokenRef.current.value = response.crno;
		if (expYRef.current) expYRef.current.value = response.exp_y;
		if (expMRef.current) expMRef.current.value = response.exp_m;
	};
	
	// Trigger metapspaymentToken on input change
	useEffect(() => {
		metapspaymentToken();
	}, [cardNumber, securityCode, expiryDate]);	

	const handleCardNumberChange = ( cardNumber ) => {
	    // Remove all non-numeric characters
	    let value = cardNumber.replace(/\D/g, '');
	    // Limited to a maximum of 16 digits
	    value = value.slice(0, 16);
	    // Insert a space every 4 digits
	    let formattedValue = value.match(/.{1,4}/g)?.join(' ') || '';
	    setCardNumber(formattedValue);
	};

	const handleExpiryDateChange = ( expiryDate ) => {
	    // Remove all non-numeric characters
		let value = expiryDate.replace(/\D/g, '');
	
		// Limited to a maximum of 4 digits
		value = value.slice(0, 4);
	
		// Insert a '/' every 2 digits
		if (value.length >= 3) {
		  value = value.slice(0, 2) + '/' + value.slice(2);
		}
	
		setExpiryDate(value);
	  };
	
	  const handleExpiryDateBlur = () => {
		const [month, year] = expiryDate.split('/');
	
		// Check that the month and year are entered
		if (!month || !year) {
		  return;
		}
	
		// Validation of the month
		const monthNum = parseInt(month, 10);
		if (monthNum < 1 || monthNum > 12) {
		  alert( __( 'Please enter a month between 01 and 12.', 'metaps-for-woocommerce' ) );
		  setExpiryDate('');
		  inputRef.current.focus();
		  return;
		}
	
		// Validation of the year
		const currentDate = new Date();
		const currentYear = currentDate.getFullYear() % 100; // Last two digits of current year
		const currentMonth = currentDate.getMonth() + 1; // Month starts from 0 so +1
	
		const inputYear = parseInt(year, 10);
		const inputMonth = parseInt(month, 10);
	
		let fullInputYear = 2000 + inputYear; // Assumed to be after 2000
	
		// Check if the entered year is within 20 years of the current year
		const maxYear = currentDate.getFullYear() + 20;
	
		if (fullInputYear < currentDate.getFullYear() || fullInputYear > maxYear) {
		  alert(`年は${currentYear}から${maxYear % 100}の間で入力してください。`);
		  setExpiryDate(expiryDate.slice(0, 3));
		  inputRef.current.focus();
		  return;
		}
	
		// Check if the expiration date is after the current date
		const expiry = new Date(fullInputYear, inputMonth - 1); // Months start at 0
		const today = new Date(currentDate.getFullYear(), currentDate.getMonth());
	
		if (expiry < today) {
		  alert( __( 'The expiration date must be the current year or later.', 'metaps-for-woocommerce' ) );
		  setExpiryDate('');
		  inputRef.current.focus();
		  return;
		}
	  };
	
	return (
		<div className={ 'wc-block-card-elements' }>
			<div className={ 'wc-block-gateway-container wc-card-number-element' }>
				<div className={ 'wc-block-gateway-input' }>
					<TextControl
						className={ 'card_number' }
						id={ 'card_number' }
						type={ 'text' }
						value={ cardNumber }
						onChange={ handleCardNumberChange }
						placeholder={ '4242 4242 4242 4242' }
					/>
				</div>
			</div>
			<div className={ 'wc-block-gateway-container wc-card-expiry-element' }>
				<div className={ 'wc-block-gateway-input' }>
					<TextControl
						className={ 'expiration_date' }
						id={ 'expiration_date' }
						type={ 'text' }
						value={ expiryDate }
						onChange={ handleExpiryDateChange }
						onBlur={ handleExpiryDateBlur }
						placeholder={ 'MM/YY' }
					/>
				</div>
			</div>
			<div className={ 'wc-block-gateway-container wc-card-cvc-element' }>
				<div className={ 'wc-block-gateway-input' }>
					<TextControl
						className={ 'security_code' }
						id={ 'security_code' }
						type={ 'text' }
						value={ securityCode }
						onChange={ ( value ) => setSecurityCode( value ) }
						placeholder={ 'CVV/CVC' }
					/>
				</div>
			</div>
			<div className={ 'input-hiddens' }>
				<input type={ 'hidden' } ref={ tokenIdRef } id={ 'metaps_cc_token_id' }/>
				<input type={ 'hidden' } ref={ cardNumberTokenRef } id={ 'metaps_cc_token_crno' }/>
				<input type={ 'hidden' } ref={ expYRef } id={ 'metaps_cc_token_exp_y' }/>
				<input type={ 'hidden' } ref={ expMRef } id={ 'metaps_cc_token_exp_m' }/>
			</div>
		</div>
	);
};

export { CreditCardInputControl };