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

