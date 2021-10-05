document.addEventListener( 'DOMContentLoaded', function( event ) {
	const elements = document.querySelector( 'ul.lp_append_data' );

	const getResponse = async ( ele ) => {
		try {
			const response = await wp.apiFetch( {
				path: wp.url.addQueryArgs( 'lp/v1/orders/statistic' ),
				method: 'GET',
			} );

			if ( response.status === 'success' && response.data ) {
				ele.innerHTML = response.data;
			} else {
				ele.innerHTML = `<div class="lp-ajax-message error" style="display:block">${ response.message &&
				response.message }</div>`;
			}
		} catch ( error ) {
			ele.innerHTML += `<div class="lp-ajax-message error" style="display:block">${ error.message &&
			error.message }</div>`;
		}
	};

	getResponse( elements );
} );
