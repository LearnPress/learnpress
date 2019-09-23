import SingleCourse from './single-course/index';

export function init() {
    console.log('Init single course')
}

jQuery(($) => {

    $('.course-curriculum').scroll(()=>{
        var $el = $('#section-section-1-549 .section-header');
        console.log($el.css('top'), $el.scrollTop());
    })
    // wp.element.render(
    //     <SingleCourse />,
    //     $('.entry-content')[0]
    // )
})
