/**
 * Reset course user progress handler.
 *
 * @since 4.4.6
 * @version 1.0.0
 */
import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';
import { LpPopupSelectItemToAdd } from 'lpAssetsJsPath/lpPopupSelectItemToAdd.js';
import SweetAlert from "sweetalert2";

const lpPopupSelectItemToAdd = new LpPopupSelectItemToAdd();

export default class ResetCourseProgress {
	static selectors = {
		elPopupTemplate: '#lp-tmpl-select-courses-to-reset-progress',
		elFilterField: '.lp-filter-field',
		elFormFilter: '.lp-form-filter-reset-course-progress',
		elPopupItemsToSelect: '.lp-popup-select-courses-to-reset-progress',
		elBtnResetAll: '.lp-btn-reset-all-courses-progress',
	};

	constructor() {
		this.btnChooseCourses = null;
		this.elFormFilter = null;
		this.elPopupItemsToSelect = null;
		this.elBtnResetAll = null;
		this.elBtnAddItemsSelected = null;
		this.debouncedSearchUsers = lpUtils.debounce( ( elForm ) => {
			this.fetchCourses( elForm )
		}, 800 );
	}

	init() {
		this.events();
	}

	events() {
		// Check and attach events only once.
		if ( ResetCourseProgress._loadedEvents ) {
			return;
		}

		ResetCourseProgress._loadedEvents = this;

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
						ResetCourseProgress.selectors.elPopupItemsToSelect
					);
				},
			},
			{
				selector:
				ResetCourseProgress.selectors
					.elBtnResetAll,
				class: this,
				callBack: this.resetAllCoursesProgress.name,
			},
		] );

		// Change events.
		lpUtils.eventHandlers( 'keyup', [
			{
				selector: ResetCourseProgress.selectors.elFilterField,
				class: this,
				callBack: this.filterCourses.name,
			}
		] )
	}

	/**
	 * Called when the picker button is clicked.
	 *
	 * @param {Object} args Event arguments.
	 */
	handleShowPopupItemsToSelect( args ) {
		const { e, target } = args;

		this.btnChooseCourses = target.closest(
			'.lp-btn-choose-courses-to-reset-progress'
		);
		if ( ! this.btnChooseCourses ) {
			return;
		}

		this.elPopupItemsToSelect = SweetAlert.getPopup().querySelector(
			ResetCourseProgress.selectors.elPopupItemsToSelect
		);
		if ( ! this.elPopupItemsToSelect ) {
			return;
		}

		this.elBtnAddItemsSelected = this.elPopupItemsToSelect.querySelector(
			LpPopupSelectItemToAdd.selectors.elBtnAddItemsSelected
		);
		if ( ! this.elBtnAddItemsSelected ) {
			return;
		}

		this.elBtnResetAll = this.elPopupItemsToSelect.querySelector(
			ResetCourseProgress.selectors.elBtnResetAll
		);
	}

	/**
	 * Called after courses are selected in the popup and the action button is clicked.
	 *
	 * @param {Array} itemsSelectedData Selected item data from the popup.
	 */
	addItemsSelectedToSection( itemsSelectedData ) {
		if ( ! this.btnChooseCourses ) {
			return;
		}

		const messageConfirm = this.elBtnAddItemsSelected.dataset.messageConfirm;
		if ( ! messageConfirm || confirm( messageConfirm ) === false ) {
			return;
		}

		this.btnChooseCourses.textContent =
			this.btnChooseCourses.dataset.messageResetting;
		lpUtils.lpSetLoadingEl( this.btnChooseCourses, 1 );

		const userItemIds = itemsSelectedData
			.map( ( item ) => parseInt( item.id, 10 ) )
			.filter( ( id ) => ! isNaN( id ) && id > 0 );

		const elSearchUser = this.elFormFilter?.querySelector( '.lp-search-user' );
		const searchUserValue = elSearchUser?.value || '';

		window.lpAJAXG.fetchAJAX(
			{
				id_url: 'course-reset-progress-tool',
				action: 'reset_progress_courses',
				user_item_ids: userItemIds,
				search_user: searchUserValue,
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
					this.btnChooseCourses.textContent =
						this.btnChooseCourses.dataset.messageChoose;
					lpUtils.lpSetLoadingEl( this.btnChooseCourses, 0 );
				},
			}
		);
	}

	/**
	 * Reset all courses progress.
	 *
	 * @param {Object} args Event arguments.
	 */
	resetAllCoursesProgress( args ) {
		const { e, target } = args;

		const elPopupItemsToSelect = target.closest( ResetCourseProgress.selectors.elPopupItemsToSelect );
		if ( ! elPopupItemsToSelect ) {
			return;
		}

		const elFormFilter = elPopupItemsToSelect.querySelector( ResetCourseProgress.selectors.elFormFilter );
		if ( ! elFormFilter ) {
			return;
		}

		const messageConfirm = this.elBtnResetAll.dataset.messageConfirm;
		if ( ! messageConfirm || confirm( messageConfirm ) === false ) {
			return;
		}

		// Show loading
		lpUtils.lpSetLoadingEl( this.btnChooseCourses, 1 );

		SweetAlert.close();

		const formData = lpUtils.getDataOfForm( elFormFilter );

		window.lpAJAXG.fetchAJAX(
			{
				id_url: 'course-reset-progress-tool',
				action: 'reset_progress_courses',
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
					this.btnChooseCourses.textContent =
						this.btnChooseCourses.dataset.messageChoose;
					lpUtils.lpSetLoadingEl( this.btnChooseCourses, 0 );
				},
			}
		);
	}

	/**
	 * Fetch courses to reset progress.
	 *
	 * @param {HTMLElement} elForm The form element.
	 */
	fetchCourses( elForm ) {
		this.elFormFilter = elForm;
		const elPopup = elForm.closest( ResetCourseProgress.selectors.elPopupItemsToSelect );
		const elLPTarget = elPopup.querySelector( '.lp-target' );
		let dataSend = window.lpAJAXG.getDataSetCurrent( elLPTarget );
		dataSend.args = lpUtils.mergeDataWithDatForm( elForm, dataSend.args  );
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
	 * Filter courses to reset progress.
	 *
	 * @param {Object} args Event arguments.
	 */
	filterCourses( args ) {
		const { target } = args;
		const elFilterField = target.closest(
			ResetCourseProgress.selectors.elFilterField
		);
		if ( ! elFilterField ) {
			return;
		}

		const elForm = elFilterField.closest( ResetCourseProgress.selectors.elFormFilter );
		if ( ! elForm ) {
			return;
		}

		this.debouncedSearchUsers( elForm );
	}
}
