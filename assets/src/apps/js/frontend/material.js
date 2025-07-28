import * as Util from '../../../js/utils.js';
export default function lpMaterialsLoad() {
	document.addEventListener( 'click', function( e ) {
		const target = e.target;
		if ( target.classList.contains( 'lp-loadmore-material' ) ) {
			const loadMoreButton = target;
			const lpTarget = target.closest( '.lp-target' );
			const dataSend = window.lpAJAXG.getDataSetCurrent( lpTarget );
			dataSend.args.paged++;

			Util.lpSetLoadingEl( loadMoreButton, 1 );
			const callBack = {
				success: ( response ) => {
					const { status, message, data } = response;
					if ( status === 'success' ) {
						const tableBody = lpTarget.querySelector( 'table.course-material-table tbody' );
						tableBody.insertAdjacentHTML( 'beforeend', data.content );
						if ( data.paged === data.total_pages ) {
							loadMoreButton.remove();
						}
						window.lpAJAXG.setDataSetCurrent( lpTarget, dataSend );
					} else {
						console.error( message );
					}
				},
				error: ( error ) => {
					console.error( error );
				},
				completed: () => {
					Util.lpSetLoadingEl( loadMoreButton, 0 );
				},
			};
			window.lpAJAXG.fetchAJAX( dataSend, callBack );
		}
	} );
}
