/**
 * Input search suggest (autocomplete): type to search, click a result to select it.
 *
 * HTML is printed by AdminInputSearchSuggest::layout() (PHP):
 * <div class="lp-wrap-input-search-suggest">
 *   <input type="text" class="lp-input-search-suggest">
 *   <input type="hidden" class="lp-input-search-suggest-value" name="...">
 *   <div class="lp-search-suggest-result"><div class="lp-target" data-send="..."></div></div>
 * </div>
 *
 * Keyboard on the text input: ArrowDown/ArrowUp move through the list, Enter selects the active item,
 * Escape closes the list.
 *
 * Multiple select (wrapper has data-type-select="multiple", items have a checkbox):
 * selecting an item toggles its ID in the hidden input (ex: 1,2,3), the list stays open
 * until a click outside or Escape. Focusing the input without a keyword lists the selected items (checked).
 * Search results: the selected items are listed first, followed by the results not selected yet.
 * Event detail has more: ids (array), item: { id, text, checked }.
 * The button "Clear all" (first row of the list) unselects all items: hidden input is empty, item is null.
 *
 * When an item is selected, event `lp-search-suggest-selected` is dispatched on the wrapper (bubbles),
 * detail: { id, text, elWrap }.
 *
 * @since 4.4.14
 * @version 1.0.0
 */
import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';

export class LpInputSearchSuggest {
	static selectors = {
		elWrap: '.lp-wrap-input-search-suggest',
		elInput: '.lp-input-search-suggest',
		elInputValue: '.lp-input-search-suggest-value',
		elResult: '.lp-search-suggest-result',
		elItem: '.lp-search-suggest-item',
		elLPTarget: '.lp-target',
		elCheckbox: '.lp-search-suggest-checkbox',
		elActions: '.lp-search-suggest-actions',
		elBtnClearAll: '.lp-search-suggest-clear-all',
	};

	static typeSelectMultiple = 'multiple';

	// Class of the items selected (multiple select).
	static classItemSelected = 'is-selected';

	static eventSelected = 'lp-search-suggest-selected';

	// Class of the item chosen with the keyboard.
	static classItemActive = 'is-active';

	static debounceWait = 500;

	constructor() {
		// One debounced function per input, so components on the same page do not cancel each other.
		this.debouncedFetches = new WeakMap();
		// Token per input, changed on every typing/selection: older pending or running requests become stale.
		this.tokens = new WeakMap();
		// Multiple select: items selected of each wrapper, Map( id => <li> ), to list them again on focus.
		this.itemsSelected = new WeakMap();
		// Multiple select: row of the button "Clear all" of each wrapper, to print it in the lists built by JS.
		this.elsActions = new WeakMap();
	}

	init() {
		this.events();
	}

	events() {
		// Check and attach events only once.
		if ( LpInputSearchSuggest._loadedEvents ) {
			return;
		}

		LpInputSearchSuggest._loadedEvents = this;

		lpUtils.eventHandlers( 'input', [
			{
				selector: LpInputSearchSuggest.selectors.elInput,
				callBack: this.handleInput.bind( this ),
			},
		] );

		lpUtils.eventHandlers( 'focusin', [
			{
				selector: LpInputSearchSuggest.selectors.elInput,
				callBack: this.handleFocus.bind( this ),
			},
		] );

		lpUtils.eventHandlers( 'keydown', [
			{
				selector: LpInputSearchSuggest.selectors.elInput,
				callBack: this.handleKeydown.bind( this ),
			},
		] );

		lpUtils.eventHandlers( 'click', [
			{
				selector: LpInputSearchSuggest.selectors.elItem,
				callBack: this.selectItem.bind( this ),
			},
			{
				selector: LpInputSearchSuggest.selectors.elBtnClearAll,
				callBack: this.clearAll.bind( this ),
			},
			{
				// Any click: close the lists the click is outside of.
				selector: 'body',
				callBack: this.closeOutside.bind( this ),
			},
		] );
	}

