import * as lpUtils from 'lpAssetsJsPath/utils.js';

class LearningContentBar {
	static eventRendered = 'lp-learning-content-bar-rendered';
	static eventClosed = 'lp-learning-content-bar-closed';

	constructor() {
		this.items = new Map();
	}

	static selectors = {
		contentBar: '.lp-addon-content-bar',
		rightSidebarItem: '.popup-right-sidebar__item',
		close: '.lp-addon-content-bar__head .lp-icon-close',
	};

	static _loadedEvents = false;

	init() {
		this.events();
	}

	events() {
		if ( LearningContentBar._loadedEvents ) {
			return;
		}
		LearningContentBar._loadedEvents = true;

		document.addEventListener( 'lp-learning-content-bar-open', ( e ) => {
			const item = document.querySelector(
				`[data-learning-bar-item="${ CSS.escape( e.detail?.item || '' ) }"]`
			);
			if ( item ) {
				this.open( { target: item } );
			}
		} );
		document.addEventListener( 'keydown', ( e ) => {
			if ( e.key === 'Escape' ) {
				this.close();
			} else if ( ( e.key === 'Enter' || e.key === ' ' ) && e.target.matches( LearningContentBar.selectors.rightSidebarItem ) ) {
				e.preventDefault();
				this.open( { target: e.target } );
			}
		} );

		lpUtils.eventHandlers( 'click', [
			{
				selector: LearningContentBar.selectors.rightSidebarItem,
				callBack: this.open.bind( this ),
			},
			{
				selector: LearningContentBar.selectors.close,
				callBack: this.close.bind( this ),
			},
		] );
	}

	open( args ) {
		const { target } = args;
		const elItem = target.closest(
			`${ LearningContentBar.selectors.rightSidebarItem }`
		);
		if ( ! elItem ) {
			return;
		}

		const contentBar = document.querySelector(
			LearningContentBar.selectors.contentBar
		);
		if ( ! contentBar ) {
			return;
		}

		const template = elItem.querySelector( 'template' );
		const headTarget = contentBar.querySelector(
			'.lp-addon-content-bar__left'
		);
		const contentTarget = contentBar.querySelector(
			'.lp-addon-content-bar__content'
		);

		if ( template && headTarget && contentTarget ) {
			const item = elItem.dataset.learningBarItem || '';
			if ( ! this.items.has( item ) ) {
				const head = template.content.querySelector( '.lp-learning-bar-item-head' );
				const content = template.content.querySelector( '.lp-learning-bar-item-content' );
				if ( ! head || ! content ) {
					return;
				}
				this.items.set( item, {
					head: head.cloneNode( true ),
					content: content.cloneNode( true ),
				} );
			}

			// Keep each tool's DOM so drafts and requests survive switching tools.
			const cached = this.items.get( item );
			if ( contentTarget.firstElementChild !== cached.content ) {
				headTarget.replaceChildren( cached.head );
				contentTarget.replaceChildren( cached.content );
			}
			contentBar.dataset.learningBarItem = item;
			contentBar.classList.add( 'open' );
			const sidebar = elItem.closest( '#popup-right-sidebar' );
			sidebar?.classList.remove( 'is-collapsed' );
			sidebar?.classList.add( 'is-expanded' );
			document.querySelectorAll( LearningContentBar.selectors.rightSidebarItem ).forEach( ( element ) => {
				element.setAttribute( 'aria-expanded', String( element === elItem ) );
			} );

			document.dispatchEvent(
				new CustomEvent( LearningContentBar.eventRendered, {
					detail: {
						item,
						contentBar,
					},
				} )
			);
		}
	}

	close( args ) {
		args?.e?.preventDefault();

		const contentBar = document.querySelector(
			LearningContentBar.selectors.contentBar
		);

		if ( contentBar?.classList.contains( 'open' ) ) {
			contentBar.classList.remove( 'open' );
			const item = document.querySelector( `${ LearningContentBar.selectors.rightSidebarItem }[aria-expanded="true"]` );
			item?.setAttribute( 'aria-expanded', 'false' );
			item?.focus();
			document.dispatchEvent( new CustomEvent( LearningContentBar.eventClosed, {
				detail: { item: contentBar.dataset.learningBarItem || '', contentBar },
			} ) );
		}
	}
}

const learningContentBar = new LearningContentBar();
learningContentBar.init();
