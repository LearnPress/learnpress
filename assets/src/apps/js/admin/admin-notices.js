let elLPAdminNotices = null;
let elBtnDismiss;
let dataHtml = null;

const urlApiAdminNotices = lpGlobalSettings.rest + 'lp/v1/admin/tools/admin-notices';
const callAdminNotices = ( set ) => {
	fetch( urlApiAdminNotices + `?${ set }`, {
		method: 'GET',
	} ).then( ( res ) =>
		res.json()
	).then( ( res ) => {
		// console.log(data);

		const { status, message, data } = res;
		if ( status === 'success' ) {
			dataHtml = data.content;

			if ( dataHtml.length === 0 && elLPAdminNotices ) {
				elLPAdminNotices.style.display = 'none';
			}
		} else {
			dataHtml = message;
		}
	} ).catch( ( err ) => {
		console.log( err );
	} );
};
callAdminNotices();

/*** DOMContentLoaded ***/
document.addEventListener( 'DOMContentLoaded', () => {
	elLPAdminNotices = document.querySelector( '.lp-admin-notices' );
	elBtnDismiss = document.querySelector( '.btn-lp-notice-dismiss' );

	const interval = setInterval( () => {
		if ( dataHtml !== null ) {
			if ( dataHtml.length > 0 ) {
				elLPAdminNotices.innerHTML = dataHtml;
				elLPAdminNotices.style.display = 'block';
			}

			clearInterval( interval );
		}
	}, 1 );
} );

/*** Events ***/
document.addEventListener( 'click', ( e ) => {
	e.preventDefault();
	const el = e.target;

	if ( el.classList.contains( 'btn-lp-notice-dismiss' ) ) {
		const parent = el.closest( '.lp-admin-notice' );
		callAdminNotices( `dismiss=${ el.getAttribute( 'data' ) }` );
		parent.closest( '.lp-admin-notice' ).remove();
	}
} );
