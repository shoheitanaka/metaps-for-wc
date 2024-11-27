import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';

const getUserId = () => {
	const [ userSavedID, setUserSavedID ] = useState( '' );
	const [ isLoggedIn, setIsLoggedIn ] = useState( false );

	useEffect( () => {
		apiFetch( { path: '/wp/v2/users/me' } )
		.then( ( user ) => {
			setIsLoggedIn( true );
			setUserSavedID( user.meta._metaps_user_id );
		} )
		.catch( ( error ) => {
			// ユーザーが未ログインの場合
			if ( error.code === 'rest_not_logged_in' ) {
				setIsLoggedIn( false );
			} else {
				console.error( __( 'Failed to get user data:' , 'metaps-for-woocommerce' ) , error );
			}
		} );
	}, [] );

	return { userSavedID, isLoggedIn };
};

export default getUserId;
