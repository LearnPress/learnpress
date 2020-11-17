import { Component } from '@wordpress/element';

import { searchCourseContent } from './components/search';
import { Sidebar } from './components/sidebar';
import { progressBar } from './components/progress';

class SingleCourse extends Component {
	render() {
		return (
			<>
			</>
		);
	}
}

export default SingleCourse;

window.addEventListener( 'DOMContentLoaded', () => {
	searchCourseContent();
	Sidebar();
	progressBar();
} );
