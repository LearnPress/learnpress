import SweetAlert from 'sweetalert2';
import { __ } from '@wordpress/i18n';

export class ViewStudentsModal {
	constructor() {
		this.init();
	}

	static selectors = {
		wrap: '#lp-modal-enrolled-wrap',
		toolbar: '.lp-enrolled-students-table-toolbar--modal',
		searchInput: '#lp-modal-enrolled-search-input',
		startDateInput: '#lp-modal-enrolled-filter-start-date',
		endDateInput: '#lp-modal-enrolled-filter-end-date',
		searchBtn: '.lp-enrolled-btn-search-modal',
		clearBtn: '.lp-enrolled-btn-clear-modal',
	};

	init() {
		if ( ViewStudentsModal._loadedEvents ) {
			return;
		}

		ViewStudentsModal._loadedEvents = true;

		document.addEventListener( 'click', ( e ) => {
			const btn = e.target.closest( '.lp-btn-view-students' );
			if ( ! btn ) {
				return;
			}

			const courseId = btn.dataset.courseId;
			const courseTitle = btn.dataset.courseTitle;

			this.openModal( courseId, courseTitle );
		} );
	}

	getModalPopup() {
		return SweetAlert.getPopup ? SweetAlert.getPopup() : null;
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

	buildModalToolbarHtml() {
		return `
			<div class="lp-enrolled-students-table-toolbar lp-enrolled-students-table-toolbar--modal">
				<div class="lp-enrolled-students-table-toolbar__row lp-enrolled-students-table-toolbar__row--filters">
					<div class="lp-enrolled-students-table-toolbar__field lp-enrolled-students-table-toolbar__field--student">
						<label class="lp-enrolled-students-table-toolbar__label" for="lp-modal-enrolled-search-input">${ __(
							'Student',
							'learnpress'
						) }</label>
						<input
							id="lp-modal-enrolled-search-input"
							class="lp-enrolled-search-input lp-enrolled-students-table-toolbar__input"
							type="text"
							placeholder="${ __(
								'Enter student name or email',
								'learnpress'
							) }"
						>
					</div>
					<div class="lp-enrolled-students-table-toolbar__field lp-enrolled-students-table-toolbar__field--date">
						<label class="lp-enrolled-students-table-toolbar__label" for="lp-modal-enrolled-filter-start-date">${ __(
							'Enrolled after',
							'learnpress'
						) }</label>
						<input
							id="lp-modal-enrolled-filter-start-date"
							class="lp-enrolled-filter-start-date lp-enrolled-students-table-toolbar__input"
							type="date"
							placeholder="mm/dd/yyyy"
						>
					</div>
					<div class="lp-enrolled-students-table-toolbar__field lp-enrolled-students-table-toolbar__field--date">
						<label class="lp-enrolled-students-table-toolbar__label" for="lp-modal-enrolled-filter-end-date">${ __(
							'Enrolled before',
							'learnpress'
						) }</label>
						<input
							id="lp-modal-enrolled-filter-end-date"
							class="lp-enrolled-filter-end-date lp-enrolled-students-table-toolbar__input"
							type="date"
							placeholder="mm/dd/yyyy"
						>
					</div>
					<div class="lp-enrolled-students-table-toolbar__actions">
						<button type="button" class="button lp-enrolled-btn-search-modal">${ __(
							'Search',
							'learnpress'
						) }</button>
						<button type="button" class="button lp-enrolled-btn-clear-modal">${ __(
							'Clear Filter',
							'learnpress'
						) }</button>
					</div>
				</div>
			</div>
		`;
	}

	loadEnrolledStudents( courseId, paged, filters = {} ) {
		const wrap = document.querySelector( ViewStudentsModal.selectors.wrap );
		if ( ! wrap ) {
			return;
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
						link.addEventListener( 'click', ( ev ) => {
							ev.preventDefault();
							const page = parseInt(
								link.dataset.paged || link.textContent.trim(),
								10
							);

							if ( Number.isNaN( page ) ) {
								return;
							}

							this.loadEnrolledStudents(
								courseId,
								page,
								this.getModalFilterValues()
							);
						} );
					} );
				}
			},
			error: ( err ) => {
				wrap.innerHTML = `<p>${ __( 'Error loading students.', 'learnpress' ) }</p>`;
				console.error( err );
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

		const triggerSearch = ( e ) => {
			e.preventDefault();
			this.loadEnrolledStudents( courseId, 1, this.getModalFilterValues() );
		};

		const searchBtn = toolbar.querySelector( ViewStudentsModal.selectors.searchBtn );
		const clearBtn = toolbar.querySelector( ViewStudentsModal.selectors.clearBtn );
		const inputs = toolbar.querySelectorAll(
			`${ ViewStudentsModal.selectors.searchInput }, ${ ViewStudentsModal.selectors.startDateInput }, ${ ViewStudentsModal.selectors.endDateInput }`
		);

		if ( searchBtn ) {
			searchBtn.addEventListener( 'click', triggerSearch );
		}

		if ( clearBtn ) {
			clearBtn.addEventListener( 'click', ( e ) => {
				e.preventDefault();

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

				this.loadEnrolledStudents( courseId, 1, this.getModalFilterValues() );
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

	openModal( courseId, courseTitle ) {
		SweetAlert.fire( {
			title: `${ courseTitle } - ${ __( 'Enrolled Students', 'learnpress' ) }`,
			html:
				this.buildModalToolbarHtml() +
				'<div id="lp-modal-enrolled-wrap"><div class="lp-loading">Loading...</div></div>',
			width: '80%',
			showConfirmButton: false,
			showCloseButton: true,
			didOpen: () => {
				this.bindModalToolbarEvents( courseId );
				this.loadEnrolledStudents( courseId, 1, this.getModalFilterValues() );
			},
		} );
	}
}
