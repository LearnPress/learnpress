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
			data: { ...widget },
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

	if ( 'IntersectionObserver' in window ) {
		const eleObserver = new IntersectionObserver( ( entries, observer ) => {
			entries.forEach( ( entry ) => {
				if ( entry.isIntersecting ) {
					const ele = entry.target;

					getResponse( ele );

					eleObserver.unobserve( ele );
				}
			} );
		} );

		[ ...widgets ].map( ( ele ) => ele.classList.contains( 'learnpress-widget-wrapper__restapi' ) && eleObserver.observe( ele ) );
	}
}

document.addEventListener( 'DOMContentLoaded', function( event ) {
	widgetRestAPI();
} );
