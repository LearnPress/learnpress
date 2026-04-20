import SweetAlert from 'sweetalert2';
import * as lpUtils from 'lpAssetsJsPath/utils.js';

export class ViewStudentsModal {
	constructor() {
		this.isRequesting = false;
		this.activeCourseId = 0;
		this.init();
	}

	static selectors = {
		wrap: '#lp-modal-enrolled-wrap',
		form: '#lp-modal-enrolled-form',
		toolbarTemplate: '#lp-tmpl-enrolled-students-toolbar-modal',
		targetTemplate: '#lp-tmpl-enrolled-students-target-modal',
		toolbar: '.lp-enrolled-students-table-toolbar--modal',
		courseTrigger: '.lp-btn-view-students',
		searchInput: '#lp-modal-enrolled-search-input',
		startDateInput: '#lp-modal-enrolled-filter-start-date',
		endDateInput: '#lp-modal-enrolled-filter-end-date',
		searchBtn: '.lp-enrolled-btn-search-modal',
		clearBtn: '.lp-enrolled-btn-clear-modal',
		modalSearchFields:
			'#lp-modal-enrolled-search-input, #lp-modal-enrolled-filter-start-date, #lp-modal-enrolled-filter-end-date',
	};

	setButtonLoadingState( btn, isLoading ) {
		if ( ! btn ) {
			return;
		}

		lpUtils.lpSetLoadingEl( btn, isLoading ? 1 : 0 );
		btn.disabled = !! isLoading;
	}

	init() {
		if ( ViewStudentsModal._loadedEvents ) {
			return;
		}

		ViewStudentsModal._loadedEvents = true;

		lpUtils.eventHandlers( 'click', [
			{
				selector: ViewStudentsModal.selectors.courseTrigger,
				class: this,
				callBack: this.handleOpenModal.name,
			},
			{
				selector: ViewStudentsModal.selectors.searchBtn,
				class: this,
				callBack: this.handleModalSearch.name,
			},
			{
				selector: ViewStudentsModal.selectors.clearBtn,
				class: this,
				callBack: this.handleModalClear.name,
			},
		] );

		lpUtils.eventHandlers( 'keydown', [
			{
				selector: ViewStudentsModal.selectors.modalSearchFields,
				class: this,
				callBack: this.handleModalSearchOnEnter.name,
				checkIsEventEnter: true,
			},
		] );

		lpUtils.eventHandlers( 'change', [
			{
				selector: ViewStudentsModal.selectors.startDateInput,
				class: this,
				callBack: this.checkDatesRange.name,
			},
			{
				selector: ViewStudentsModal.selectors.endDateInput,
				class: this,
				callBack: this.checkDatesRange.name,
			},
		] );
	}

	handleOpenModal( args ) {
		const btn = args?.target?.closest(
			ViewStudentsModal.selectors.courseTrigger
		);
		if (
			! btn ||
			this.isRequesting ||
			btn.classList.contains( 'loading' ) ||
			btn.disabled
		) {
			return;
		}

		const courseId = parseInt( btn.dataset.courseId, 10 ) || 0;
		if ( ! courseId ) {
			return;
		}

		const courseTitle = btn.dataset.courseTitle || '';
		this.activeCourseId = courseId;

		this.setButtonLoadingState( btn, true );
		this.openModal( courseId, courseTitle, btn );
	}

	handleModalSearch( args ) {
		const btn = args?.target?.closest(
			ViewStudentsModal.selectors.searchBtn
		);
		if ( ! btn || ! this.activeCourseId ) {
			return;
		}

		if ( args?.e ) {
			args.e.preventDefault();
		}

		if (
			this.isRequesting ||
			btn.classList.contains( 'loading' ) ||
			btn.disabled
		) {
			return;
		}

		this.setButtonLoadingState( btn, true );
		this.loadEnrolledStudents(
			this.activeCourseId,
			1,
			btn
		);
	}

	handleModalSearchOnEnter( args ) {
		if ( args?.e ) {
			args.e.preventDefault();
		}

		const form = this.getModalForm();
		if ( ! form ) {
			return;
		}

		const btn = form.querySelector( ViewStudentsModal.selectors.searchBtn );
		if ( ! btn ) {
			return;
		}

		this.handleModalSearch( { ...args, target: btn } );
	}

	handleModalClear( args ) {
		const btn = args?.target?.closest(
			ViewStudentsModal.selectors.clearBtn
		);
		const form = this.getModalForm();
		if ( ! btn || ! form || ! this.activeCourseId ) {
			return;
		}

		if ( args?.e ) {
			args.e.preventDefault();
		}

		if (
			this.isRequesting ||
			btn.classList.contains( 'loading' ) ||
			btn.disabled
		) {
			return;
		}

		form.reset();
		this.setButtonLoadingState( btn, true );
		this.loadEnrolledStudents(
			this.activeCourseId,
			1,
			btn
		);
	}

