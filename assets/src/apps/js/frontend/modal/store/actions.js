export function show( message, cb ) {
	return {
		type: 'SHOW_MODAL',
		message,
		cb,
	};
}

export function hide() {
	return {
		type: 'HIDE_MODAL',
	};
}

export function confirm( value ) {
	return {
		type: 'CONFIRM',
		value,
	};
}
