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

export { lpFetchAPI, lpAddQueryArgs, lpGetCurrentURLNoParam };
