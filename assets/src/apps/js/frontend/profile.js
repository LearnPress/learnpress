import courseTab from './profile/course-tab';
import courseStatistics from './profile/statistic';
import recoverOrder from './profile/order-recover';
import Avatar from './profile/avatar';

document.addEventListener( 'DOMContentLoaded', function( event ) {
	courseTab();
	courseStatistics();
	recoverOrder();
} );

if ( document.getElementById( 'learnpress-avatar-upload' ) ) {
	wp.element.render( <Avatar />, document.getElementById( 'learnpress-avatar-upload' ) );
}