	/**
	 * Get elements of the component which contains the element.
	 *
	 * @param {HTMLElement} el Element inside the wrapper.
	 * @return {Object|null} Elements, null if the structure is invalid.
	 */
	getElements( el ) {
		const { selectors } = LpInputSearchSuggest;
		const elWrap = el.closest( selectors.elWrap );
		if ( ! elWrap ) {
			return null;
		}

		const elInput = elWrap.querySelector( selectors.elInput );
		const elInputValue = elWrap.querySelector( selectors.elInputValue );
		const elResult = elWrap.querySelector( selectors.elResult );
		const elLPTarget = elResult?.querySelector( selectors.elLPTarget );
		if ( ! elInput || ! elInputValue || ! elLPTarget ) {
			return null;
		}

		return { elWrap, elInput, elInputValue, elResult, elLPTarget };
	}

	/**
	 * Change the token of the input, so pending and running requests of it are ignored.
	 *
	 * @param {HTMLElement} elInput The text input.
	 * @return {number} The new token.
	 */
	newToken( elInput ) {
		const token = ( this.tokens.get( elInput ) || 0 ) + 1;
		this.tokens.set( elInput, token );

		return token;
	}

	/**
	 * Called when the user types.
	 *
	 * @param {Object} args Event arguments.
	 */
	handleInput( args ) {
		const els = this.getElements( args.target );
		if ( ! els ) {
			return;
		}

		const { elWrap, elInput, elInputValue, elResult, elLPTarget } = els;
		const isMultiple = this.isMultiple( elWrap );

		if ( isMultiple ) {
			// Keep the items selected before the list is replaced.
			this.getItemsSelected( els );
		} else {
			// The text changed, so the selected ID is no longer valid.
			elInputValue.value = elInput.value;
		}

		const token = this.newToken( elInput );

		// Empty text: no request. Multiple: list the items selected, single: clear the list.
		if ( elInput.value.trim() === '' ) {
			this.setLoading( elWrap, elLPTarget, 0 );
			if ( isMultiple ) {
				this.renderItemsSelected( els );
				return;
			}

			elLPTarget.innerHTML = '';
			lpUtils.lpShowHideEl( elResult, 0 );
			return;
		}

		let debouncedFetch = this.debouncedFetches.get( elInput );
		if ( ! debouncedFetch ) {
			debouncedFetch = lpUtils.debounce( ( params ) => {
				this.fetchSuggest( params.elInput, params.token );
			}, LpInputSearchSuggest.debounceWait );
			this.debouncedFetches.set( elInput, debouncedFetch );
		}

		debouncedFetch( { elInput, token } );
	}

	/**
	 * Show the list again when the input is focused and still has results.
	 * Multiple select without a keyword: list the items selected (checked).
	 *
	 * @param {Object} args Event arguments.
	 */
	handleFocus( args ) {
		const els = this.getElements( args.target );
		if ( ! els ) {
			return;
		}

		if (
			this.isMultiple( els.elWrap ) &&
			els.elInput.value.trim() === ''
		) {
			this.renderItemsSelected( els );
			return;
		}

		if ( els.elLPTarget.innerHTML === '' ) {
			return;
		}

		lpUtils.lpShowHideEl( els.elResult, 1 );
	}

