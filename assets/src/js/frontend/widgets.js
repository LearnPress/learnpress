import { lpFetchAPI } from '../utils';
import API from '../api';

function widgetRestAPI() {
	const widgets = document.querySelectorAll( '.learnpress-widget-wrapper' );

	if ( ! widgets.length ) {
		return;
	}

	const getResponse = ( ele ) => {
		const widgetData = ele.dataset.widget ? JSON.parse( ele.dataset.widget ) : '';
		const url = API.frontend.apiWidgets;
		const paramsFetch = {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},
			body: JSON.stringify( { ...widgetData, ...{ params_url: lpData.urlParams } } ),
		};

		if ( 0 !== parseInt( lpData.user_id ) ) {
			paramsFetch.headers[ 'X-WP-Nonce' ] = lpData.nonce;
		}

		const callBack = {
			before: () => {

			},
			success: ( res ) => {
				const { data, status, message } = res;

				if ( data && status === 'success' ) {
					ele.insertAdjacentHTML( 'afterbegin', data );
				} else if ( message ) {
					ele.insertAdjacentHTML( 'afterbegin', `<div class="lp-ajax-message error" style="display:block">${ message }</div>` );
				}
			},
			error: ( error ) => {

			},
			completed: () => {
				//delete ele.dataset.widget;
				ele.querySelector( '.lp-skeleton-animation' ).remove();
			},
		};

		// Call API load widget
		lpFetchAPI( url, paramsFetch, callBack );
	};

	widgets.forEach( ( ele ) => {
		if ( ele.classList.contains( 'learnpress-widget-wrapper__restapi' ) ) {
			getResponse( ele );
		}
	} );
}

document.addEventListener( 'DOMContentLoaded', function( event ) {
	widgetRestAPI();
} );
