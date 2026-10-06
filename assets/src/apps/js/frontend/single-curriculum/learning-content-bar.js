import * as lpUtils from 'lpAssetsJsPath/utils.js';

class LearningContentBar {
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

	open() {
		const contentBar = document.querySelector(
			LearningContentBar.selectors.contentBar
		);

		if ( contentBar ) {
			contentBar.classList.add( 'open' );
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
