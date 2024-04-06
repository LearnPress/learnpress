/**
 * Fetch API.
 *
 * @param url
 * @param data
 * @param functions
 * @since 4.2.5.1
 * @version 1.0.1
 */
const lpFetchAPI = ( url, data = {}, functions = {} ) => {
	if ( 'function' === typeof functions.before ) {
		functions.before();
	}

	fetch( url, { method: 'GET', ...data } )
		.then( ( response ) => response.json() )
		.then( ( response ) => {
			if ( 'function' === typeof functions.success ) {
				functions.success( response );
			}
		} ).catch( ( err ) => {
			if ( 'function' === typeof functions.error ) {
				functions.error( err );
			}
		} )
		.finally( () => {
			if ( 'function' === typeof functions.completed ) {
				functions.completed();
			}
		} );
};

/**
 * Get current URL without params.
 *
 * @since 4.2.5.1
 */
const lpGetCurrentURLNoParam = () => {
	let currentUrl = window.location.href;
	const hasParams = currentUrl.includes( '?' );
	if ( hasParams ) {
		currentUrl = currentUrl.split( '?' )[ 0 ];
	}

	return currentUrl;
};

const lpAddQueryArgs = ( endpoint, args ) => {
	const url = new URL( endpoint );

	Object.keys( args ).forEach( ( arg ) => {
		url.searchParams.set( arg, args[ arg ] );
	} );

	return url;
};

/**
 * Listen element viewed.
 *
 * @param el
 * @param callback
 * @since 4.2.5.8
 */
const listenElementViewed = ( el, callback ) => {
	const observerSeeItem = new IntersectionObserver( function( entries ) {
		for ( const entry of entries ) {
			if ( entry.isIntersecting ) {
				callback( entry );
			}
		}
	} );

	observerSeeItem.observe( el );
};

/**
 * Listen element created.
 *
 * @param callback
 * @since 4.2.5.8
 */
const listenElementCreated = ( callback ) => {
	const observerCreateItem = new MutationObserver( function( mutations ) {
		mutations.forEach( function( mutation ) {
			if ( mutation.addedNodes ) {
				mutation.addedNodes.forEach( function( node ) {
					if ( node.nodeType === 1 ) {
						callback( node );
					}
				} );
			}
		} );
	} );

	observerCreateItem.observe( document, { childList: true, subtree: true } );
	// End.
};

export { lpFetchAPI, lpAddQueryArgs, lpGetCurrentURLNoParam, listenElementViewed, listenElementCreated };
