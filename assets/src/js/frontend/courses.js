;(function ($) {

    const searchCourseHandler = function (event) {
        event.preventDefault();

        $.ajax({
            url: 'http://localhost/learnpress/dev/courses-2/',
            data: {
                s: $(this).find('input[name="s"]').val()
            },
            type: 'post',
            success: (response) => {
                var newEl = $(response).contents().find('.learn-press-courses');

                if (newEl.length) {
                    $('.learn-press-courses').replaceWith(newEl)
                } else {
                    $('.learn-press-courses').html('')
                }

            }
        })
    };

    const switchCoursesLayoutHandler = function (event) {
        var $target;//= $(this).data('target');

        if(!$target){
            var $parent = $(this).parent();
            while(!$target || !$target.length){
               $target = $parent.find('.learn-press-courses');
               $parent = $parent.parent();
               console.log('X')
            }

            $(this).data('target', $target);
        }

        $target.attr('data-layout', this.value)

    };

    $(document).ready(function () {
        $('.search-courses').on('submit', searchCourseHandler);
        $('input[name="lp-switch-layout-btn"]').on('change', switchCoursesLayoutHandler)
    })

})(jQuery);