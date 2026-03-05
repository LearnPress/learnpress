import SweetAlert from 'sweetalert2';
import { __ } from '@wordpress/i18n';
import * as lpUtils from 'lpAssetsJsPath/utils.js';

export class ViewStudentsModal {
	constructor() {
		this.isRequesting = false;
		this.init();
	}

	static selectors = {
		wrap: '#lp-modal-enrolled-wrap',
		form: '#lp-modal-enrolled-form',
		toolbarTemplate: '#lp-tmpl-enrolled-students-toolbar-modal',
		toolbar: '.lp-enrolled-students-table-toolbar--modal',
		searchInput: '#lp-modal-enrolled-search-input',
		startDateInput: '#lp-modal-enrolled-filter-start-date',
		endDateInput: '#lp-modal-enrolled-filter-end-date',
		searchBtn: '.lp-enrolled-btn-search-modal',
		clearBtn: '.lp-enrolled-btn-clear-modal',
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

		document.addEventListener( 'click', ( e ) => {
			const btn = e.target.closest( '.lp-btn-view-students' );
			if (
				! btn ||
				this.isRequesting ||
				btn.classList.contains( 'loading' ) ||
				btn.disabled
			) {
				return;
			}

			const courseId = btn.dataset.courseId;
			const courseTitle = btn.dataset.courseTitle;

			this.setButtonLoadingState( btn, true );
			this.openModal( courseId, courseTitle, btn );
		} );
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

		wrap.innerHTML = '<div class="lp-loading">Loading...</div>';

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

	bindModalToolbarEvents( courseId ) {
		const form = this.getModalForm();
		if ( ! form ) {
			return;
		}

		const toolbar = form.matches( ViewStudentsModal.selectors.toolbar )
			? form
			: form.querySelector( ViewStudentsModal.selectors.toolbar );
		if ( ! toolbar ) {
			return;
		}

		const wrap = document.querySelector( ViewStudentsModal.selectors.wrap );
		if ( ! wrap ) {
			return;
		}

		const handleSearch = ( e ) => {
			e.preventDefault();
			const searchBtn = toolbar.querySelector(
				ViewStudentsModal.selectors.searchBtn
			);

			if (
				! searchBtn ||
				this.isRequesting ||
				searchBtn.classList.contains( 'loading' ) ||
				searchBtn.disabled
			) {
				return;
			}

			this.setButtonLoadingState( searchBtn, true );
			this.loadEnrolledStudents(
				courseId,
				1,
				this.getModalFilterValues(),
				searchBtn
			);
		};

		toolbar.addEventListener( 'click', ( e ) => {
			const searchBtn = e.target.closest(
				ViewStudentsModal.selectors.searchBtn
			);
			if ( searchBtn ) {
				handleSearch( e );
				return;
			}

			const clearBtn = e.target.closest(
				ViewStudentsModal.selectors.clearBtn
			);
			if ( ! clearBtn ) {
				return;
			}

			e.preventDefault();
			if (
				this.isRequesting ||
				clearBtn.classList.contains( 'loading' ) ||
				clearBtn.disabled
			) {
				return;
			}

			form.reset();
			this.setButtonLoadingState( clearBtn, true );
			this.loadEnrolledStudents(
				courseId,
				1,
				this.getModalFilterValues(),
				clearBtn
			);
		} );

		toolbar.addEventListener( 'keydown', ( e ) => {
			if ( e.key !== 'Enter' ) {
				return;
			}

			if (
				! e.target.closest(
					`${ ViewStudentsModal.selectors.searchInput }, ${ ViewStudentsModal.selectors.startDateInput }, ${ ViewStudentsModal.selectors.endDateInput }`
				)
			) {
				return;
			}

			handleSearch( e );
		} );

		wrap.addEventListener( 'click', ( e ) => {
			const link = e.target.closest( '.page-numbers' );
			if ( ! link ) {
				return;
			}

			e.preventDefault();
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
			if ( Number.isNaN( page ) ) {
				return;
			}

			this.setButtonLoadingState( link, true );
			this.loadEnrolledStudents(
				courseId,
				page,
				this.getModalFilterValues(),
				link
			);
		} );
	}

	openModal( courseId, courseTitle, elTrigger = null ) {
		const modalToolbarHtml = this.getModalToolbarHtml();

		if ( ! modalToolbarHtml ) {
			if ( elTrigger ) {
				this.setButtonLoadingState( elTrigger, false );
			}
			return;
		}

		SweetAlert.fire( {
			title: `${ courseTitle } - ${ __(
				'Enrolled Students',
				'learnpress'
			) }`,
			html:
				modalToolbarHtml +
				'<div id="lp-modal-enrolled-wrap"><div class="lp-loading">Loading...</div></div>',
			width: '80%',
			showConfirmButton: false,
			showCloseButton: true,
			didOpen: () => {
				this.bindModalToolbarEvents( courseId );
				this.loadEnrolledStudents(
					courseId,
					1,
					this.getModalFilterValues(),
					elTrigger
				);
			},
			didClose: () => {
				if ( elTrigger ) {
					this.setButtonLoadingState( elTrigger, false );
				}
			},
		} );
	}
}
