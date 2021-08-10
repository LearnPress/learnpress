const $ = jQuery;

import { debounce } from 'lodash';

function autocompleteWidget( widget ) {
	const getResponse = async ( ele, value ) => {
		const postType = ele.data( 'type' ) || 'posts';

		ele.addClass( 'loading' );

		if ( ! ele.parent().find( '.lp_widget_autocomplete__loading' ).length ) {
			ele.after( '<p class="lp_widget_autocomplete__loading">Loading...</p>' );
		}

		$( ele ).parent().find( '.lp_widget_autocomplete__select' ).remove();

		const response = await wp.apiFetch( {
			path: wp.url.addQueryArgs( `wp/v2/${ postType }`, { search: value } ),
			method: 'GET',
		} );

		ele.parent().find( '.lp_widget_autocomplete__loading' ).remove();

		ele.removeClass( 'loading' );

		$( ele ).parent().find( '.lp_widget_autocomplete__select' ).remove();

		ele.after( '<div class="lp_widget_autocomplete__select"></div>' );

		if ( response.length > 0 ) {
			response.forEach( ( item ) => {
				$( '.lp_widget_autocomplete__select' ).append( `<div class="lp_widget_autocomplete__item" data-id="${ item.id }">${ item.title.rendered }</div>` );
			} );
		} else {
			$( '.lp_widget_autocomplete__select' ).append( '<p>No items found!</p>' );
		}

		$( document ).on( 'click', function() {
			[ ...document.querySelectorAll( '.lp_widget_autocomplete__select' ) ].map( ( ele ) => ele.style.display = 'none' );
		} );

		ele.on( 'click', ( e ) => {
			e.stopPropagation();
			ele.parent().find( '.lp_widget_autocomplete__select' ).css( 'display', 'block' );
		} );

		const elses = ele.parent().find( '.lp_widget_autocomplete__item' );
		elses.each( function( item ) {
			$( elses[ item ] ).on( 'click', function( e ) {
				e.preventDefault();

				const id = $( elses[ item ] ).data( 'id' );

				ele.parent().find( '.lp_widget_autocomplete_field__value' ).val( id );

				ele.val( $( elses[ item ] ).text() || '' );
			} );
		} );
	};

	if ( $( widget ).find( '.lp_widget_autocomplete_field' ).length > 0 ) {
		const ele = $( widget ).find( '.lp_widget_autocomplete_field' );

		$( ele ).on( 'keyup', debounce( ( e ) => {
			const value = e.target.value;

			if ( value.length > 2 ) {
				getResponse( $( ele ), value );
			} else {
				$( widget ).find( '.lp_widget_autocomplete__select' )?.remove();
			}
		}, 300 ) );
	}
}

document.addEventListener( 'DOMContentLoaded', function( event ) {
	if ( document.querySelector( '#widgets-editor' ) ) {
		$( document ).on( 'widget-added', function( event, widget ) {
			autocompleteWidget( widget );
		} );
	} else {
		const widgets = $( document ).find( '#widgets-right .widget' );

		if ( widgets.length > 0 ) {
			widgets.each( function( widget ) {
				autocompleteWidget( widget );
			} );
		}
	}
} );
