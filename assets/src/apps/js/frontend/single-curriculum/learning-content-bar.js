import * as lpUtils from 'lpAssetsJsPath/utils.js';

class LearningContentBar {
	static eventRendered = 'lp-learning-content-bar-rendered';

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
		const { e, target } = args;
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

		contentBar.classList.add( 'open' );

		const template = elItem.querySelector( 'template' );
		const headTarget = contentBar.querySelector(
			'.lp-addon-content-bar__left'
		);
		const contentTarget = contentBar.querySelector(
			'.lp-addon-content-bar__content'
		);

		if ( template && headTarget && contentTarget ) {
			const head = template.content.querySelector(
				'.lp-learning-bar-item-head'
			);
			const content = template.content.querySelector(
				'.lp-learning-bar-item-content'
			);

			if ( head ) {
				headTarget.innerHTML = '';
				headTarget.appendChild( head.cloneNode( true ) );
			}

			if ( content ) {
				contentTarget.innerHTML = '';
				contentTarget.appendChild( content.cloneNode( true ) );
			}

			document.dispatchEvent(
				new CustomEvent( LearningContentBar.eventRendered, {
					detail: {
						item: elItem.dataset.learningBarItem || '',
						contentBar,
					},
				} )
			);
		}
	}

	close( { e } ) {
		e.preventDefault();

		const contentBar = document.querySelector(
			LearningContentBar.selectors.contentBar
		);

		if ( contentBar ) {
			contentBar.classList.remove( 'open' );
		}
	}
}

const learningContentBar = new LearningContentBar();
learningContentBar.init();
