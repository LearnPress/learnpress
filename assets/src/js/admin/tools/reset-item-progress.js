/**
 * Reset item user progress handler.
 *
 * @since 4.4.6
 * @version 1.0.0
 */
import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';
import { LpPopupSelectItemToAdd } from 'lpAssetsJsPath/lpPopupSelectItemToAdd.js';
import SweetAlert from "sweetalert2";

const lpPopupSelectItemToAdd = new LpPopupSelectItemToAdd( {
	openButtonSelector: '.lp-btn-choose-item-to-reset-progress',
} );

export default class ResetItemProgress {
	static selectors = {
		elPopupTemplate: '#lp-tmpl-select-items-to-reset-progress',
		elFilterField: '.lp-filter-field',
		elFormFilter: '.lp-form-filter-reset-item-progress',
		elPopupItemsToSelect: '.lp-popup-select-items-to-reset-progress',
		elBtnResetAll: '.lp-btn-reset-all-items-progress',
	};

	constructor() {
		this.btnChooseItems = null;
		this.elFormFilter = null;
		this.elPopupItemsToSelect = null;
		this.elBtnResetAll = null;
		this.elBtnAddItemsSelected = null;
		this.debouncedSearchItems = lpUtils.debounce( ( elForm ) => {
			this.fetchItems( elForm )
		}, 800 );
	}

	init() {
		this.events();
	}

	events() {
		// Check and attach events only once.
		if ( ResetItemProgress._loadedEvents ) {
			return;
		}

		ResetItemProgress._loadedEvents = this;

		// Click events.
		lpUtils.eventHandlers( 'click', [
			{
				selector:
					LpPopupSelectItemToAdd.selectors
						.elBtnShowPopupItemsToSelect,
				class: this,
				callBack: this.handleShowPopupItemsToSelect.name,
			},
			{
				selector:
					LpPopupSelectItemToAdd.selectors.elBtnAddItemsSelected,
				class: lpPopupSelectItemToAdd,
				callBack: lpPopupSelectItemToAdd.addItemsSelectedToSection.name,
				callBackHandle: this.addItemsSelectedToSection.bind( this ),
				conditionBeforeCallBack: ( args ) => {
					// Only run when the Add button inside this tool's popup is clicked.
					return !! args.target.closest(
						ResetItemProgress.selectors.elPopupItemsToSelect
					);
				},
			},
			{
				selector:
				ResetItemProgress.selectors
					.elBtnResetAll,
				class: this,
				callBack: this.resetAllItemsProgress.name,
			},
		] );

		// Change events.
		lpUtils.eventHandlers( 'keyup', [
			{
				selector: ResetItemProgress.selectors.elFilterField,
				class: this,
				callBack: this.filterItems.name,
			}
		] );

		lpUtils.eventHandlers( 'change', [
			{
				selector: ResetItemProgress.selectors.elFilterField,
				class: this,
				callBack: this.filterItems.name,
			}
		] );
	}

	/**
	 * Called when the picker button is clicked.
	 *
	 * @param {Object} args Event arguments.
	 */
	handleShowPopupItemsToSelect( args ) {
		const { e, target } = args;
		this.btnChooseItems = target.closest(
			'.lp-btn-choose-item-to-reset-progress'
		);

		if ( ! this.btnChooseItems ) {
			return;
		}

		this.elPopupItemsToSelect = SweetAlert.getPopup().querySelector(
			ResetItemProgress.selectors.elPopupItemsToSelect
		);

		this.elBtnAddItemsSelected = this.elPopupItemsToSelect.querySelector(
			LpPopupSelectItemToAdd.selectors.elBtnAddItemsSelected
		);
		this.elBtnResetAll = this.elPopupItemsToSelect.querySelector(
			ResetItemProgress.selectors.elBtnResetAll
		);
	}

