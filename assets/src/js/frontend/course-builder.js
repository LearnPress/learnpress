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
import { BuilderPopup } from './course-builder/builder-popup.js';
import { BuilderMaterial } from './course-builder/builder-lesson/builder-material.js';
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
new BuilderPopup();

// Events
document.addEventListener( 'click', ( e ) => {
	initElsTomSelect();
} );

document.addEventListener( 'DOMContentLoaded', () => {
	// Sure that the TomSelect is loaded if listen can't find elements.
	initElsTomSelect();

	// Initialize BuilderMaterial for Course Builder Settings tab Material
	initBuilderMaterialForCourseSettings();
} );

Utils.lpOnElementReady( 'select.lp-tom-select', ( e ) => {
	initElsTomSelect();
} );

window.lpFindTomSelect = initElsTomSelect;

/**
 * Initialize BuilderMaterial for Course Builder Settings tab Material
 */
function initBuilderMaterialForCourseSettings() {
	// Listen for tab clicks in Course Settings using event delegation
	document.addEventListener( 'click', ( e ) => {
		const target = e.target.closest( 'ul.lp-meta-box__course-tab__tabs a' );
		
		if ( ! target ) {
			return;
		}

		const targetPanel = target.getAttribute( 'href' );
		
		// Check if Material tab is clicked
		if ( targetPanel && targetPanel.includes( 'material' ) ) {
			// Wait for DOM to update
			setTimeout( () => {
				const materialContainer = document.querySelector( targetPanel + ' #lp-material-container' );
				if ( materialContainer && ! materialContainer.dataset.builderMaterialInit ) {
					// Mark as initialized to prevent multiple instances
					materialContainer.dataset.builderMaterialInit = 'true';
					// Initialize BuilderMaterial
					new BuilderMaterial( materialContainer );
				}
			}, 100 );
		}
	} );
}
