import courseTab from './profile/course-tab';
import courseStatistics from './profile/statistic';
import recoverOrder from './profile/order-recover';
import Avatar from './profile/avatar';
import CourseList from './profile/course-list';
import profileCoverImage from './profile/cover-image';

profileCoverImage();

document.addEventListener( 'DOMContentLoaded', function( event ) {
	courseTab();
	courseStatistics();
	recoverOrder();
	CourseList();
} );

if ( document.getElementById( 'learnpress-avatar-upload' ) ) {
	wp.element.render( <Avatar />, document.getElementById( 'learnpress-avatar-upload' ) );
}
