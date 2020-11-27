import { dispatch } from '@wordpress/data';

export function isOpen( state ) {
	return state.isOpen;
}

export function getMessage( state ) {
	return state.message;
}

export function confirm( state, message, cb ) {
	const { show, hide } = dispatch( 'learnpress/modal' );

	if ( ! state.message ) {
		show( message, cb );
	} else {
		hide();
		return state.confirm;
	}

	return 'no';
}
