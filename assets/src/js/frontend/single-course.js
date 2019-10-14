import SingleCourse from './single-course/index';

const $ = jQuery;

export function formatDuration(seconds) {
    let html;
    let x, d;
    const day_in_seconds = 3600 * 24;


    if (seconds > day_in_seconds) {
        d = (seconds - seconds % day_in_seconds) / day_in_seconds;
        seconds = seconds % day_in_seconds;
    } else if (seconds == day_in_seconds) {
        return '24:00';
    }

    x = (new Date(seconds * 1000).toUTCString()).match(/\d{2}:\d{2}:\d{2}/)[0].split(':');

    if (x[2] === '00') {
        x.splice(2, 1);
    }

    if (x[0] === '00') {
        x[0] = 0;
    }

    if (d) {
        x[0] = parseInt(x[0]) + d * 24;
    }

    html = x.join(':');

    return html;
}

const toggleSidebarHandler = function toggleSidebarHandler(event) {
    LP.localStorage.set('sidebar-toggle', event.target.checked);
};

export {toggleSidebarHandler};

const createCustomScrollbar = function (element) {
    [].map.call(arguments, (element) => {
        $(element).each(function () {
            $(this)
                .addClass('scrollbar-light')
                .css({
                    opacity: 1
                })
                .scrollbar({
                    scrollx: false
                })
                .parent()
                .css({
                    position: 'absolute',
                    top: 0,
                    bottom: 0,
                    width: '100%',
                    opacity: 1
                })
        });
    });
};

const AjaxSearchCourses = function (el) {
    var $form = $(el);
    var submit = function () {

        wp.apiFetch({
            url: '?s='+$(this).find('input[name="s"]').val(),
        });

        return false;
    }

    $form.on('submit', submit);
}

$(window).load(() => {
    var timerClearScroll;
    var $curriculum = $('#learn-press-course-curriculum');

    $curriculum.scroll(lodash.throttle(function () {
        var $self = $(this);

        $self.addClass('scrolling');
        timerClearScroll && clearTimeout(timerClearScroll);
        timerClearScroll = setTimeout(() => {
            $self.removeClass('scrolling');
        }, 1000);

    }, 500));

    $curriculum.find('.section-desc').each((i, el) => {
        const a = $('<span class="show-desc"></span>').on('click', () => {
            b.toggleClass('c');
        });
        const b = $(el).siblings('.section-title').append(a)
    });

    $('#sidebar-toggle').on('change', toggleSidebarHandler).prop('checked', LP.localStorage.get('sidebar-toggle'));

    new AjaxSearchCourses($('#search-course'));

    createCustomScrollbar($curriculum.find('.curriculum-scrollable'), $('#popup-content').find('.content-item-scrollable'));


    LP.Hook.doAction('course-ready');

    console.log('BBBB')
    // if (window.location.hash) {
    //     $('.content-item-scrollable:last').scrollTo($(window.location.hash));
    // }
})
