import HTTP from './http';
import CourseStore from './store/course';

window.$Vue = window.$Vue || Vue;
window.$Vuex = window.$Vuex || Vuex;

const $ = window.jQuery;

/**
 * Init app.
 *
 * @since 3.0.0
 */
$(document).ready(function () {

    window.LP_Curriculum_Store = new $Vuex.Store(CourseStore(lpAdminCourseEditorSettings));
    HTTP({ns: 'LPCurriculumRequest', store: LP_Curriculum_Store});

    setTimeout(() => {
        window.LP_Course_Editor = new $Vue({
            el: '#admin-editor-lp_course',
            template: '<lp-course-editor></lp-course-editor>'
        });
    }, 100)
});