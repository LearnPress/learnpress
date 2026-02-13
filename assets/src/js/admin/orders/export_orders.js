import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify';

export const ExportOrdersToCSV = () => {
	const handleExport = ( args ) => {
		const { e, target } = args;
		e.preventDefault();
		lpUtils.lpSetLoadingEl( target, true );
		let dataSend = JSON.parse( target.dataset.send );

		const callBack = {
			success: ( response ) => {
				const { message, status, data } = response;

				if ( status === 'success' ) {
					const maxPage = data?.max_page;
					const nextPage = data?.next_page;
					const done = data?.done;
					const downloadUrl = data?.download_url;

					if ( !! maxPage && !! nextPage && nextPage <= maxPage ) {
						dataSend = { ...dataSend, paged: nextPage };
						window.lpAJAXG.fetchAJAX( dataSend, callBack );
					} else if ( !! done ) {
						lpToastify.show( message, 'success' );

						const link = document.createElement( 'a' );
						link.href = downloadUrl;
						link.target = '_self';
						document.body.appendChild( link );
						link.click();
						document.body.removeChild( link );
					}
				} else {
					throw message;
				}
			},
			error: ( error ) => {
				lpToastify.show( error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( target, false );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	};

	lpUtils.eventHandlers( 'click', [
		{
			selector: '.lp-btn-export-order-to-csv',
			class: this,
			callBack: handleExport,
		},
	] );
};
