(function ($, Vue) {
    $(document).ready(function () {
        Vue.component('lp-curriculum', {
            template: '#tmpl-lp-course-curriculum'
        });

        var CourseEditor = new Vue({
            el: '#course-editor-v2',
            template: '#tmpl-lp-course-editor'
        });
    });
})(jQuery, window.Vue);