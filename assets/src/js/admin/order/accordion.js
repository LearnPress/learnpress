const accordion = () => {
	const listCourseEls = Array.prototype.slice.call( document.querySelectorAll( '.lp-course-sold-out' ) );
	if ( ! listCourseEls || listCourseEls.length < 1 ) {
		return;
	}

	listCourseEls.map( ( listCourseEl ) => {
		const titleCourse = listCourseEl.querySelector( '.lp-course-sold-out__title' );
		const contentCourse = listCourseEl.querySelector( '.lp-course-sold-out__list' );
		if ( ! titleCourse || ! contentCourse ) {
			return;
		}

		titleCourse.addEventListener( 'click', () => {
			contentCourse.classList.toggle( 'is-active' );
		} );
	} );
};

document.addEventListener( 'DOMContentLoaded', () => {
	accordion();
} );
