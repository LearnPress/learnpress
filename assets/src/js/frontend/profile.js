import courseTab from './profile/course-tab';
import courseStatistics from './profile/statistic';
import recoverOrder from './profile/order-recover';
import profileCoverImage from './profile/cover-image';
import profileAvatarImage from './profile/avatar';
import profileQuizTab from './profile/quiz';
import { ViewStudentsModal } from '../admin/courses/view-students-modal';

profileCoverImage();
profileQuizTab();
courseStatistics();
recoverOrder();
new ViewStudentsModal();

document.addEventListener( 'DOMContentLoaded', function( event ) {
	courseTab();
} );

if ( document.getElementById( 'learnpress-avatar-upload' ) ) {
	profileAvatarImage();
}
