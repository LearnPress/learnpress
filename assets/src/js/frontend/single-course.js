import SingleCourse from './single-course/index';

const $ = jQuery;
const {
    debounce
} = lodash;
const {_x} = wp.i18n;

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
    LP.Cookies.set('sidebar-toggle', event.target.checked);
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
    var $ul = $('<ul class="search-results"></ul>').appendTo($form);
    var $input = $form.find('input[name="s"]');
    var paged = 1;

    var submit = async function (e) {
        e.preventDefault();
        const response = await wp.apiFetch({
            path: 'lp/v1/courses/search?s=' + $input.val() + '&page=' + paged,
        });

        const {
            courses,
            num_pages,
            page
        } = response.results;
        $ul.html('');

        if (courses.length) {
            courses.map((course) => {
                $ul.append(`<li class="search-results__item">
                    <a href="${course.url}">
                    ` + (course.thumbnail.small ? `<img src="${course.thumbnail.small}" />` : '') + `
                        <h4 class="search-results__item-title">${course.title}</h4>
                        <span class="search-results__item-author">${course.author}</span>
                        ${course.price_html}
                        </a>
                    </li>`);
            });

            if (num_pages > 1) {
                $ul.append(`<li class="search-results__pagination">
                  ` + ([...Array(num_pages).keys()].map((i) => {
                        return i === paged - 1 ? '<span>' + (i + 1) + '</span>' : '<a data-page="' + (i + 1) + '">' + (i + 1) + '</a>'
                    })).join('') + `
                </li>`)
            }
        } else {
            $ul.append('<li class="search-results__not-found">' + _x('No course found!', 'ajax search course not found', 'learnpress') + '</li>');
        }

        $form.addClass('searching');


        return false;
    };

    $input.on('keyup', debounce(function (e) {
        paged = 1;
        if (e.target.value.length < 3) {
            return;
        }
        submit(e)
    }, 300));
    $form.on('click', '.clear', () => {
        $form.removeClass('searching');
        $input.val('')
    }).on('click', '.search-results__pagination a', (e) => {
        e.preventDefault();
        paged = $(e.target).data('page');
        submit(e);
    })
}

$(window).on('load', () => {
    var $popup = $('#popup-course');
    var timerClearScroll;
    var $curriculum = $('#learn-press-course-curriculum');

    // Popup only
    if ($popup.length) {

        $curriculum.scroll(lodash.throttle(function () {
            var $self = $(this);

            $self.addClass('scrolling');
            timerClearScroll && clearTimeout(timerClearScroll);
            timerClearScroll = setTimeout(() => {
                $self.removeClass('scrolling');
            }, 1000);

        }, 500));

        $('#sidebar-toggle').on('change', toggleSidebarHandler);

        new AjaxSearchCourses($popup.find('.search-course'));

        createCustomScrollbar($curriculum.find('.curriculum-scrollable'), $('#popup-content').find('.content-item-scrollable'));

        LP.toElement('.course-item.current', {container: '.curriculum-scrollable:eq(1)', offset: 200})
    }

    $curriculum.find('.section-desc').each((i, el) => {
        const a = $('<span class="show-desc"></span>').on('click', () => {
            b.toggleClass('c');
        });
        const b = $(el).siblings('.section-title').append(a)
    });

    LP.Hook.doAction('course-ready');

    // if (window.location.hash) {
    //     $('.content-item-scrollable:last').scrollTo($(window.location.hash));
    // }
})
