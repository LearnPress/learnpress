import SingleCourse from './single-course/index';

export function init() {
}

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


jQuery(($) => {
    var t;

    $('.course-curriculum').scroll(lodash.throttle(function(){
        var $self = $(this),
            $el = $('#section-section-1-549 .section-header');
        $self.addClass('scrolling');
        t && clearTimeout(t);
        t = setTimeout(() => {
            $self.removeClass('scrolling');
        }, 1000)
    }, 500)).find('.section-desc').each((i, el) => {
        const a = $('<span class="show-desc"></span>').on('click', () => {
            b.toggleClass('c');
        });
        const b = $(el).siblings('.section-title').append(a)
    });

    $(document).on('change', '#sidebar-toggle', LP.singleCourse.toggleSidebarHandler);

    $('#sidebar-toggle').prop('checked', LP.localStorage.get('sidebar-toggle'));

    // wp.element.render(
    //     <SingleCourse />,
    //     $('.entry-content')[0]
    // )
})
