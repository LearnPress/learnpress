import { Component } from '@wordpress/element';
import Quiz from '@learnpress/quiz';

import './store';

import { searchCourseContent } from './components/search';
// import { commentForm } from './components/comment';

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
	searchCourseContent();
	// commentForm();
}

window.addEventListener( 'DOMContentLoaded', () => {
	run();
} );

