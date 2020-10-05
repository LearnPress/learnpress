import extend from './extend';

const isDebugMode = function isDebugMode() {
	return !! window.LP_DEBUG;
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
