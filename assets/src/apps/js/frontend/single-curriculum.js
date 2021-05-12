const $ = jQuery;
import SingleCurriculums from './single-curriculum/index';
import lpModalOverlayCompleteItem from './show-lp-overlay-complete-item';
import scrollToItemCurrent from './single-curriculum/scrolltoitem';

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
	scrollToItemCurrent.init();
	//init();
} );
