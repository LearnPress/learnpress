/**
 * Course settings tab "More Features": recommended add-ons with Dismiss / Reset.
 *
 * Server renders every candidate add-on (`.lp-more-features__item[data-slug]`);
 * this module keeps at most `data-max` visible, hides dismissed ones and stores
 * them in localStorage as `{ slug: timestamp }` for `data-ttl-days` days.
 * Shared by wp-admin (edit-course.js) and Course Builder (course-builder.js).
 *
 * @since 4.4.15
 * @version 1.0.0
 */

const DAY_MS = 24 * 60 * 60 * 1000;
const ANIMATION_MS = 250;
const SELECTOR = {
	root: '.lp-more-features',
	item: '.lp-more-features__item',
	empty: '.lp-more-features__empty',
	reset: '.lp-more-features__reset',
	resetLink: '.lp-more-features__reset-link',
	dismiss: '.lp-more-features__dismiss',
};

/**
 * Parse stored value and drop invalid or expired entries.
 *
 * @param {string|null} raw   Raw localStorage value.
 * @param {number}      now   Timestamp (ms).
 * @param {number}      ttlMs Time to live (ms).
 * @return {Object} Map slug => timestamp still valid.
 */
export const parseDismissed = ( raw, now, ttlMs ) => {
	let data;
	try {
		data = JSON.parse( raw );
	} catch ( e ) {
		return {};
	}

	if ( ! data || typeof data !== 'object' || Array.isArray( data ) ) {
		return {};
	}

	const result = {};
	Object.keys( data ).forEach( ( slug ) => {
		const time = data[ slug ];
		if ( typeof time === 'number' && Number.isFinite( time ) && now - time < ttlMs ) {
			result[ slug ] = time;
		}
	} );

	return result;
};

/**
 * Slugs to show: not dismissed, keeping order, up to max.
 *
 * @param {string[]} slugs     All slugs in display order.
 * @param {Object}   dismissed Map slug => timestamp.
 * @param {number}   max       Max items.
 * @return {string[]}
 */
export const pickVisible = ( slugs, dismissed, max ) => {
	return slugs.filter( ( slug ) => ! Object.prototype.hasOwnProperty.call( dismissed, slug ) ).slice( 0, max );
};

export class MoreFeatures {
	constructor( root, options = {} ) {
		this.root = root;
		this.max = parseInt( root.dataset.max, 10 ) || 5;
		this.ttlMs = ( parseInt( root.dataset.ttlDays, 10 ) || 30 ) * DAY_MS;
		this.storageKey = root.dataset.storageKey || 'lp_more_features_dismissed';
		this.delay = options.delay ?? ANIMATION_MS;
		this.now = options.now || ( () => Date.now() );
		this.memory = {}; // Fallback when localStorage is unavailable.
		this.dismissed = {};
	}

	init() {
		this.dismissed = this.load();
		this.root.addEventListener( 'click', ( e ) => this.onClick( e ) );
		this.render( false );
	}

	load() {
		const now = this.now();
		let raw = null;
		try {
			raw = window.localStorage.getItem( this.storageKey );
		} catch ( e ) {
			return parseDismissed( JSON.stringify( this.memory ), now, this.ttlMs );
		}

		const valid = parseDismissed( raw, now, this.ttlMs );
		// Persist the cleaned list so expired entries are removed.
		if ( raw !== null && JSON.stringify( valid ) !== raw ) {
			this.save( valid );
		}

		return valid;
	}

	save( data ) {
		this.memory = data;
		try {
			if ( Object.keys( data ).length ) {
				window.localStorage.setItem( this.storageKey, JSON.stringify( data ) );
			} else {
				window.localStorage.removeItem( this.storageKey );
			}
		} catch ( e ) {
			// Ignore: dismissal then only lasts for this page load.
		}
	}

	items() {
		return Array.from( this.root.querySelectorAll( SELECTOR.item ) );
	}

	onClick( e ) {
		const dismissBtn = e.target.closest( SELECTOR.dismiss );
		if ( dismissBtn && this.root.contains( dismissBtn ) ) {
			e.preventDefault();
			this.dismiss( dismissBtn.closest( SELECTOR.item ) );
			return;
		}

		if ( e.target.closest( SELECTOR.reset ) || e.target.closest( SELECTOR.resetLink ) ) {
			e.preventDefault();
			this.reset();
		}
	}

	dismiss( item ) {
		if ( ! item || item.classList.contains( 'is-removing' ) ) {
			return;
		}

		this.dismissed[ item.dataset.slug ] = this.now();
		this.save( this.dismissed );

		// Slide-up the dismissed item while the next one fades in.
		item.classList.add( 'is-removing' );
		this.render( true );
		setTimeout( () => {
			item.hidden = true;
			item.classList.remove( 'is-removing' );
			this.updateChrome();
		}, this.delay );
	}

	reset() {
		this.dismissed = {};
		this.save( {} );
		this.render( true );
	}

	render( animate ) {
		const items = this.items();
		const visible = pickVisible(
			items.map( ( item ) => item.dataset.slug ),
			this.dismissed,
			this.max
		);

		items.forEach( ( item ) => {
			if ( item.classList.contains( 'is-removing' ) ) {
				return;
			}

			const show = visible.includes( item.dataset.slug );
			if ( show && item.hidden ) {
				item.hidden = false;
				if ( animate ) {
					item.classList.add( 'is-entering' );
					setTimeout( () => item.classList.remove( 'is-entering' ), this.delay + 50 );
				}
			} else if ( ! show && ! item.hidden ) {
				item.hidden = true;
			}
		} );

		this.updateChrome();
	}

	/**
	 * Empty message and Reset button follow the current state.
	 */
	updateChrome() {
		const items = this.items();
		const shown = items.filter( ( item ) => ! item.hidden && ! item.classList.contains( 'is-removing' ) );
		const hasDismissed = items.some( ( item ) =>
			Object.prototype.hasOwnProperty.call( this.dismissed, item.dataset.slug )
		);

		const empty = this.root.querySelector( SELECTOR.empty );
		if ( empty ) {
			empty.hidden = ! ( items.length > 0 && shown.length === 0 );
		}

		const reset = this.root.querySelector( SELECTOR.reset );
		if ( reset ) {
			reset.hidden = ! hasDismissed;
		}
	}
}

/**
 * Init every not-yet-initialized block (safe to call repeatedly).
 *
 * @param {ParentNode} scope
 */
export const initMoreFeatures = ( scope = document ) => {
	scope.querySelectorAll( `${ SELECTOR.root }:not([data-lp-init])` ).forEach( ( root ) => {
		root.dataset.lpInit = '1';
		new MoreFeatures( root ).init();
	} );
};

/**
 * Init now and for blocks inserted later (Course Builder loads tabs via AJAX).
 */
export const bootMoreFeatures = () => {
	const start = () => {
		initMoreFeatures();
		if ( typeof MutationObserver === 'undefined' || ! document.body ) {
			return;
		}

		new MutationObserver( () => {
			if ( document.querySelector( `${ SELECTOR.root }:not([data-lp-init])` ) ) {
				initMoreFeatures();
			}
		} ).observe( document.body, { childList: true, subtree: true } );
	};

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
};
