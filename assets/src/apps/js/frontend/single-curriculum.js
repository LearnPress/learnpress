const $ = jQuery;
import SingleCurriculums from './single-curriculum/index';

export default SingleCurriculums;

export const init = () => {
	wp.element.render(
		<SingleCurriculums />,
		document.getElementById( 'learn-press-course-curriculum' )
	);
};

document.addEventListener( 'DOMContentLoaded', function( event ) {
	LP.Hook.doAction( 'course-ready' );
	//init();
} );
