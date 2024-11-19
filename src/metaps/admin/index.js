import './index.scss';
import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';
import { SettingsPage } from './components';

domReady( () => {
    const root = createRoot(
        document.getElementById( 'metaps-settings' )
    );

    root.render( <SettingsPage /> );
} );