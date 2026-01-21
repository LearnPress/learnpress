import * as lpUtils from './../../utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify';

const exportOrders = () => {
	const handleExport = ( args ) => {
		const { e, target } = args;
		e.preventDefault();
		lpUtils.lpSetLoadingEl( target, true );
		let dataSend = JSON.parse( target.dataset.send );

		const callBack = {
			success: ( response ) => {
				const { message, status, data } = response;
				lpToastify.show( message, status );

				if ( status === 'success' ) {
					const maxPage = data?.max_page;
					const nextPage = data?.next_page;
					const done = data?.done;
					const downloadUrl = data?.download_url;

					if ( !! maxPage && !! nextPage && nextPage <= maxPage ) {
						dataSend = { ...dataSend, paged: nextPage };
						window.lpAJAXG.fetchAJAX( dataSend, callBack );
					} else if ( !! done ) {
						const link = document.createElement( 'a' );
						link.href = downloadUrl;
						link.target = '_self';
						document.body.appendChild( link );
						link.click();
						document.body.removeChild( link );
					}
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
			selector: 'button.export',
			class: this,
			callBack: handleExport,
		},
	] );
};

export default exportOrders;
