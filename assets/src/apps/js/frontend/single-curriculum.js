const $ = jQuery;

import SingleCurriculums from './single-curriculum/index';

export default SingleCurriculums;

export const init = () => {
	wp.element.render(
		<SingleCurriculums />,
	);
};

$( window ).on( 'load', () => {
	LP.Hook.doAction( 'course-ready' );
} );
