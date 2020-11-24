import { Component } from '@wordpress/element';

import { searchCourseContent } from './components/search';
import { Sidebar } from './components/sidebar';
import { progressBar } from './components/progress';
import { commentForm } from './components/comment';

class SingleCurriculums extends Component {
	render() {
		return (
			<>
			</>
		);
	}
}

export default SingleCurriculums;

window.addEventListener( 'DOMContentLoaded', () => {
	searchCourseContent();
	Sidebar();
	progressBar();
	commentForm();
} );
