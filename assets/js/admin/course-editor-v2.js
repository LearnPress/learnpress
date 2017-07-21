(function ($, Vue, data) {
    Vue.component('lp-curriculum', {
        template: '#tmpl-lp-course-curriculum'
    });

    Vue.component('lp-course-editor', {
        template: '#tmpl-lp-course-editor'
    });

    Vue.component('lp-list-sections', {
        template: '#tmpl-lp-list-sections'
    });

    $(document).ready(function () {
        var Root = new Vue({
            el: '#course-editor-v2',
            template: '<lp-course-editor></lp-course-editor>'
        });
    });
})(jQuery, window.Vue, lq_course_editor);