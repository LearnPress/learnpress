import courseTab from './profile/course-tab';
import courseStatistics from './profile/statistic';
import recoverOrder from './profile/order-recover';
import Avatar from './profile/avatar';
import CourseList from './profile/course-list';
import profileCoverImage from './profile/cover-image';

profileCoverImage();

const mbCurrentTab = () => {
	const currentTabElement = document.querySelector( '.mb-current-tab' );
	const tabsNav = document.querySelector( '.learn-press-tabs__nav' );
	if ( ! currentTabElement || ! tabsNav ) {
		return;
	}

	currentTabElement.addEventListener( 'click', () => {
		tabsNav.classList.toggle( 'open' );
	} );
};

document.addEventListener( 'DOMContentLoaded', function( event ) {
	courseTab();
	courseStatistics();
	recoverOrder();
	CourseList();
	// mbCurrentTab();
} );

if ( document.getElementById( 'learnpress-avatar-upload' ) ) {
	wp.element.render( <Avatar />, document.getElementById( 'learnpress-avatar-upload' ) );
}
