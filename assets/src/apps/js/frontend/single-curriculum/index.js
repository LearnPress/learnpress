const $ = jQuery;
import { Component } from '@wordpress/element';
import { searchCourseContent } from './components/search';
import { Sidebar } from './components/sidebar';
import { progressBar } from './components/progress';
import { commentForm } from './components/comment';
import { itemsProgress } from './components/items-progress';

import './components/compatible';

class SingleCurriculums extends Component {
	checkCourseDurationExpire() {
		const elCourseItemIsBlockeds = document.getElementsByName( 'lp-course-timestamp-remaining' );

		if ( elCourseItemIsBlockeds.length ) {
			const elCourseItemIsBlocked = elCourseItemIsBlockeds[ 0 ];
			const timeDuration = elCourseItemIsBlocked.value; // value is second

			if ( timeDuration < ( 60 * 60 * 24 ) ) { // compare with 1 day
				setTimeout(
					function() {
						window.location.reload( true );
					},
					timeDuration * 1000
				);
			}
		}
	}
	render() {
		return (
			<div></div>
		);
	}
}

export default SingleCurriculums;

document.addEventListener( 'DOMContentLoaded', () => {
	LP.Hook.doAction( 'lp-compatible-builder' );

	searchCourseContent();
	Sidebar();
	progressBar();
	//commentForm();
	itemsProgress();

	// Check duration expire of course
	const singleCurriculums = new SingleCurriculums();
	singleCurriculums.checkCourseDurationExpire();
} );