	/**
	 * Keyboard on the text input, like a select:
	 * ArrowDown/ArrowUp move the active item, Enter selects it, Escape closes the list.
	 *
	 * @param {Object} args Event arguments.
	 */
	handleKeydown( args ) {
		const { e } = args;
		if ( ! [ 'ArrowDown', 'ArrowUp', 'Enter', 'Escape' ].includes( e.key ) ) {
			return;
		}

		const els = this.getElements( args.target );
		if ( ! els ) {
			return;
		}

		const { elResult, elLPTarget } = els;
		const { selectors, classItemActive } = LpInputSearchSuggest;
		const elItems = Array.from(
			elLPTarget.querySelectorAll( selectors.elItem )
		);
		const isOpen =
			elItems.length > 0 &&
			! elResult.classList.contains( lpUtils.lpClassName.hidden );
		const indexActive = elItems.findIndex( ( elItem ) =>
			elItem.classList.contains( classItemActive )
		);

		if ( e.key === 'Escape' ) {
			if ( isOpen ) {
				e.preventDefault();
				lpUtils.lpShowHideEl( elResult, 0 );
			}
			return;
		}

		if ( e.key === 'Enter' ) {
			// Without an active item, Enter keeps its default behavior.
			if ( isOpen && indexActive >= 0 ) {
				e.preventDefault();
				this.chooseItem( elItems[ indexActive ] );
			}
			return;
		}

		if ( ! elItems.length ) {
			return;
		}

		// Do not move the caret of the input.
		e.preventDefault();

		// The list was closed: open it again, keep the active item.
		if ( ! isOpen ) {
			lpUtils.lpShowHideEl( elResult, 1 );
			if ( indexActive >= 0 ) {
				return;
			}
		}

		// Stop at the first/last item, like a select.
		let indexNew;
		if ( e.key === 'ArrowDown' ) {
			indexNew = Math.min( indexActive + 1, elItems.length - 1 );
		} else {
			indexNew = indexActive < 0 ? elItems.length - 1 : Math.max( indexActive - 1, 0 );
		}

		elItems.forEach( ( elItem, index ) => {
			elItem.classList.toggle( classItemActive, index === indexNew );
		} );
		elItems[ indexNew ].scrollIntoView?.( { block: 'nearest' } );
	}

	/**
	 * Fetch the list of suggestions.
	 *
	 * @param {HTMLElement} elInput The text input.
	 * @param {number}      token   Token created when the user typed.
	 */
	fetchSuggest( elInput, token ) {
		const els = this.getElements( elInput );
		if ( ! els ) {
			return;
		}

		const { elWrap, elResult, elLPTarget } = els;
		// The user typed again, selected an item or cleared the text while waiting/fetching.
		const isStale = () => this.tokens.get( elInput ) !== token;
		const search = elInput.value.trim();
		if ( isStale() || search === '' ) {
			return;
		}

		const dataSend = window.lpAJAXG.getDataSetCurrent( elLPTarget );
		dataSend.args.search = search;
		dataSend.args.paged = 1;
		window.lpAJAXG.setDataSetCurrent( elLPTarget, dataSend );

		this.setLoading( elWrap, elLPTarget, 1 );

		window.lpAJAXG.fetchAJAX( dataSend, {
			success: ( response ) => {
				if ( isStale() ) {
					return;
				}

				const { status, message, data } = response;
				if ( status === 'error' ) {
					lpToastify.show( message, 'error' );
					return;
				}

				// Multiple: get the items selected before the list is replaced.
				const itemsSelected = this.isMultiple( elWrap )
					? this.getItemsSelected( els )
					: null;

				elLPTarget.innerHTML = data?.content || '';
				if ( itemsSelected ) {
					this.prependItemsSelected( els, itemsSelected );
				}
				this.syncItemsChecked( els );
				lpUtils.lpShowHideEl( elResult, 1 );
			},
			error: ( error ) => {
				lpToastify.show( error?.message || error, 'error' );
			},
			completed: () => {
				// The loading is handled by the newer request, the selection or the clearing.
				if ( isStale() ) {
					return;
				}

				this.setLoading( elWrap, elLPTarget, 0 );
			},
		} );
	}

	/**
	 * Called when an item of the list is clicked.
	 *
	 * @param {Object} args Event arguments.
	 */
	selectItem( args ) {
		this.chooseItem(
			args.target.closest( LpInputSearchSuggest.selectors.elItem )
		);
	}

