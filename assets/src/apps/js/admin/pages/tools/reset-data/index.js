import ResetCourse from './course';

const resetData = () => {
	if ( document.querySelectorAll( '#learn-press-reset-course-users' ).length > 0 ) {
		wp.element.render( <ResetCourse />, [ ...document.querySelectorAll( '#learn-press-reset-course-users' ) ][ 0 ] );
	}
};
export default resetData;
