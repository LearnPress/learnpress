import * as lpUtils from 'lpAssetsJsPath/utils.js';

/**
 * LearnPress themes handler.
 *
 * @since 4.4.7
 * @version 1.0.0
 */
export class LPThemes {
	/**
	 * Themes container selectors.
	 */
	static selectors = {
		container: '.learn-press-themes',
		elListThemes: '.lp-themes-grid',
		filter: '.lp-themes-filter__item',
		count: '.lp-themes-filter__count',
		search: '.lp-themes-search__input',
		card: '.lp-theme-card',
		title: '.lp-theme-card__title',
		description: '.lp-theme-card__description',
	};

	/**
	 * Initialize the themes handler.
	 *
	 * @param {HTMLElement} container Themes container.
	 */
	init( container ) {
		this.container = container;
		this.events();
		this.countThemes();
	}

	/**
	 * Register theme filter and search events.
	 */
	events() {
		if ( LPThemes._loadedEvents ) {
			return;
		}
		LPThemes._loadedEvents = true;

		lpUtils.eventHandlers( 'click', [
			{
				selector: LPThemes.selectors.filter,
				callBack: this.filterByCategory.name,
				class: this,
			},
		] );

		lpUtils.eventHandlers( 'input', [
			{
				selector: LPThemes.selectors.search,
				callBack: this.filterThemes.name,
				class: this,
			},
		] );
	}

	/**
	 * Count themes and update each category count.
	 */
	countThemes() {
		const container = this.container;
		const cards = container.querySelectorAll( LPThemes.selectors.card );
		const counts = { all: cards.length };

		cards.forEach( ( card ) => {
			const key = card.getAttribute( 'data-category' );
			counts[ key ] = ( counts[ key ] || 0 ) + 1;
		} );

		container
			.querySelectorAll( LPThemes.selectors.filter )
			.forEach( ( item ) => {
				const count = item.querySelector( LPThemes.selectors.count );
				const text = `(${
					counts[ item.getAttribute( 'data-category' ) ] || 0
				})`;
				if ( count && count.textContent !== text ) {
					count.textContent = text;
				}
			} );
	}

	/**
	 * Activate the selected category and filter themes.
	 *
	 * @param {Object} args Event arguments.
	 * @param {Event} args.e Browser event.
	 * @param {Element} args.target Event target.
	 */
	filterByCategory( { e, target } ) {
		const filter = target.closest( LPThemes.selectors.filter );
		const container = filter.closest( LPThemes.selectors.container );

		if ( ! container ) {
			return;
		}

		e.preventDefault();
		container
			.querySelectorAll( LPThemes.selectors.filter )
			.forEach( ( item ) => {
				const isActive = item === filter;
				item.classList.toggle( 'active', isActive );
				item.setAttribute(
					'aria-pressed',
					isActive ? 'true' : 'false'
				);
			} );

		this.filterThemes();
	}

	/**
	 * Filter themes by the active category and search terms.
	 */
	filterThemes() {
		const container = this.container;
		const search = container.querySelector( LPThemes.selectors.search );
		const query = search ? search.value.trim().toLowerCase() : '';
		const searchTerms = query.split( /\s+/ ).filter( Boolean );
		const active = container.querySelector(
			`${ LPThemes.selectors.filter }.active`
		);
		const category = active
			? active.getAttribute( 'data-category' )
			: 'all';
		const cards = container.querySelectorAll( LPThemes.selectors.card );

		cards.forEach( ( card ) => {
			const title = card.querySelector( LPThemes.selectors.title );
			const description = card.querySelector(
				LPThemes.selectors.description
			);
			const text = `${ title ? title.textContent : '' } ${
				description ? description.textContent : ''
			}`.toLowerCase();
			card.hidden =
				( 'all' !== category &&
					category !== card.getAttribute( 'data-category' ) ) ||
				! searchTerms.every( ( term ) => text.includes( term ) );
		} );
	}
}

const lpThemes = new LPThemes();
lpUtils.lpOnElementReady( LPThemes.selectors.elListThemes, ( elListThemes ) => {
	const container = elListThemes.closest( LPThemes.selectors.container );
	lpThemes.init( container );
} );
