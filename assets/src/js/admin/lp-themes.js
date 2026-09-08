(function() {
	'use strict';

	document.addEventListener( 'click', function( event ) {
		if ( ! ( event.target instanceof Element ) ) {
			return;
		}

		const filter = event.target.closest( '.lp-themes-filter__item' );

		if ( ! filter ) {
			return;
		}

		const container = filter.closest( '.learn-press-themes' );

		if ( ! container ) {
			return;
		}

		event.preventDefault();

		const filters = container.querySelectorAll(
			'.lp-themes-filter__item'
		);


		filters.forEach( function( item ) {
			const isActive = item === filter;

			item.classList.toggle( 'active', isActive );
			item.setAttribute(
				'aria-pressed',
				isActive ? 'true' : 'false'
			);
		} );

		filterThemes( container );
	} );

	function filterThemes( container ) {
		const search = container.querySelector( '.lp-themes-search__input' );
		const query = search ? search.value.trim().toLowerCase() : '';
		const active = container.querySelector( '.lp-themes-filter__item.active' );
		const category = active ? active.getAttribute( 'data-category' ) : 'all';
		const cards = container.querySelectorAll( '.lp-theme-card' );
		const counts = { all: cards.length };
		cards.forEach( function( card ) {
			const key = card.getAttribute( 'data-category' );
			counts[ key ] = ( counts[ key ] || 0 ) + 1;
		} );
		container.querySelectorAll( '.lp-themes-filter__item' ).forEach( function( item ) {
			const count = item.querySelector( '.lp-themes-filter__count' );
			const text = '(' + ( counts[ item.getAttribute( 'data-category' ) ] || 0 ) + ')';
			if ( count && count.textContent !== text ) {
				count.textContent = text;
			}
		} );

		let visible = 0;

		cards.forEach( function( card ) {
			const title = card.querySelector( '.lp-theme-card__title' );
			const description = card.querySelector( '.lp-theme-card__description' );
			const text = ( ( title ? title.textContent : '' ) + ' ' +
				( description ? description.textContent : '' ) ).toLowerCase();
			card.hidden = ( 'all' !== category && category !== card.getAttribute( 'data-category' ) ) ||
				! text.includes( query );
			if ( ! card.hidden ) {
				visible++;
			}
		} );

		const empty = container.querySelector( '.lp-themes-empty' );
		if ( empty ) {
			empty.hidden = ! cards.length || visible > 0;
		}
	}

	document.addEventListener( 'input', function( event ) {
		if ( event.target instanceof Element && event.target.matches( '.lp-themes-search__input' ) ) {
			const container = event.target.closest( '.learn-press-themes' );
			if ( container ) {
				filterThemes( container );
			}
		}
	} );

	function init() {
		document.querySelectorAll( '.learn-press-themes' ).forEach( function( container ) {
			filterThemes( container );
			new MutationObserver( function() {
				filterThemes( container );
			} ).observe( container, { childList: true, subtree: true } );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
})();
