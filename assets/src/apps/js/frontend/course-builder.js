import * as editCourse from './course-builder/edit-course.js';
import * as courseTab from './course-builder/tab-course.js';
import * as lessonTab from './course-builder/tab-lesson.js';
import * as editLesson from './course-builder/edit-lesson.js';

document.addEventListener( 'click', ( e ) => {
	const target = e.target;

	//Course Tab
	courseTab.courseDuplicate( e, target );
	courseTab.courseTrash( e, target );
	courseTab.courseDraft( e, target );
	courseTab.courseDelete( e, target );
	courseTab.activeCourseExpanded( e, target );

	//Course Edit
	editCourse.updateCourse( e, target );
	editCourse.trashCourse( e, target );
	editCourse.openMediaUploader( e, target );
	editCourse.removeFeaturedImage( e, target );
	editCourse.toggleAddCategoryForm( e, target );
	editCourse.toggleAddTagForm( e, target );
	editCourse.addNewCategory( e, target );
	editCourse.addNewTag( e, target );

	//Lesson Tab
	lessonTab.lessonDuplicate( e, target );
	lessonTab.lessonDelete( e, target );
	lessonTab.lessonTrash( e, target );
	lessonTab.lessonPublish( e, target );
	lessonTab.activeLessonExpanded( e, target );

	//Lesson Edit
	editLesson.updateLesson( e, target );
	editLesson.trashLesson( e, target );
} );

document.addEventListener( 'keydown', ( e ) => {
	const target = e.target;

	// Event enter for save new category
	editCourse.addNewCategory( e, target );
	// Event enter for save new term
	editCourse.addNewTag( e, target );
} );
