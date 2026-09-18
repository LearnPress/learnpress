import assignUserCourse from './tools/assign-user-course';
import HandleSampleData from './tools/handle-sample-data';
import ResetCourseProgress from './tools/reset-course-progress';
import ResetItemProgress from './tools/reset-item-progress';

assignUserCourse();
( new HandleSampleData() ).init();
( new ResetCourseProgress() ).init();
( new ResetItemProgress() ).init();
