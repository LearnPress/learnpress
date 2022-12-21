const STORE_DATA = {};

export const Modal = ( state = STORE_DATA, action ) => {
	switch ( action.type ) {
	case 'SHOW_MODAL':
		return {
			...state,
			isOpen: true,
			message: action.message,
			cb: action.cb,
		};
	case 'HIDE_MODAL':
		return {
			...state,
			isOpen: false,
			message: false,
			cb: null,
		};
	case 'CONFIRM':
		state.cb &&
				setTimeout( () => {
					state.cb();
				}, 10 );

		return {
			...state,
			confirm: action.value,
		};
	}
	return state;
};

export default Modal;
