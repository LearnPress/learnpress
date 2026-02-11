/**
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
import { BuilderStandaloneQuiz } from './course-builder/builder-quiz/builder-standalone-quiz.js';
import { BuilderTabQuestion } from './course-builder/builder-question/builder-tab-question.js';
import { BuilderEditQuestion } from './course-builder/builder-question/builder-edit-question.js';
import { BuilderPopup } from './course-builder/builder-popup.js';
import { BuilderMaterial } from './course-builder/builder-lesson/builder-material.js';
import { BuilderFormState, getFormState } from './course-builder/builder-form-state.js';
import { initElsTomSelect } from 'lpAssetsJsPath/admin/init-tom-select.js';
import { Utils } from 'lpAssetsJsPath/admin/utils-admin.js';

// Initialize all builder components
const initBuilderComponents = () => {
	try {
		new BuilderTabCourse();
		new BuilderEditCourse();
		new BuilderTabLesson();
		new BuilderEditLesson();
		new BuilderTabQuiz();
		new BuilderStandaloneQuiz();
		new BuilderTabQuestion();
		new BuilderEditQuestion();
		new BuilderPopup();
		
		// Initialize form state management for ClassPress-style UX
		getFormState();

		// Initialize sidebar toggle
		initSidebarToggle();
	} catch ( e ) {
		console.error( 'Error initializing builder components:', e );
	}
};

/**
 * Initialize sidebar collapse/expand toggle
 * Persists state in localStorage
 */
const initSidebarToggle = () => {
	const sidebar = document.getElementById( 'lp-course-builder-sidebar' );
	const toggleBtn = document.querySelector( '.lp-cb-sidebar__toggle' );
	const wrapper = document.getElementById( 'lp-course-builder' );
	const storageKey = 'lp_cb_sidebar_collapsed';

	if ( ! sidebar || ! toggleBtn ) {
		return;
	}

	// Restore saved state
	const isCollapsed = localStorage.getItem( storageKey ) === 'true';
	if ( isCollapsed ) {
		sidebar.classList.add( 'is-collapsed' );
		if ( wrapper ) {
			wrapper.classList.add( 'has-collapsed-sidebar' );
		}
	}

	// Handle toggle click
	toggleBtn.addEventListener( 'click', () => {
		const willCollapse = ! sidebar.classList.contains( 'is-collapsed' );
		
		sidebar.classList.toggle( 'is-collapsed' );
		if ( wrapper ) {
			wrapper.classList.toggle( 'has-collapsed-sidebar' );
		}
		
		// Save state
		localStorage.setItem( storageKey, willCollapse ? 'true' : 'false' );
	} );
};

// Initialize components
initBuilderComponents();

// Events
document.addEventListener( 'click', ( e ) => {
	try {
		initElsTomSelect();
	} catch ( e ) {
		console.warn( 'Error initializing TomSelect:', e );
	}
} );

document.addEventListener( 'DOMContentLoaded', () => {
	// Sure that the TomSelect is loaded if listener can't find elements.
	try {
		initElsTomSelect();
	} catch ( e ) {
		console.warn( 'Error initializing TomSelect on DOMContentLoaded:', e );
	}

	// Initialize BuilderMaterial for Course Builder Settings tab Material
	try {
		initBuilderMaterialForCourseSettings();
	} catch ( e ) {
		console.error( 'Error initializing BuilderMaterial:', e );
	}
} );

// Use lpOnElementReady safely
if ( Utils?.lpOnElementReady ) {
	Utils.lpOnElementReady( 'select.lp-tom-select', () => {
		try {
			initElsTomSelect();
		} catch ( e ) {
			console.warn( 'Error initializing TomSelect:', e );
		}
	} );
}

window.lpFindTomSelect = initElsTomSelect;

/**
 * Initialize BuilderMaterial for Course Builder Settings tab Material
 */
function initBuilderMaterialForCourseSettings() {
	const initializedContainers = new WeakSet();

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
				try {
					const materialContainer = document.querySelector( targetPanel + ' #lp-material-container' );
					if ( materialContainer && ! initializedContainers.has( materialContainer ) ) {
						// Mark as initialized to prevent multiple instances
						initializedContainers.add( materialContainer );
						// Initialize BuilderMaterial
						new BuilderMaterial( materialContainer );
					}
				} catch ( e ) {
					console.error( 'Error initializing BuilderMaterial:', e );
				}
			}, 100 );
		}
	} );
}
