import { addQueryArgs } from '@wordpress/url';

function widgetRestAPI() {
	const widgets = document.querySelectorAll( '.learnpress-widget-wrapper' );

	if ( ! widgets.length ) {
		return;
	}

	const getResponse = async ( ele ) => {
		const widget = ele.dataset.widget ? JSON.parse( ele.dataset.widget ) : '';

		const response = await wp.apiFetch( {
			path: 'lp/v1/widgets/api',
			method: 'POST',
			data: { ...widget, ...{ params_url: lpGlobalSettings.lpArchiveSkeleton } },
		} );

		const { data, status, message } = response;

		if ( data && status === 'success' ) {
			ele.insertAdjacentHTML( 'afterbegin', data );
		} else if ( message ) {
			ele.insertAdjacentHTML( 'afterbegin', `<div class="lp-ajax-message error" style="display:block">${ message }</div>` );
		}

		delete ele.dataset.widget;

		ele.querySelector( '.lp-skeleton-animation' ).remove();
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
