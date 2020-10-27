import ModalCourseItems from '../store/modal-course-items';
import CourseSection from '../store/course-section';
import i18n from '../store/i18n';
import getters from '../getters/course';
import mutations from '../mutations/course';
import actions from '../actions/course';

const $ = window.jQuery;
const Course = function Course(data) {
    var state = $.extend({}, data.root);

    state.status = 'success';
    state.heartbeat = true;
    state.countCurrentRequest = 0;

    return {
        state: state,
        getters: getters,
        mutations: mutations,
        actions: actions,
        modules: {
            ci: ModalCourseItems(data),
            i18n: i18n(data.i18n),
            ss: CourseSection(data)
        }
    }
};

export default Course;