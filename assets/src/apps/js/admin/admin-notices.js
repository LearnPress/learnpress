let elLPAdminNotices;
let dataHtml = null;

const urlApiAdminNotices = lpGlobalSettings.rest + 'lp/v1/admin/tools/admin-notices';
fetch( urlApiAdminNotices, {
	method: 'GET',
} ).then( ( res ) =>
	res.json()
).then( ( res ) => {
	// console.log(data);
	const { status, message, data } = res;
	dataHtml = data.content;
} ).catch( ( err ) => {
	console.log( err );
} );

document.addEventListener( 'DOMContentLoaded', () => {
	elLPAdminNotices = document.querySelector( '.lp-admin-notices' );

	const interval = setInterval( () => {
		if ( dataHtml !== null ) {
			elLPAdminNotices.innerHTML = dataHtml;
			clearInterval( interval );
		}
	}, 1 );
} );
