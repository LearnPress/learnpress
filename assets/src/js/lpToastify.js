/**
 * Utils functions
 *
 * @param url
 * @param data
 * @param functions
 * @since 4.3.0
 * @version 1.0.0
 */
import Toastify from 'toastify-js';
import 'toastify-js/src/toastify.css';

const argsToastify = {
	text: '',
	gravity: lpData.toast.gravity, // `top` or `bottom`
	position: lpData.toast.position, // `left`, `center` or `right`
	className: `${ lpData.toast.classPrefix }`,
	close: lpData.toast.close == 1,
	stopOnFocus: lpData.toast.stopOnFocus == 1,
	duration: lpData.toast.duration,
};
export const show = ( message, status = 'success', argsCustom ) => {
	let args = argsToastify;
	if ( argsCustom ) {
		args = { ...args, ...argsCustom };
	}

	const toastify = new Toastify( {
		...args,
		text: message,
		className: `${ lpData.toast.classPrefix } ${ status }`,
	} );
	toastify.showToast();
};
