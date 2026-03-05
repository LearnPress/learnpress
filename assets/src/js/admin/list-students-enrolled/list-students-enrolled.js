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
        elForm: '#lp-enrolled-students-form',
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
        if ( elLPTarget ) {
            const dataSend = window.lpAJAXG.getDataSetCurrent( elLPTarget );
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
                callBack: this.searchStudents.name,
                checkIsEventEnter: true,
            },
            {
                selector: ListStudentsEnrolled.selectors.elCourseNameInput,
                class: this,
                callBack: this.searchStudents.name,
                checkIsEventEnter: true,
            },
            {
                selector: ListStudentsEnrolled.selectors.elStartDateInput,
                class: this,
                callBack: this.searchStudents.name,
                checkIsEventEnter: true,
            },
            {
                selector: ListStudentsEnrolled.selectors.elEndDateInput,
                class: this,
                callBack: this.searchStudents.name,
                checkIsEventEnter: true,
            },
        ] );
    }

    setButtonLoadingState( btn, isLoading ) {
        if ( ! btn ) {
            return;
        }

        lpUtils.lpSetLoadingEl( btn, isLoading ? 1 : 0 );
        btn.disabled = !! isLoading;
    }

    getToolbarForm() {
        return this.elContainer?.querySelector(
            ListStudentsEnrolled.selectors.elForm
        );
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

        const elForm = this.getToolbarForm();
        const elLPTarget = this.elContainer.querySelector(
            ListStudentsEnrolled.selectors.elLPTarget
        );
        if ( ! elLPTarget || ! elForm ) {
            return;
        }

        this.setButtonLoadingState( btn, true );

        const dataSend = window.lpAJAXG.getDataSetCurrent( elLPTarget );
        dataSend.args = this.getFilterArgsFromForm( elForm, dataSend.args || {} );
        dataSend.args.paged = 1;
        window.lpAJAXG.setDataSetCurrent( elLPTarget, dataSend );

        this.reloadContent( elLPTarget, dataSend, btn );
    }

    /**
     * Clear all filters and reload default data.
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

        const elForm = this.getToolbarForm();
        const elLPTarget = this.elContainer.querySelector(
            ListStudentsEnrolled.selectors.elLPTarget
        );
        if ( ! elLPTarget || ! elForm ) {
            return;
        }

        this.setButtonLoadingState( btn, true );

        elForm.reset();
        this.syncCourseIdFromName( elForm );

        const dataSend = window.lpAJAXG.getDataSetCurrent( elLPTarget );
        dataSend.args = lpUtils.mergeDataWithDatForm( elForm, dataSend.args || {} );
        dataSend.args.paged = 1;
        window.lpAJAXG.setDataSetCurrent( elLPTarget, dataSend );

        this.reloadContent( elLPTarget, dataSend, btn );
    }

    /**
     * Shared reload helper: loading indicator + AJAX fetch.
     */
    reloadContent( elLPTarget, dataSend, btn = null ) {
        this.isRequesting = true;
        window.lpAJAXG.showHideLoading( elLPTarget, 1 );

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
                window.lpAJAXG.showHideLoading( elLPTarget, 0 );
                this.setButtonLoadingState( btn, false );
            },
        };

        window.lpAJAXG.fetchAJAX( dataSend, callBack );
    }
}

// Auto-initialize when DOM is available (for standalone page load).
const listStudentsEnrolled = new ListStudentsEnrolled();

document.addEventListener( 'DOMContentLoaded', () => {
    listStudentsEnrolled.init();
} );

// Also listen for AJAX content loaded (for profile tabs loaded dynamically).
document.addEventListener( 'lp_load_ajax_element_done', () => {
    listStudentsEnrolled.init();
} );