	/**
	 * Select an item: fill the inputs, close the list, dispatch the event.
	 *
	 * @param {HTMLElement|null} elItem Item of the list.
	 */
	chooseItem( elItem ) {
		const els = elItem ? this.getElements( elItem ) : null;
		if ( ! els ) {
			return;
		}

		const { elWrap, elInput, elInputValue, elResult, elLPTarget } = els;
		const id = elItem.dataset.id || '';
		const text = elItem.dataset.text || '';

		if (
			elItem.dataset.typeSelect ===
			LpInputSearchSuggest.typeSelectMultiple
		) {
			this.toggleItem( elItem, els );
			return;
		}

		elInput.value = text;
		elInputValue.value = id;

		// Cancel pending/running requests and remove the list, it does not match the new text.
		this.newToken( elInput );
		this.setLoading( elWrap, elLPTarget, 0 );
		elLPTarget.innerHTML = '';
		lpUtils.lpShowHideEl( elResult, 0 );

		elWrap.dispatchEvent(
			new CustomEvent( LpInputSearchSuggest.eventSelected, {
				bubbles: true,
				detail: { id, text, elWrap },
			} )
		);
	}

	/**
	 * Multiple select: check/uncheck an item, the hidden input has the IDs separated by comma (ex: 1,2,3).
	 * The list stays open and the keyword is kept.
	 *
	 * @param {HTMLElement} elItem Item of the list.
	 * @param {Object}      els    Elements of the component.
	 */
	toggleItem( elItem, els ) {
		const { elWrap, elInputValue } = els;
		const id = elItem.dataset.id || '';
		const text = elItem.dataset.text || '';
		if ( id === '' ) {
			return;
		}

		const itemsSelected = this.getItemsSelected( els );
		let ids = this.getIds( elInputValue );
		const checked = ! ids.includes( id );

		if ( checked ) {
			ids.push( id );
			itemsSelected.set( id, elItem.cloneNode( true ) );
		} else {
			ids = ids.filter( ( idSelected ) => idSelected !== id );
			itemsSelected.delete( id );
		}

		elInputValue.value = ids.join( ',' );
		this.syncItemsChecked( els );

		elWrap.dispatchEvent(
			new CustomEvent( LpInputSearchSuggest.eventSelected, {
				bubbles: true,
				detail: {
					id: elInputValue.value,
					ids,
					item: { id, text, checked },
					text,
					elWrap,
				},
			} )
		);
	}

	/**
	 * Multiple select: called when the button "Clear all" is clicked, unselect all items.
	 * With a keyword the list stays open and the rows stay in place unchecked;
	 * without a keyword (list of the items selected) the list is emptied and closed.
	 *
	 * @param {Object} args Event arguments.
	 */
	clearAll( args ) {
		const { e, target } = args;
		const els = this.getElements( target );
		if ( ! els ) {
			return;
		}

		e.preventDefault();

		const { elWrap, elInput, elInputValue } = els;
		this.getElActions( els );
		elInputValue.value = '';
		this.itemsSelected.get( elWrap )?.clear();
		this.syncItemsChecked( els );
		// The button is hidden now, keep the focus in the component.
		elInput.focus?.();

		elWrap.dispatchEvent(
			new CustomEvent( LpInputSearchSuggest.eventSelected, {
				bubbles: true,
				detail: { id: '', ids: [], item: null, text: '', elWrap },
			} )
		);
	}

	/**
	 * Multiple select: get the row of the button "Clear all" (a clone, to print in the lists built by JS).
	 * It is printed by PHP in every list, remember it when it is seen.
	 *
	 * @param {Object} els Elements of the component.
	 * @return {HTMLElement|null} The row, null if no list was printed yet.
	 */
	getElActions( els ) {
		const { elWrap, elLPTarget } = els;
		const elActions = elLPTarget.querySelector(
			LpInputSearchSuggest.selectors.elActions
		);
		if ( elActions ) {
			this.elsActions.set( elWrap, elActions.cloneNode( true ) );
		}

		return this.elsActions.get( elWrap ) || null;
	}

