import SweetAlert from 'sweetalert2';
import { __ } from '@wordpress/i18n';
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
		toolbar: '.lp-enrolled-students-table-toolbar--modal',
		courseTrigger: '.lp-btn-view-students',
		searchInput: '#lp-modal-enrolled-search-input',
		startDateInput: '#lp-modal-enrolled-filter-start-date',
		endDateInput: '#lp-modal-enrolled-filter-end-date',
		searchBtn: '.lp-enrolled-btn-search-modal',
		clearBtn: '.lp-enrolled-btn-clear-modal',
		paginationLink: '.page-numbers',
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
			{
				selector: `${ ViewStudentsModal.selectors.wrap } ${ ViewStudentsModal.selectors.paginationLink }`,
				class: this,
				callBack: this.handleModalPagination.name,
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
			this.getModalFilterValues(),
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
			this.getModalFilterValues(),
			btn
		);
	}

	handleModalPagination( args ) {
		const link = args?.target?.closest(
			ViewStudentsModal.selectors.paginationLink
		);
		if ( ! link || ! link.closest( ViewStudentsModal.selectors.wrap ) ) {
			return;
		}

		if ( args?.e ) {
			args.e.preventDefault();
		}

		if (
			this.isRequesting ||
			link.classList.contains( 'loading' ) ||
			link.disabled
		) {
			return;
		}

		const page = parseInt(
			link.dataset.paged || link.textContent.trim(),
			10
		);
		if ( Number.isNaN( page ) || ! this.activeCourseId ) {
			return;
		}

		this.setButtonLoadingState( link, true );
		this.loadEnrolledStudents(
			this.activeCourseId,
			page,
			this.getModalFilterValues(),
			link
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

	getModalForm() {
		const popup = this.getModalPopup();
		if ( ! popup ) {
			return null;
		}

		return popup.querySelector( ViewStudentsModal.selectors.form );
	}

	getModalFilterValues() {
		const form = this.getModalForm();
		if ( ! form ) {
			return {
				search: '',
				start_date: '',
				end_date: '',
			};
		}

		const filters = lpUtils.mergeDataWithDatForm( form, {} );

		return {
			search: filters.search || '',
			start_date: filters.start_date || '',
			end_date: filters.end_date || '',
		};
	}

	loadEnrolledStudents( courseId, paged, filters = {}, elLoading = null ) {
		const wrap = document.querySelector( ViewStudentsModal.selectors.wrap );
		if ( ! wrap || this.isRequesting ) {
			return;
		}

		this.isRequesting = true;
		if ( elLoading ) {
			this.setButtonLoadingState( elLoading, true );
		}

		wrap.innerHTML = `<div class="lp-loading">${ __(
			'Loading...',
			'learnpress'
		) }</div>`;

		const dataSend = {
			callback: {
				class: 'LearnPress\\TemplateHooks\\Admin\\AdminListStudentsEnrolled',
				method: 'render_enrolled_students',
			},
			args: {
				course_id: parseInt( courseId, 10 ) || 0,
				paged: paged,
				search: filters.search || '',
				start_date: filters.start_date || '',
				end_date: filters.end_date || '',
			},
		};

		const callBack = {
			success: ( response ) => {
				if ( response.status === 'success' ) {
					wrap.innerHTML = response.data.content;

					wrap.querySelectorAll( '.page-numbers' ).forEach(
						( link ) => {
							link.classList.add( 'lp-button' );
						}
					);
				}
			},
			error: ( err ) => {
				wrap.innerHTML = `<p>${ __(
					'Error loading students.',
					'learnpress'
				) }</p>`;
				console.error( err );
			},
			completed: () => {
				this.isRequesting = false;
				if ( elLoading ) {
					this.setButtonLoadingState( elLoading, false );
				}
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	openModal( courseId, courseTitle, elTrigger = null ) {
		const modalToolbarHtml = this.getModalToolbarHtml();

		if ( ! modalToolbarHtml ) {
			if ( elTrigger ) {
				this.setButtonLoadingState( elTrigger, false );
			}
			return;
		}

		this.activeCourseId = parseInt( courseId, 10 ) || 0;

		SweetAlert.fire( {
			title: `${ courseTitle } - ${ __(
				'Enrolled Students',
				'learnpress'
			) }`,
			html:
				modalToolbarHtml +
				`<div id="lp-modal-enrolled-wrap"><div class="lp-loading">${ __(
					'Loading...',
					'learnpress'
				) }</div></div>`,
			width: '80%',
			showConfirmButton: false,
			showCloseButton: true,
			didOpen: () => {
				this.loadEnrolledStudents(
					this.activeCourseId,
					1,
					this.getModalFilterValues(),
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
}
