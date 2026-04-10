/**
 * List Students Enrolled Script
 *
 * Handles filter and search interactions for the enrolled students table.
 * Pagination is handled by loadAJAX.js clickNumberPage (via .page-numbers class).
 *
 * @since 4.3.3
 * @version 1.0.0
 */
import * as lpUtils from 'lpAssetsJsPath/utils.js';

export class ListStudentsEnrolled {
	constructor() {
		this.instructorId = null;
		this.elContainer = null;
		this.isRequesting = false;
	}

	static selectors = {
		elContainer: '#lp-enrolled-students',
		elForm: '.lp-enrolled-students-form',
		elLPTarget: '.lp-target',
		elCourseNameInput: '.lp-enrolled-filter-course-name',
		elCourseIdInput: '#lp-enrolled-filter-course-id',
		elCourseList: '#lp-enrolled-course-list',
		elSearchInput: '.lp-enrolled-search-input',
		elStartDateInput: '.lp-enrolled-filter-start-date',
		elEndDateInput: '.lp-enrolled-filter-end-date',
		elBtnSearch: '.lp-enrolled-btn-search',
		elBtnClear: '.lp-enrolled-btn-clear',
	};

	init() {
		this.elContainer = document.querySelector(
			ListStudentsEnrolled.selectors.elContainer
		);
		if ( ! this.elContainer ) {
			return;
		}

		const elLPTarget = this.elContainer.querySelector(
			ListStudentsEnrolled.selectors.elLPTarget
		);
		const ajaxHandle = this.getAjaxHandle();
		if ( elLPTarget && ajaxHandle ) {
			const dataSend = ajaxHandle.getDataSetCurrent( elLPTarget );
			if ( dataSend && dataSend.args ) {
				this.instructorId = dataSend.args.instructor_id;
			}
		}

		this.events();
	}

	events() {
		if ( ListStudentsEnrolled._loadedEvents ) {
			return;
		}

		ListStudentsEnrolled._loadedEvents = this;

		// Search/Clear button click.
		lpUtils.eventHandlers( 'click', [
			{
				selector: ListStudentsEnrolled.selectors.elBtnSearch,
				class: this,
				callBack: this.searchStudents.name,
			},
			{
				selector: ListStudentsEnrolled.selectors.elBtnClear,
				class: this,
				callBack: this.clearFilters.name,
			},
		] );

		// Search on Enter key.
		lpUtils.eventHandlers( 'keydown', [
			{
				selector: ListStudentsEnrolled.selectors.elSearchInput,
				class: this,
				callBack: this.triggerBtnSearch.name,
				checkIsEventEnter: true,
			},
			{
				selector: ListStudentsEnrolled.selectors.elCourseNameInput,
				class: this,
				callBack: this.triggerBtnSearch.name,
				checkIsEventEnter: true,
			},
			{
				selector: ListStudentsEnrolled.selectors.elStartDateInput,
				class: this,
				callBack: this.triggerBtnSearch.name,
				checkIsEventEnter: true,
			},
			{
				selector: ListStudentsEnrolled.selectors.elEndDateInput,
				class: this,
				callBack: this.triggerBtnSearch.name,
				checkIsEventEnter: true,
			},
		] );

		lpUtils.eventHandlers( 'change', [
			{
				selector: ListStudentsEnrolled.selectors.elStartDateInput,
				class: this,
				callBack: this.checkDatesRange.name,
			},
			{
				selector: ListStudentsEnrolled.selectors.elEndDateInput,
				class: this,
				callBack: this.checkDatesRange.name,
			},
		] );
	}

	// Click button search.
	triggerBtnSearch() {
		const buttonSearch = this.elContainer.querySelector(
			ListStudentsEnrolled.selectors.elBtnSearch
		);
		if ( buttonSearch ) {
			buttonSearch.click();
		}
	}

	// Ensure start date is not after end date and vice versa. If invalid, adjust the other date to match.
	checkDatesRange( args ) {
		const { e } = args;
		const elInput = e?.target;
		if ( ! elInput ) {
			return;
		}

		const elForm = elInput.closest( ListStudentsEnrolled.selectors.elForm );
		if ( ! elForm ) {
			return;
		}

		const startDateInput = elForm.querySelector(
			ListStudentsEnrolled.selectors.elStartDateInput,
		);
		const endDateInput = elForm.querySelector(
			ListStudentsEnrolled.selectors.elEndDateInput,
		);

		if ( elInput === startDateInput ) {
			if ( startDateInput.value ) {
				endDateInput.min = startDateInput.value;
				if ( endDateInput.value && endDateInput.value < startDateInput.value ) {
					endDateInput.value = startDateInput.value;
				}
			} else {
				endDateInput.min = '';
			}
		} else if ( elInput === endDateInput ) {
			if ( endDateInput.value ) {
				startDateInput.max = endDateInput.value;
				if ( startDateInput.value && startDateInput.value > endDateInput.value ) {
					startDateInput.value = endDateInput.value;
				}
			} else {
				startDateInput.max = '';
			}
		}
	}

	setButtonLoadingState( btn, isLoading ) {
		if ( ! btn ) {
			return;
		}

		lpUtils.lpSetLoadingEl( btn, isLoading ? 1 : 0 );
		btn.disabled = !! isLoading;
	}

