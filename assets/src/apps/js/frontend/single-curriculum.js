import SingleCurriculums from './single-curriculum/index';
import lpModalOverlayCompleteItem from './show-lp-overlay-complete-item';
import courseCurriculumSkeleton from './single-curriculum/skeleton';
import lpMaterialsLoad from './material';

export default SingleCurriculums;

export const init = () => {
	wp.element.render(
		<SingleCurriculums />,
		document.getElementById( 'learn-press-course-curriculum' )
	);
};

document.addEventListener( 'DOMContentLoaded', function( event ) {
	LP.Hook.doAction( 'course-ready' );
	lpModalOverlayCompleteItem.init();

	lpMaterialsLoad( true );
	//courseCurriculumSkeleton();
	//init();
} );

const detectedElCurriculum = setInterval( function() {
	const elementCurriculum = document.querySelector( '.learnpress-course-curriculum' );
	if ( elementCurriculum ) {
		courseCurriculumSkeleton();
		clearInterval( detectedElCurriculum );
	}
}, 1 );
