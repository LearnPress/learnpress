/**=
 * Course builder JS handler.
 *
 * @since 4.3.0
 * @version 1.0.0
 */
import { BuilderTabCourse } from './course-builder/builder-course/builder-tab-course.js';
import { BuilderEditCourse } from './course-builder/builder-course/builder-edit-course.js';
import { BuilderTabLesson } from './course-builder/builder-lesson/builder-tab-lesson.js';
import { BuilderEditLesson } from './course-builder/builder-lesson/builder-edit-lesson.js';
import { BuilderTabQuiz } from './course-builder/builder-quiz/builder-tab-quiz.js';
import { BuilderEditQuiz } from './course-builder/builder-quiz/builder-edit-quiz.js';
import { BuilderTabQuestion } from './course-builder/builder-question/builder-tab-question.js';
import { BuilderEditQuestion } from './course-builder/builder-question/builder-edit-question.js';
import { initElsTomSelect } from 'lpAssetsJsPath/admin/init-tom-select.js';
import { Utils } from 'lpAssetsJsPath/admin/utils-admin.js';

new BuilderTabCourse();
new BuilderEditCourse();
new BuilderTabLesson();
new BuilderEditLesson();
new BuilderTabQuiz();
new BuilderEditQuiz();
new BuilderTabQuestion();
new BuilderEditQuestion();

// Events
document.addEventListener( 'click', ( e ) => {
	initElsTomSelect();
} );

document.addEventListener( 'DOMContentLoaded', () => {
	// Sure that the TomSelect is loaded if listen can't find elements.
	initElsTomSelect();
} );

Utils.lpOnElementReady( 'select.lp-tom-select', ( e ) => {
	initElsTomSelect();
} );

window.lpFindTomSelect = initElsTomSelect;
