/**
 * Reset course progress handler.
 *
 * @since 4.4.6
 * @version 1.0.0
 */
import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';
import { LpPopupSelectItemToAdd } from 'lpAssetsJsPath/lpPopupSelectItemToAdd.js';

const lpPopupSelectItemToAdd = new LpPopupSelectItemToAdd();

export default class ResetCourseProgress {
	static selectors = {
		elPopupTemplate: '#lp-tmpl-select-courses-to-reset-progress',
	};

	constructor() {
		this.idUrlHandle = 'reset-course-progress';
	}

	init() {
		this.preparePopupTemplate();
		this.events();
		this.btnChooseCourses = null;
	}

	/**
	 * Modify the popup template HTML before it is opened.
	 * Adds the requested reset button class and label to the action button.
	 */
	preparePopupTemplate() {
		const template = document.querySelector(
			ResetCourseProgress.selectors.elPopupTemplate
		);
		if ( ! template ) {
			return;
		}

		const wrapper = document.createElement( 'div' );
		wrapper.innerHTML = template.innerHTML;

		const btn = wrapper.querySelector( '.lp-btn-add-items-selected' );
		if ( ! btn ) {
			return;
		}

		btn.classList.add( 'lp-btn-reset-courses-progress' );
		btn.textContent = 'Reset course progress';
		template.innerHTML = wrapper.innerHTML;
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
			},
		] );
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

		this.btnChooseCourses.textContent =
			this.btnChooseCourses.dataset.messageResetting;
		lpUtils.lpSetLoadingEl( this.btnChooseCourses, 1 );

		const courseIds = itemsSelectedData
			.map( ( item ) => parseInt( item.id, 10 ) )
			.filter( ( id ) => ! isNaN( id ) && id > 0 );

		window.lpAJAXG.fetchAJAX(
			{
				id_url: 'course-reset-progress-tool',
				action: 'reset_progress_courses',
				course_ids: courseIds,
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
}