	getAjaxHandle() {
		const ajaxHandle = window.lpAJAXG;
		if (
			! ajaxHandle ||
			typeof ajaxHandle.getDataSetCurrent !== 'function' ||
			typeof ajaxHandle.setDataSetCurrent !== 'function' ||
			typeof ajaxHandle.showHideLoading !== 'function' ||
			typeof ajaxHandle.fetchAJAX !== 'function'
		) {
			return null;
		}

		return ajaxHandle;
	}

	syncCourseIdFromName( elForm ) {
		const courseIdInput = elForm?.querySelector(
			ListStudentsEnrolled.selectors.elCourseIdInput
		);
		if ( ! courseIdInput ) {
			return;
		}

		courseIdInput.value = '0';

		const courseNameInput = elForm.querySelector(
			ListStudentsEnrolled.selectors.elCourseNameInput
		);
		const datalist = elForm.querySelector(
			ListStudentsEnrolled.selectors.elCourseList
		);
		const courseName = courseNameInput?.value.trim() || '';
		if ( ! courseName || ! datalist ) {
			return;
		}

		const selectedOption = Array.from( datalist.options || [] ).find(
			( option ) => option.value.trim() === courseName
		);
		if ( selectedOption ) {
			courseIdInput.value = selectedOption.dataset.courseId || '0';
		}
	}

	getFilterArgsFromForm( elForm, dataArgs = {} ) {
		this.syncCourseIdFromName( elForm );
		return lpUtils.mergeDataWithDatForm( elForm, dataArgs );
	}

	/**
	 * Search students: update args.search, re-fetch.
	 * @param args
	 */
	searchStudents( args ) {
		const { e } = args;
		if ( e ) {
			e.preventDefault();
		}
		const btn = args?.target?.closest(
			ListStudentsEnrolled.selectors.elBtnSearch
		);
		if ( btn ) {
			if (
				this.isRequesting ||
				btn.classList.contains( 'loading' ) ||
				btn.disabled
			) {
				return;
			}
		} else if ( this.isRequesting ) {
			return;
		}

		const elForm = btn.closest( ListStudentsEnrolled.selectors.elForm );
		const elLPTarget = this.elContainer.querySelector(
			ListStudentsEnrolled.selectors.elLPTarget
		);
		if ( ! elLPTarget || ! elForm ) {
			return;
		}

		const ajaxHandle = this.getAjaxHandle();
		if ( ! ajaxHandle ) {
			return;
		}

		this.setButtonLoadingState( btn, true );

		const dataSend = ajaxHandle.getDataSetCurrent( elLPTarget );
		dataSend.args = this.getFilterArgsFromForm(
			elForm,
			dataSend.args || {}
		);
		dataSend.args.paged = 1;
		ajaxHandle.setDataSetCurrent( elLPTarget, dataSend );

		this.reloadContent( elLPTarget, dataSend, btn );
	}

	/**
	 * Clear all filters and reload default data.
	 * @param args
	 */
	clearFilters( args ) {
		const { e } = args;
		if ( e ) {
			e.preventDefault();
		}
		const btn = args?.target?.closest(
			ListStudentsEnrolled.selectors.elBtnClear
		);
		if ( btn ) {
			if (
				this.isRequesting ||
				btn.classList.contains( 'loading' ) ||
				btn.disabled
			) {
				return;
			}
		} else if ( this.isRequesting ) {
			return;
		}

		const elForm = btn.closest( ListStudentsEnrolled.selectors.elForm );
		const elLPTarget = this.elContainer.querySelector(
			ListStudentsEnrolled.selectors.elLPTarget
		);
		if ( ! elLPTarget || ! elForm ) {
			return;
		}

		const ajaxHandle = this.getAjaxHandle();
		if ( ! ajaxHandle ) {
			return;
		}

		this.setButtonLoadingState( btn, true );

		elForm.reset();
		this.syncCourseIdFromName( elForm );

		const dataSend = ajaxHandle.getDataSetCurrent( elLPTarget );
		dataSend.args = lpUtils.mergeDataWithDatForm(
			elForm,
			dataSend.args || {}
		);
		dataSend.args.paged = 1;
		ajaxHandle.setDataSetCurrent( elLPTarget, dataSend );

		this.reloadContent( elLPTarget, dataSend, btn );
	}

	/**
	 * Shared reload helper: loading indicator + AJAX fetch.
	 * @param elLPTarget
	 * @param dataSend
	 * @param btn
	 */
	reloadContent( elLPTarget, dataSend, btn = null ) {
		const ajaxHandle = this.getAjaxHandle();
		if ( ! ajaxHandle ) {
			this.isRequesting = false;
			this.setButtonLoadingState( btn, false );
			return;
		}

		this.isRequesting = true;
		ajaxHandle.showHideLoading( elLPTarget, 1 );

		const callBack = {
			success: ( response ) => {
				const { status, data } = response;
				if ( 'success' === status ) {
					elLPTarget.innerHTML = data.content;
				}
			},
			error: ( error ) => console.error( error ),
			completed: () => {
				this.isRequesting = false;
				ajaxHandle.showHideLoading( elLPTarget, 0 );
				this.setButtonLoadingState( btn, false );
			},
		};

		ajaxHandle.fetchAJAX( dataSend, callBack );
	}
}

// Auto-initialize when DOM is available (for standalone page load).
const listStudentsEnrolled = new ListStudentsEnrolled();

lpUtils.lpOnElementReady( ListStudentsEnrolled.selectors.elContainer, () => {
	listStudentsEnrolled.init();
} );
