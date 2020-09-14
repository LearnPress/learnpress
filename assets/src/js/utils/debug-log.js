import extend from './extend';

const isDebugMode = function isDebugMode() {
	return !! window.LP_DEBUG;
	// var uri = window.location.href;
	//
	// if (uri.match(/debug=true/)) {
	//     return true;
	// }
	//
	// if (window['LP_DEBUG'] !== undefined) {
	//     return !!LP_DEBUG;
	// }
	//
	// return !!window.location.href.match(/localhost/);
};

const log = function log() {
	if ( ! isDebugMode() ) {
		return;
	}

	console.log.apply( null, arguments );
};

const _export = { isDebugMode, log };

extend( 'Utils', _export );
export default _export;
