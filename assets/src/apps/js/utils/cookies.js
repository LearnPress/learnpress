const Cookies = {
	get: ( name, def, global ) => {
		let ret;

		if ( global ) {
			ret = wpCookies.get( name );
		} else {
			let ck = wpCookies.get( 'LP' );
			if ( ck ) {
				ck = JSON.parse( ck );
				ret = name ? ck[ name ] : ck;
			}
		}

		if ( ! ret && ret !== def ) {
			ret = def;
		}

		return ret;
	},

	set( name, value, expires, path, domain, secure ) {
		if ( arguments.length > 2 ) {
			wpCookies.set( name, value, expires, path, domain, secure );
		} else if ( arguments.length == 2 ) {
			let ck = wpCookies.get( 'LP' );

			if ( ck ) {
				ck = JSON.parse( ck );
			} else {
				ck = {};
			}

			ck[ name ] = value;

			wpCookies.set( 'LP', JSON.stringify( ck ), '', '/' );
		} else {
			wpCookies.set( 'LP', JSON.stringify( name ), '', '/' );
		}
	},

	remove( name ) {
		const allCookies = Cookies.get();
		const reg = new RegExp( name, 'g' );
		const newCookies = {};
		const useRegExp = name.match( /\*/ );

		for ( const i in allCookies ) {
			if ( useRegExp ) {
				if ( ! i.match( reg ) ) {
					newCookies[ i ] = allCookies[ i ];
				}
			} else if ( name != i ) {
				newCookies[ i ] = allCookies[ i ];
			}
		}

		Cookies.set( newCookies );
	},
};

export default Cookies;