	/**
	 * Called after items are selected in the popup and the action button is clicked.
	 *
	 * @param {Array} itemsSelectedData Selected item data from the popup.
	 */
	addItemsSelectedToSection( itemsSelectedData ) {
		if ( ! this.btnChooseItems ) {
			return;
		}

		const messageConfirm = this.elBtnAddItemsSelected.dataset.messageConfirm;
		if ( ! messageConfirm || confirm( messageConfirm ) === false ) {
			return;
		}

		this.btnChooseItems.textContent =
			this.btnChooseItems.dataset.messageResetting;
		lpUtils.lpSetLoadingEl( this.btnChooseItems, 1 );

		const userItemIds = itemsSelectedData
			.map( ( item ) => parseInt( item.id, 10 ) )
			.filter( ( id ) => ! isNaN( id ) && id > 0 );

		window.lpAJAXG.fetchAJAX(
			{
				id_url: 'item-reset-progress-tool',
				action: 'reset_progress_items_course',
				user_item_ids: userItemIds,
			},
			{
				success: ( response ) => {
					const { status, message } = response;
					lpToastify.show( message, status );
				},
				error: ( error ) => {
					lpToastify.show( error.message, 'error' );
				},
				completed: () => {
					this.btnChooseItems.textContent =
						this.btnChooseItems.dataset.messageChoose;
					lpUtils.lpSetLoadingEl( this.btnChooseItems, 0 );
				},
			}
		);
	}

	/**
	 * Reset all items progress.
	 *
	 * @param {Object} args Event arguments.
	 */
	resetAllItemsProgress( args ) {
		const { e, target } = args;

		const elPopupItemsToSelect = target.closest( ResetItemProgress.selectors.elPopupItemsToSelect );
		if ( ! elPopupItemsToSelect ) {
			return;
		}

		const elFormFilter = elPopupItemsToSelect.querySelector( ResetItemProgress.selectors.elFormFilter );
		if ( ! elFormFilter ) {
			return;
		}

		const messageConfirm = this.elBtnResetAll.dataset.messageConfirm;
		if ( ! messageConfirm || confirm( messageConfirm ) === false ) {
			return;
		}

		// Show loading
		lpUtils.lpSetLoadingEl( this.btnChooseItems, 1 );

		SweetAlert.close();

		const formData = lpUtils.getDataOfForm( elFormFilter );

		window.lpAJAXG.fetchAJAX(
			{
				id_url: 'item-reset-progress-tool',
				action: 'reset_progress_items_course',
				reset_all: 1,
				...formData,
			},
			{
				success: ( response ) => {
					const { status, message } = response;
					lpToastify.show( message, status );
				},
				error: ( error ) => {
					lpToastify.show( error.message, 'error' );
				},
				completed: () => {
					this.btnChooseItems.textContent =
						this.btnChooseItems.dataset.messageChoose;
					lpUtils.lpSetLoadingEl( this.btnChooseItems, 0 );
				},
			}
		);
	}

	/**
	 * Fetch items to reset progress.
	 *
	 * @param {HTMLElement} elForm The form element.
	 */
	fetchItems( elForm ) {
		this.elFormFilter = elForm;
		const elPopup = elForm.closest( ResetItemProgress.selectors.elPopupItemsToSelect );
		const elLPTarget = elPopup.querySelector( '.lp-target' );
		let dataSend = window.lpAJAXG.getDataSetCurrent( elLPTarget );
		dataSend.args = lpUtils.mergeDataWithDatForm( elForm, dataSend.args );
		dataSend.args.paged = 1;
		window.lpAJAXG.setDataSetCurrent( elLPTarget, dataSend );

		// Show loading
		window.lpAJAXG.showHideLoading( elLPTarget, 1 );

		window.lpAJAXG.fetchAJAX( dataSend, {
			success: ( response ) => {
				const { data } = response;
				elLPTarget.innerHTML = data.content || '';
			},
			error: ( error ) => {
				lpToastify.show( error, 'error' );
			},
			completed: () => {
				window.lpAJAXG.showHideLoading( elLPTarget, 0 );
			},
		} );
	}

	/**
	 * Filter items to reset progress.
	 *
	 * @param {Object} args Event arguments.
	 */
	filterItems( args ) {
		const { target } = args;
		const elFilterField = target.closest(
			ResetItemProgress.selectors.elFilterField
		);
		if ( ! elFilterField ) {
			return;
		}

		const elForm = elFilterField.closest( ResetItemProgress.selectors.elFormFilter );
		if ( ! elForm ) {
			return;
		}

		this.debouncedSearchItems( elForm );
	}
}
