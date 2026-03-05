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
        elLPTarget: '.lp-target',
        elCourseNameInput: '.lp-enrolled-filter-course-name',
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
        if (!this.elContainer) {
            return;
        }

        const elLPTarget = this.elContainer.querySelector(
            ListStudentsEnrolled.selectors.elLPTarget
        );
        if (elLPTarget) {
            const dataSend = window.lpAJAXG.getDataSetCurrent(elLPTarget);
            if (dataSend && dataSend.args) {
                this.instructorId = dataSend.args.instructor_id;
            }
        }

        this.events();
    }

    events() {
        if (ListStudentsEnrolled._loadedEvents) {
            return;
        }

        ListStudentsEnrolled._loadedEvents = this;

        // Search/Clear button click.
        lpUtils.eventHandlers('click', [
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
        ]);

        // Search on Enter key.
        lpUtils.eventHandlers('keydown', [
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
        ]);
    }

    setButtonLoadingState(btn, isLoading) {
        if (!btn) {
            return;
        }

        lpUtils.lpSetLoadingEl(btn, isLoading ? 1 : 0);
        btn.disabled = !!isLoading;
    }

    /**
     * Search students: update args.search, re-fetch.
     */
    searchStudents(args) {
        const { e } = args;
        if (e) {
            e.preventDefault();
        }
        const btn = args?.target?.closest(
            ListStudentsEnrolled.selectors.elBtnSearch
        );
        if (btn) {
            if (this.isRequesting || btn.classList.contains('loading') || btn.disabled) {
                return;
            }
        } else if (this.isRequesting) {
            return;
        }

        const elInput = this.elContainer.querySelector(
            ListStudentsEnrolled.selectors.elSearchInput
        );
        const elCourseNameInput = this.elContainer.querySelector(
            ListStudentsEnrolled.selectors.elCourseNameInput
        );
        const elStartDateInput = this.elContainer.querySelector(
            ListStudentsEnrolled.selectors.elStartDateInput
        );
        const elEndDateInput = this.elContainer.querySelector(
            ListStudentsEnrolled.selectors.elEndDateInput
        );
        const elLPTarget = this.elContainer.querySelector(
            ListStudentsEnrolled.selectors.elLPTarget
        );
        if (!elLPTarget || !elInput) {
            return;
        }

        this.setButtonLoadingState(btn, true);

        const dataSend = window.lpAJAXG.getDataSetCurrent(elLPTarget);
        dataSend.args.course_id = 0;
        dataSend.args.course_name = elCourseNameInput?.value.trim() || '';
        dataSend.args.search = elInput.value.trim();
        dataSend.args.start_date = elStartDateInput?.value || '';
        dataSend.args.end_date = elEndDateInput?.value || '';
        dataSend.args.paged = 1;
        window.lpAJAXG.setDataSetCurrent(elLPTarget, dataSend);

        this.reloadContent(elLPTarget, dataSend, btn);
    }


    /**
     * Clear all filters and reload default data.
     */
    clearFilters(args) {
        const { e } = args;
        if (e) {
            e.preventDefault();
        }
        const btn = args?.target?.closest(
            ListStudentsEnrolled.selectors.elBtnClear
        );
        if (btn) {
            if (this.isRequesting || btn.classList.contains('loading') || btn.disabled) {
                return;
            }
        } else if (this.isRequesting) {
            return;
        }

        const elCourseNameInput = this.elContainer.querySelector(
            ListStudentsEnrolled.selectors.elCourseNameInput
        );
        const elSearchInput = this.elContainer.querySelector(
            ListStudentsEnrolled.selectors.elSearchInput
        );
        const elStartDateInput = this.elContainer.querySelector(
            ListStudentsEnrolled.selectors.elStartDateInput
        );
        const elEndDateInput = this.elContainer.querySelector(
            ListStudentsEnrolled.selectors.elEndDateInput
        );
        const elLPTarget = this.elContainer.querySelector(
            ListStudentsEnrolled.selectors.elLPTarget
        );
        if (!elLPTarget) {
            return;
        }

        this.setButtonLoadingState(btn, true);

        if (elCourseNameInput) {
            elCourseNameInput.value = '';
        }
        if (elSearchInput) {
            elSearchInput.value = '';
        }
        if (elStartDateInput) {
            elStartDateInput.value = '';
        }
        if (elEndDateInput) {
            elEndDateInput.value = '';
        }

        const dataSend = window.lpAJAXG.getDataSetCurrent(elLPTarget);
        dataSend.args.course_id = 0;
        dataSend.args.course_name = '';
        dataSend.args.search = '';
        dataSend.args.start_date = '';
        dataSend.args.end_date = '';
        dataSend.args.paged = 1;
        window.lpAJAXG.setDataSetCurrent(elLPTarget, dataSend);

        this.reloadContent(elLPTarget, dataSend, btn);
    }
    /**
     * Shared reload helper — loading indicator + AJAX fetch.
     */
    reloadContent(elLPTarget, dataSend, btn = null) {
        this.isRequesting = true;
        window.lpAJAXG.showHideLoading(elLPTarget, 1);

        const callBack = {
            success: (response) => {
                const { status, data } = response;
                if ('success' === status) {
                    elLPTarget.innerHTML = data.content;
                }
            },
            error: (error) => console.error(error),
            completed: () => {
                this.isRequesting = false;
                window.lpAJAXG.showHideLoading(elLPTarget, 0);
                this.setButtonLoadingState(btn, false);
            },
        };

        window.lpAJAXG.fetchAJAX(dataSend, callBack);
    }
}

// Auto-initialize when DOM is available (for standalone page load).
const listStudentsEnrolled = new ListStudentsEnrolled();

document.addEventListener('DOMContentLoaded', () => {
    listStudentsEnrolled.init();
});

// Also listen for AJAX content loaded (for profile tabs loaded dynamically).
document.addEventListener('lp_load_ajax_element_done', () => {
    listStudentsEnrolled.init();
});
