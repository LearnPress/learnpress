/**
 * Auto prepend `LP` prefix for jQuery fn plugin name.
 *
 * Create : $.fn.LP( 'PLUGIN_NAME', func) <=> $.fn.LP_PLUGIN_NAME
 * Usage: $(selector).LP('PLUGIN_NAME') <=> $(selector).LP_PLUGIN_NAME()
 *
 * @version 3.2.6
 */

const $ = window.jQuery;
let exp;

( function() {
	if ( $ === undefined ) {
		return;
	}

	$.fn.LP = exp = function( widget, fn ) {
		if ( typeof fn === 'function' ) {
			$.fn[ 'LP_' + widget ] = fn;
		} else if ( widget ) {
			const args = [];
			if ( arguments.length > 1 ) {
				for ( let i = 1; i < arguments.length; i++ ) {
					args.push( arguments[ i ] );
				}
			}

			return typeof ( $( this )[ 'LP_' + widget ] ) === 'function' ? $( this )[ 'LP_' + widget ].apply( this, args ) : this;
		}
		return this;
	};
}() );

export default exp;
