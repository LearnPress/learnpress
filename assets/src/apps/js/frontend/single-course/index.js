/**
 * @deprecated 4.5.1 not imported (assets/src/apps/js/frontend/single-course.js is deprecated).
 * Component SingleCourse is empty, Sidebar() is called on page item by single-curriculum/index.js.
 */
import { Component } from '@wordpress/element';
import Quiz from '@learnpress/quiz';

import './store';

import { Sidebar } from '../single-curriculum/components/sidebar'; // Use toggle in Curriculum tab.

class SingleCourse extends Component {
	render() {
		return (
			<>
			</>
		);
	}
}

export default SingleCourse;

function run() {
	Sidebar();
}

document.addEventListener( 'DOMContentLoaded', () => {
	run();
} );

