/**
 * Manage event callbacks.
Allow add/remove a callback function into custom event of an object.
 *
 * @class
 * @param self
 */
const Event_Callback = function Event_Callback( self ) {
	const callbacks = {};
	const $ = window.jQuery;

	this.on = function( event, callback ) {
		let namespaces = event.split( '.' ),
			namespace = '';

		if ( namespaces.length > 1 ) {
			event = namespaces[ 0 ];
			namespace = namespaces[ 1 ];
		}

		if ( ! callbacks[ event ] ) {
			callbacks[ event ] = [ [], {} ];
		}

		if ( namespace ) {
			if ( ! callbacks[ event ][ 1 ][ namespace ] ) {
				callbacks[ event ][ 1 ][ namespace ] = [];
			}
			callbacks[ event ][ 1 ][ namespace ].push( callback );
		} else {
			callbacks[ event ][ 0 ].push( callback );
		}

		return self;
	};

	this.off = function( event, callback ) {
		let namespaces = event.split( '.' ),
			namespace = '';

		if ( namespaces.length > 1 ) {
			event = namespaces[ 0 ];
			namespace = namespaces[ 1 ];
		}

		if ( ! callbacks[ event ] ) {
			return self;
		}
		let at = -1;
		if ( ! namespace ) {
			if ( typeof callback === 'function' ) {
				at = callbacks[ event ][ 0 ].indexOf( callback );
				if ( at < 0 ) {
					return self;
				}
				callbacks[ event ][ 0 ].splice( at, 1 );
			} else {
				callbacks[ event ][ 0 ] = [];
			}
		} else {
			if ( ! callbacks[ event ][ 1 ][ namespace ] ) {
				return self;
			}

			if ( typeof callback === 'function' ) {
				at = callbacks[ event ][ 1 ][ namespace ].indexOf( callback );
				if ( at < 0 ) {
					return self;
				}
				callbacks[ event ][ 1 ][ namespace ].splice( at, 1 );
			} else {
				callbacks[ event ][ 1 ][ namespace ] = [];
			}
		}

		return self;
	};

	this.callEvent = function( event, callbackArgs ) {
		if ( ! callbacks[ event ] ) {
			return;
		}

		if ( callbacks[ event ][ 0 ] ) {
			for ( var i = 0; i < callbacks[ event ][ 0 ].length; i++ ) {
				typeof ( callbacks[ event ][ 0 ][ i ] ) === 'function' && callbacks[ event ][ i ][ 0 ].apply( self, callbackArgs );
			}
		}

		if ( callbacks[ event ][ 1 ] ) {
			for ( var i in callbacks[ event ][ 1 ] ) {
				for ( let j = 0; j < callbacks[ event ][ 1 ][ i ].length; j++ ) {
					typeof ( callbacks[ event ][ 1 ][ i ][ j ] ) === 'function' && callbacks[ event ][ 1 ][ i ][ j ].apply( self, callbackArgs );
				}
			}
		}
	};
};

export default Event_Callback;