	/**
	 * Check the component is multiple select.
	 *
	 * @param {HTMLElement} elWrap The wrapper.
	 * @return {boolean} True if multiple select.
	 */
	isMultiple( elWrap ) {
		const { selectors, typeSelectMultiple } = LpInputSearchSuggest;
		if ( elWrap.dataset.typeSelect === typeSelectMultiple ) {
			return true;
		}

		// The wrapper is not marked (ex: custom layout): detect by the items of the list
		// or by the args sent via AJAX, so a new search never clears the items selected.
		let isMultiple = !! elWrap.querySelector(
			`${ selectors.elItem }[data-type-select="${ typeSelectMultiple }"]`
		);

		if ( ! isMultiple ) {
			try {
				const dataSend = JSON.parse(
					elWrap.querySelector( selectors.elLPTarget )?.dataset
						.send || '{}'
				);
				isMultiple = dataSend?.args?.type_select === typeSelectMultiple;
			} catch ( e ) {
				isMultiple = false;
			}
		}

		if ( isMultiple ) {
			elWrap.dataset.typeSelect = typeSelectMultiple;
		}

		return isMultiple;
	}

	/**
	 * Get the IDs selected from the hidden input.
	 *
	 * @param {HTMLElement} elInputValue The hidden input.
	 * @return {string[]} IDs.
	 */
	getIds( elInputValue ) {
		return elInputValue.value
			.split( ',' )
			.map( ( id ) => id.trim() )
			.filter( ( id ) => id !== '' );
	}

	/**
	 * Multiple select: get the items selected of the component, Map( id => <li> ).
	 * Items of the current list which are selected (ex: printed by PHP) are added,
	 * items which are no longer in the hidden input are removed.
	 *
	 * @param {Object} els Elements of the component.
	 * @return {Map} Items selected.
	 */
	getItemsSelected( els ) {
		const { elWrap, elInputValue, elLPTarget } = els;
		// The list is about to be replaced: remember the row of the button "Clear all" too.
		this.getElActions( els );
		let itemsSelected = this.itemsSelected.get( elWrap );
		if ( ! itemsSelected ) {
			itemsSelected = new Map();
			this.itemsSelected.set( elWrap, itemsSelected );
		}

		const ids = this.getIds( elInputValue );

		elLPTarget
			.querySelectorAll( LpInputSearchSuggest.selectors.elItem )
			.forEach( ( elItem ) => {
				const id = elItem.dataset.id || '';
				if ( ids.includes( id ) && ! itemsSelected.has( id ) ) {
					itemsSelected.set( id, elItem.cloneNode( true ) );
				}
			} );

		itemsSelected.forEach( ( elItem, id ) => {
			if ( ! ids.includes( id ) ) {
				itemsSelected.delete( id );
			}
		} );

		return itemsSelected;
	}

	/**
	 * Multiple select: show the list of the items selected (checked), in the order of the hidden input.
	 *
	 * @param {Object} els Elements of the component.
	 */
	renderItemsSelected( els ) {
		const { elInputValue, elResult, elLPTarget } = els;
		const itemsSelected = this.getItemsSelected( els );

		elLPTarget.innerHTML = '';

		if ( ! itemsSelected.size ) {
			lpUtils.lpShowHideEl( elResult, 0 );
			return;
		}

		const elList = document.createElement( 'ul' );
		elList.className = 'lp-search-suggest-list';
		const elActions = this.elsActions.get( els.elWrap );
		if ( elActions ) {
			elList.appendChild( elActions.cloneNode( true ) );
		}

		this.getIds( elInputValue ).forEach( ( id ) => {
			const elItem = itemsSelected.get( id );
			if ( elItem ) {
				elList.appendChild( elItem.cloneNode( true ) );
			}
		} );

		elLPTarget.appendChild( elList );
		this.syncItemsChecked( els );
		lpUtils.lpShowHideEl( elResult, 1 );
	}

