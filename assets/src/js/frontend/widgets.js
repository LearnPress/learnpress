import { lpFetchAPI } from '../utils';
import API from '../api';

function widgetRestAPI() {
	const widgets = document.querySelectorAll( '.learnpress-widget-wrapper:not(.loaded)' );

	if ( ! widgets.length ) {
		return;
	}

	const getResponse = ( ele ) => {
		const lang = lpData.urlParams.lang ? `?lang=${ lpData.urlParams.lang }` : '';
		const widgetData = ele.dataset.widget ? JSON.parse( ele.dataset.widget ) : '';
		const url = API.frontend.apiWidgets + lang;
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
				const elSkeleton = ele.querySelector( '.lp-skeleton-animation' );
				if ( elSkeleton ) {
					elSkeleton.remove();
				}
			},
		};

		// Call API load widget
		lpFetchAPI( url, paramsFetch, callBack );
	};

	widgets.forEach( ( ele ) => {
		ele.classList.add( 'loaded' );
		if ( ele.classList.contains( 'learnpress-widget-wrapper__restapi' ) ) {
			getResponse( ele );
		}
	} );
}

widgetRestAPI();

// Case 2: readystatechange, find all elements with the class '.lp-load-ajax-element' not have class 'loaded'
document.addEventListener( 'readystatechange', ( event ) => {
	widgetRestAPI();
} );

// Case 3: DOMContentLoaded, find all elements with the class '.lp-load-ajax-element' not have class 'loaded'
document.addEventListener( 'DOMContentLoaded', () => {
	widgetRestAPI();
} );