	getModalPopup() {
		return SweetAlert.getPopup ? SweetAlert.getPopup() : null;
	}

	getModalToolbarHtml() {
		const template = document.querySelector(
			ViewStudentsModal.selectors.toolbarTemplate
		);

		return template ? template.innerHTML : '';
	}

	getModalTargetHtml() {
		const template = document.querySelector(
			ViewStudentsModal.selectors.targetTemplate
		);

		return template ? template.innerHTML : '';
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

	getModalForm() {
		const popup = this.getModalPopup();
		if ( ! popup ) {
			return null;
		}

		return popup.querySelector( ViewStudentsModal.selectors.form );
	}

	getModalFilterArgs( dataArgs = {} ) {
		const form = this.getModalForm();
		if ( ! form ) {
			return dataArgs;
		}

		return lpUtils.mergeDataWithDatForm( form, dataArgs );
	}

	loadEnrolledStudents( courseId, paged, elLoading = null ) {
		const wrap = document.querySelector( ViewStudentsModal.selectors.wrap );
		const elTarget = wrap?.querySelector( '.lp-target' );
		const ajaxHandle = this.getAjaxHandle();
		if ( ! wrap || ! elTarget || ! ajaxHandle || this.isRequesting ) {
			return;
		}

		this.isRequesting = true;
		if ( elLoading ) {
			this.setButtonLoadingState( elLoading, true );
		}

		const dataSend = ajaxHandle.getDataSetCurrent( elTarget );
		dataSend.args = this.getModalFilterArgs( dataSend.args || {} );
		dataSend.args.course_id = parseInt( courseId, 10 ) || 0;
		dataSend.args.paged = paged;
		ajaxHandle.setDataSetCurrent( elTarget, dataSend );
		ajaxHandle.showHideLoading( elTarget, 1 );

		const callBack = {
			success: ( response ) => {
				elTarget.innerHTML = response.data.content;
			},
			error: ( err ) => {
				console.error( err );
			},
			completed: () => {
				this.isRequesting = false;
				ajaxHandle.showHideLoading( elTarget, 0 );
				if ( elLoading ) {
					this.setButtonLoadingState( elLoading, false );
				}
			},
		};

		ajaxHandle.fetchAJAX( dataSend, callBack );
	}

	openModal( courseId, courseTitle, elTrigger = null ) {
		const modalToolbarHtml = this.getModalToolbarHtml();
		const modalTargetHtml = this.getModalTargetHtml();

		if ( ! modalToolbarHtml || ! modalTargetHtml ) {
			if ( elTrigger ) {
				this.setButtonLoadingState( elTrigger, false );
			}
			return;
		}

		this.activeCourseId = parseInt( courseId, 10 ) || 0;

		SweetAlert.fire( {
			title: `${ courseTitle }`,
			html: modalToolbarHtml + modalTargetHtml,
			width: '80%',
			showConfirmButton: false,
			showCloseButton: true,
			didOpen: () => {
				this.loadEnrolledStudents(
					this.activeCourseId,
					1,
					elTrigger
				);
			},
			didClose: () => {
				this.activeCourseId = 0;
				if ( elTrigger ) {
					this.setButtonLoadingState( elTrigger, false );
				}
			},
		} );
	}

	// Ensure start date is not after end date and vice versa. If invalid, adjust the other date to match.
	checkDatesRange( args ) {
		const { e } = args;
		const elInput = e?.target;
		if ( ! elInput ) {
			return;
		}

		const elForm = elInput.closest( ViewStudentsModal.selectors.form );
		if ( ! elForm ) {
			return;
		}

		const startDateInput = elForm.querySelector(
			ViewStudentsModal.selectors.startDateInput,
		);
		const endDateInput = elForm.querySelector(
			ViewStudentsModal.selectors.endDateInput,
		);

		if ( elInput === startDateInput ) {
			if ( startDateInput.value ) {
				endDateInput.min = startDateInput.value;
				if (
					endDateInput.value &&
					endDateInput.value < startDateInput.value
				) {
					endDateInput.value = startDateInput.value;
				}
			} else {
				endDateInput.min = '';
			}
		} else if ( elInput === endDateInput ) {
			if ( endDateInput.value ) {
				startDateInput.max = endDateInput.value;
				if (
					startDateInput.value &&
					startDateInput.value > endDateInput.value
				) {
					startDateInput.value = endDateInput.value;
				}
			} else {
				startDateInput.max = '';
			}
		}
	}
}
