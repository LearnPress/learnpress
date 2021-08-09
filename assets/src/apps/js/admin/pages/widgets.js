const $ = jQuery;

import { debounce } from 'lodash';

function autocomplete() {
	const elements = document.querySelectorAll( '.lp_widget_autocomplete_field' );

	const getResponse = async ( ele, value ) => {
		const postType = ele.dataset.type || 'posts';

		const response = await wp.apiFetch( {
			path: wp.url.addQueryArgs( `wp/v2/${ postType }`, { search: value } ),
			method: 'GET',
		} );

		[ ...document.querySelectorAll( '.lp_widget_autocomplete__select' ) ].map( ( ele ) => ele.remove() );

		const output = [];

		response.forEach( ( item ) => {
			output.push( `<div class="lp_widget_autocomplete__item" style="padding: 7px 10px; border: 1px solid #949494; border-bottom-color: #afafaf; border-top: none; cursor: pointer;">${ item.title.rendered }</div>` );
		} );

		ele.insertAdjacentHTML( 'afterend', `<div class="lp_widget_autocomplete__select" style="max-height: 160px; overflow: auto; background: #eee; left: 0; width: 100%; font-size: 13px;">${ output.join( '' ) }</div>` );
	};

	if ( elements && elements.length > 0 ) {
		elements.forEach( ( ele ) => {
			ele.addEventListener( 'keyup', debounce( ( e ) => {
				const value = e.target.value;

				if ( value.length > 2 ) {
					getResponse( ele, value );
				} else {
					ele.querySelector( '.lp_widget_autocomplete__select' )?.remove();
				}
			}, 300 ) );
		} );
	}
}

document.addEventListener( 'DOMContentLoaded', function( event ) {
	let widgets = null;

	$( document ).on( 'widget-added', function( event, widget ) {
		if ( ! widgets ) {
			autocomplete();
		}
		widgets = widget;
	} );
} );
