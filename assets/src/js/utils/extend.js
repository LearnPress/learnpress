export default function() {
	window.LP = window.LP || {};

	if ( typeof arguments[ 0 ] === 'string' ) {
		LP[ arguments[ 0 ] ] = LP[ arguments[ 0 ] ] || {};
		LP[ arguments[ 0 ] ] = jQuery.extend( LP[ arguments[ 0 ] ], arguments[ 1 ] );
	} else {
		LP = jQuery.extend( LP, arguments[ 0 ] );
	}
}
