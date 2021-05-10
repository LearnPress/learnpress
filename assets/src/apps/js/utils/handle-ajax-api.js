const handleAjax = function( url, params, functions ) {
	wp.apiFetch( {
		path: url,
		method: 'POST',
		data: params,
	} ).then( ( res ) => {
		if ( 'function' === typeof functions.success ) {
			functions.success( res );
		}
	} ).catch( ( err ) => {
		if ( 'function' === typeof functions.error ) {
			functions.error( err );
		}
	} ).then( () => {
		if ( 'function' === typeof functions.completed ) {
			functions.completed();
		}
	} );
};

export default handleAjax;
