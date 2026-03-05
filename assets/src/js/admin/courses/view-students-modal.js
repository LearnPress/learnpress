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

	getModalFilterValues() {
		const popup = this.getModalPopup();
		if ( ! popup ) {
			return {
				search: '',
				start_date: '',
				end_date: '',
			};
		}

		const searchInput = popup.querySelector(
			ViewStudentsModal.selectors.searchInput
		);
		const startDateInput = popup.querySelector(
			ViewStudentsModal.selectors.startDateInput
		);
		const endDateInput = popup.querySelector(
			ViewStudentsModal.selectors.endDateInput
		);

		return {
			search: searchInput?.value.trim() || '',
			start_date: startDateInput?.value || '',
			end_date: endDateInput?.value || '',
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

					wrap.querySelectorAll( '.page-numbers' ).forEach( ( link ) => {
						link.classList.add( 'lp-button' );

						link.addEventListener( 'click', ( ev ) => {
							ev.preventDefault();
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
					} );
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
		const popup = this.getModalPopup();
		if ( ! popup ) {
			return;
		}

		const toolbar = popup.querySelector( ViewStudentsModal.selectors.toolbar );
		if ( ! toolbar ) {
			return;
		}

		const searchBtn = toolbar.querySelector( ViewStudentsModal.selectors.searchBtn );
		const clearBtn = toolbar.querySelector( ViewStudentsModal.selectors.clearBtn );
		const inputs = toolbar.querySelectorAll(
			`${ ViewStudentsModal.selectors.searchInput }, ${ ViewStudentsModal.selectors.startDateInput }, ${ ViewStudentsModal.selectors.endDateInput }`
		);

		const triggerSearch = ( e ) => {
			e.preventDefault();

			if (
				searchBtn &&
				( this.isRequesting ||
					searchBtn.classList.contains( 'loading' ) ||
					searchBtn.disabled )
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

		if ( searchBtn ) {
			searchBtn.addEventListener( 'click', triggerSearch );
		}

		if ( clearBtn ) {
			clearBtn.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				if (
					this.isRequesting ||
					clearBtn.classList.contains( 'loading' ) ||
					clearBtn.disabled
				) {
					return;
				}

				const searchInput = toolbar.querySelector(
					ViewStudentsModal.selectors.searchInput
				);
				const startDateInput = toolbar.querySelector(
					ViewStudentsModal.selectors.startDateInput
				);
				const endDateInput = toolbar.querySelector(
					ViewStudentsModal.selectors.endDateInput
				);

				if ( searchInput ) {
					searchInput.value = '';
				}
				if ( startDateInput ) {
					startDateInput.value = '';
				}
				if ( endDateInput ) {
					endDateInput.value = '';
				}

				this.setButtonLoadingState( clearBtn, true );
				this.loadEnrolledStudents(
					courseId,
					1,
					this.getModalFilterValues(),
					clearBtn
				);
			} );
		}

		inputs.forEach( ( input ) => {
			input.addEventListener( 'keydown', ( e ) => {
				if ( e.key === 'Enter' ) {
					triggerSearch( e );
				}
			} );
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
			title: `${ courseTitle } - ${ __( 'Enrolled Students', 'learnpress' ) }`,
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
