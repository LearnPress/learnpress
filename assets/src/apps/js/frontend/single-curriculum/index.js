import { Component } from '@wordpress/element';

import { searchCourseContent } from './components/search';
import { Sidebar } from './components/sidebar';
import { progressBar } from './components/progress';
import { commentForm } from './components/comment';
import './components/compatible';

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
	LP.Hook.doAction( 'lp-compatible-builder' );

	searchCourseContent();
	Sidebar();
	progressBar();
	commentForm();
} );
