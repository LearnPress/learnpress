import * as editCourse from './course-builder/edit-course.js';
import * as courseTab from './course-builder/tab-course.js';

document.addEventListener( 'click', ( e ) => {
	const target = e.target;

	// Click update or draft course
	editCourse.updateCourse( e, target );
	//Click trash course
	editCourse.trashCourse( e, target );
	// Click set featured image
	editCourse.openMediaUploader( e, target );
	// Click remove featured image
	editCourse.removeFeaturedImage( e, target );
	//Click add new category
	editCourse.toggleAddCategoryForm( e, target );
	//Click add new term
	editCourse.toggleAddTagForm( e, target );
	//Click save new category
	editCourse.addNewCategory( e, target );
	//Click save new term
	editCourse.addNewTag( e, target );

	//Click duplicate course
	courseTab.courseDuplicate( e, target );
	//Click delete course
	courseTab.courseDelete( e, target );
	//Click course action explanded
	courseTab.activeCourseExpanded( e, target );
} );

document.addEventListener( 'keydown', ( e ) => {
	const target = e.target;

	// Event enter for save new category
	editCourse.addNewCategory( e, target );
	// Event enter for save new term
	editCourse.addNewTag( e, target );
} );
