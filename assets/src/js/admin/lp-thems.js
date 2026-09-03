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

		const category = (
			filter.getAttribute( 'data-category' ) || 'all'
		).toLowerCase();

		const filters = container.querySelectorAll(
			'.lp-themes-filter__item'
		);

		const cards = container.querySelectorAll(
			'.lp-theme-card'
		);

		filters.forEach( function( item ) {
			const isActive = item === filter;

			item.classList.toggle( 'active', isActive );
			item.setAttribute(
				'aria-pressed',
				isActive ? 'true' : 'false'
			);
		} );

		cards.forEach( function( card ) {
			const cardCategory = (
				card.getAttribute( 'data-category' ) || ''
			).toLowerCase();

			card.hidden =
				'all' !== category &&
				category !== cardCategory;
		} );
	} );
})();