	/**
	 * Multiple select: put the items selected at the top of the list of results,
	 * followed by the results which are not selected yet.
	 *
	 * @param {Object} els           Elements of the component.
	 * @param {Map}    itemsSelected Items selected, Map( id => <li> ).
	 */
	prependItemsSelected( els, itemsSelected ) {
		const { elInputValue, elLPTarget } = els;
		// No list: the content is a message (ex: error).
		const elList = elLPTarget.querySelector( '.lp-search-suggest-list' );
		if ( ! elList || ! itemsSelected.size ) {
			return;
		}

		// Results already selected are listed at the top only.
		elList
			.querySelectorAll( LpInputSearchSuggest.selectors.elItem )
			.forEach( ( elItem ) => {
				if ( itemsSelected.has( elItem.dataset.id || '' ) ) {
					elItem.remove();
				}
			} );

		// After the row of the button "Clear all".
		const elActions = elList.querySelector(
			LpInputSearchSuggest.selectors.elActions
		);
		const elFirst = elActions ? elActions.nextSibling : elList.firstChild;
		this.getIds( elInputValue ).forEach( ( id ) => {
			const elItem = itemsSelected.get( id );
			if ( elItem ) {
				elList.insertBefore( elItem.cloneNode( true ), elFirst );
			}
		} );
	}

	/**
	 * Multiple select: check the items of the list which are in the hidden input, uncheck the others.
	 *
	 * @param {Object} els Elements of the component.
	 */
	syncItemsChecked( els ) {
		const { elWrap, elInputValue, elLPTarget } = els;
		if ( ! this.isMultiple( elWrap ) ) {
			return;
		}

		const { selectors, classItemSelected, classItemActive } =
			LpInputSearchSuggest;
		const ids = this.getIds( elInputValue );

		elLPTarget.querySelectorAll( selectors.elItem ).forEach( ( elItem ) => {
			const checked = ids.includes( elItem.dataset.id || '' );
			const elCheckbox = elItem.querySelector( selectors.elCheckbox );

			elItem.classList.toggle( classItemSelected, checked );
			if ( elCheckbox ) {
				elCheckbox.checked = checked;
			}
		} );

		// The button "Clear all" is shown only when an item is selected.
		elLPTarget
			.querySelectorAll( selectors.elActions )
			.forEach( ( elActions ) => {
				lpUtils.lpShowHideEl( elActions, ids.length > 0 ? 1 : 0 );
			} );

		// Items cloned from an older list must not stay active.
		this.itemsSelected
			.get( elWrap )
			?.forEach( ( elItem ) => elItem.classList.remove( classItemActive ) );
	}

	/**
	 * Close the lists when clicking outside of their component.
	 *
	 * @param {Object} args Event arguments.
	 */
	closeOutside( args ) {
		const { selectors } = LpInputSearchSuggest;
		const elWrapClicked = args.target.closest( selectors.elWrap );

		document.querySelectorAll( selectors.elWrap ).forEach( ( elWrap ) => {
			if ( elWrap === elWrapClicked ) {
				return;
			}

			lpUtils.lpShowHideEl(
				elWrap.querySelector( selectors.elResult ),
				0
			);
		} );
	}

	/**
	 * Show/hide loading.
	 *
	 * @param {HTMLElement} elWrap     The wrapper.
	 * @param {HTMLElement} elLPTarget The target of the AJAX content.
	 * @param {number}      status     1: show, 0: hide.
	 */
	setLoading( elWrap, elLPTarget, status ) {
		lpUtils.lpSetLoadingEl( elWrap, status );
		window.lpAJAXG.showHideLoading( elLPTarget, status );
	}
}

new LpInputSearchSuggest().init();
