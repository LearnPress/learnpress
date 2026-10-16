/**
 * Course builder JS handler.
 *
 * @since 4.3.6
 * @version 1.0.0
 */
import * as lpUtils from 'lpAssetsJsPath/utils.js';
import SweetAlert from 'sweetalert2';
import { BuilderTabCourse } from './course-builder/builder-course/builder-tab-course.js';
import { SWAL_ICON_DELETE } from './course-builder/swal-icons.js';
import { BuilderEditCourse } from './course-builder/builder-course/builder-edit-course.js';
import { CreateCourseViaAI } from '../admin/courses/generate-with-ai.js';
import { GenerateWithOpenai } from '../admin/generate-with-openai.js';
import { BuilderTabLesson } from './course-builder/builder-lesson/builder-tab-lesson.js';
import { BuilderEditLesson } from './course-builder/builder-lesson/builder-edit-lesson.js';
import { BuilderTabQuiz } from './course-builder/builder-quiz/builder-tab-quiz.js';
import { BuilderStandaloneQuiz } from './course-builder/builder-quiz/builder-standalone-quiz.js';
import { BuilderTabQuestion } from './course-builder/builder-question/builder-tab-question.js';
import { BuilderEditQuestion } from './course-builder/builder-question/builder-edit-question.js';
import { BuilderPopup } from './course-builder/builder-popup.js';
import { BuilderMaterial } from './course-builder/builder-lesson/builder-material.js';
import { BuilderDashboard } from './course-builder/builder-dashboard.js';
import { BuilderSettings } from './course-builder/builder-settings.js';
import { getFormState } from './course-builder/builder-form-state.js';
import { EditCurriculumAi } from '../admin/edit-course/edit-curriculum/edit-curriculum-ai.js';
import { initElsTomSelect } from 'lpAssetsJsPath/admin/init-tom-select.js';
import { Utils } from 'lpAssetsJsPath/admin/utils-admin.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify';

let createCourseAiInitialized = false;
let editCourseAiInitialized = false;

/**
 * Initialize delegated AI actions after Course Builder content is mounted.
 * The Frontend Editor fetches its course markup asynchronously, after this
 * bundle's initial page setup has already run.
 *
 * @param {HTMLElement|Document} container Content that may contain AI buttons.
 */
const initCourseBuilderAi = ( container = document ) => {
	if (
		! createCourseAiInitialized &&
		container.querySelector( '.lp-btn-generate-course-with-ai, .lp-btn-warning-enable-ai' )
	) {
		new CreateCourseViaAI( {
			autoInsertButton: false,
			isCourseBuilder: true,
			redirectDelayMs: 1200,
		} );
		createCourseAiInitialized = true;
	}

	if (
		! editCourseAiInitialized &&
		container.querySelector( '.cb-course-edit-ai-btn.lp-btn-generate-with-ai' )
	) {
		new GenerateWithOpenai( {
			autoInsertButtons: false,
			isCourseBuilder: true,
		} );
		editCourseAiInitialized = true;
	}
};

window.lpCourseBuilderInitAi = initCourseBuilderAi;

// Initialize all builder components
const initBuilderComponents = () => {
	try {
		new BuilderTabCourse();
		initCourseEditor();
		initCourseBuilderAi();

		new BuilderTabLesson();
		new BuilderEditLesson();
		new BuilderTabQuiz();
		new BuilderStandaloneQuiz();
		new BuilderTabQuestion();
		new BuilderEditQuestion();
		new BuilderPopup();
		new BuilderDashboard();
		new BuilderSettings();
		new EditCurriculumAi( { isCourseBuilder: true } );

		// Initialize form state management for ClassPress-style UX
		getFormState();

		// Initialize sidebar toggle
		initSidebarToggle();
		initSidebarSubMenus();
		initHeaderMoreActions();
	} catch ( e ) {
		console.error( 'Error initializing builder components:', e );
	}
};

const initializedCourseEditors = new WeakSet();

/**
 * Initialize the Course Builder edit form after it has been mounted dynamically.
 *
 * @param {HTMLElement} container Optional container holding the editor markup.
 * @return {boolean} Whether the editor was initialized.
 */
const initCourseEditor = ( container = document ) => {
	const editElement = container.matches?.( '.cb-section__course-edit' )
		? container
		: container.querySelector?.( '.cb-section__course-edit' );

	if ( ! editElement || initializedCourseEditors.has( editElement ) ) {
		return false;
	}

	try {
		new BuilderEditCourse();
		initializedCourseEditors.add( editElement );
		return true;
	} catch ( error ) {
		console.error( 'Error initializing Course Builder edit form:', error );
		return false;
	}
};

window.lpCourseBuilderInitCourseEditor = initCourseEditor;

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

/**
 * Initialize nested sidebar submenu toggles.
 */
const initSidebarSubMenus = () => {
	const sidebar = document.getElementById( 'lp-course-builder-sidebar' );

	if ( ! sidebar || sidebar.dataset.subMenusInitialized === 'true' ) {
		return;
	}

	sidebar.dataset.subMenusInitialized = 'true';

	sidebar.addEventListener( 'click', ( e ) => {
		const toggleBtn = e.target.closest( '.lp-cb-sidebar__sub-menu-toggle' );

		if ( ! toggleBtn || ! sidebar.contains( toggleBtn ) ) {
			return;
		}

		const item = toggleBtn.closest( '.lp-cb-sidebar__item.has-sub-menu' );
		if ( ! item ) {
			return;
		}

		e.preventDefault();

		const isExpanded = item.classList.toggle( 'is-expanded' );
		toggleBtn.setAttribute( 'aria-expanded', isExpanded ? 'true' : 'false' );
	} );
};

/**
 * Initialize "More actions" menu in edit header (duplicate/trash).
 * Uses explicit open state to avoid focus-only behavior issues across browsers.
 */
const initHeaderMoreActions = () => {
	const wrapSelector = '.cb-header-action-expanded';
	const toggleSelector = '.course-action-expanded';
	const openClass = 'is-open';

	const closeAll = ( exclude = null ) => {
		document.querySelectorAll( `${ wrapSelector }.${ openClass }` ).forEach( ( wrap ) => {
			if ( exclude && wrap === exclude ) {
				return;
			}

			wrap.classList.remove( openClass );
			const btn = wrap.querySelector( toggleSelector );
			if ( btn ) {
				btn.setAttribute( 'aria-expanded', 'false' );
			}
		} );
	};

	document.addEventListener( 'click', async ( e ) => {
		const target = e.target;
		const toggleBtn = target.closest( toggleSelector );

		if ( toggleBtn ) {
			e.preventDefault();
			e.stopPropagation();

			const wrap = toggleBtn.closest( wrapSelector );
			if ( ! wrap ) {
				return;
			}

			const willOpen = ! wrap.classList.contains( openClass );
			closeAll( wrap );
			wrap.classList.toggle( openClass, willOpen );
			toggleBtn.setAttribute(
				'aria-expanded',
				willOpen ? 'true' : 'false',
			);
			return;
		}

		if ( ! target.closest( wrapSelector ) ) {
			closeAll();
		}

		// New expand item
		const elActionExpands = document.querySelectorAll(
			'.lp-cb-item-action-expand',
		);
		if ( target.closest( '.lp-cb-item-action-expand-toggle' ) ) {
			const elParentAction = target.closest( '.lp-cb-item-action-wrap' );
			const elActionExpand = elParentAction.querySelector(
				'.lp-cb-item-action-expand',
			);

			elActionExpand.classList.toggle( lpUtils.lpClassName.hidden );

			// Close all
			elActionExpands.forEach( ( elActionExpandCheck ) => {
				if ( elActionExpandCheck !== elActionExpand ) {
					elActionExpandCheck.classList.add( lpUtils.lpClassName.hidden );
				}
			} );
		}

		if ( ! target.closest( '.lp-cb-item-action-expand-toggle' ) ) {
			elActionExpands.forEach( ( elActionExpandCheck ) => {
				elActionExpandCheck.classList.add(
					lpUtils.lpClassName.hidden,
				);
			} );
		}
		// End new expand item

		// Item action
		if ( target.closest( '.lp-cb-item-action' ) ) {
			const elAction = target.closest( '.lp-cb-item-action' );
			const elParentActionWrap = elAction.closest(
				'.lp-cb-item-action-wrap',
			);
			const elParentActionExpand = elParentActionWrap.querySelector(
				'.lp-cb-item-action-expand-toggle',
			);
			const dataSend = JSON.parse( elAction.dataset.send );
			if ( dataSend.action_type === 'delete' ) {
				const result = await SweetAlert.fire( {
					title: elAction.textContent,
					text: elAction.dataset.confirm,
					iconHtml: SWAL_ICON_DELETE,
					customClass: { icon: 'lp-cb-swal-icon-html' },
					showCloseButton: true,
					showCancelButton: true,
					cancelButtonText: lpData.i18n.cancel,
					confirmButtonText: lpData.i18n.yes,
					reverseButtons: true,
				} );
				if ( ! result.isConfirmed ) {
					return;
				}
			}

			lpUtils.lpSetLoadingEl( elParentActionExpand, 1 );
			const svg = elParentActionExpand.querySelector( 'svg' );
			//lpUtils.lpShowHideEl( svg, 0 );

			// Ajax to generate prompt
			const callBack = {
				success: ( response ) => {
					const { message, status, data } = response;

					lpToastify.show( message, status );

					if ( status === 'success' ) {
						if ( data.redirect_url ) {
							window.location.href = data.redirect_url;
						} else if ( data.html ) {
							const elParentLi = elAction.closest( 'li.course' );
							elParentLi.outerHTML = data.html;
						} else if ( dataSend.action_type === 'delete' ) {
							const elParentLi = elAction.closest( 'li.course' );
							elParentLi.remove();
						}
					}
				},
				error: ( error ) => {
					lpToastify.show( error, 'error' );
				},
				completed: () => {
					lpUtils.lpSetLoadingEl( elParentActionExpand, 0 );
				},
			};

			window.lpAJAXG.fetchAJAX( dataSend, callBack );
		}
		// End item action
	} );

	document.addEventListener( 'keydown', ( e ) => {
		if ( e.key === 'Escape' ) {
			closeAll();
		}
	} );
};

// Add loading state to search buttons on form submit (page reload)
document.addEventListener( 'submit', ( e ) => {
	const form = e.target.closest( '.cb-search-form' );
	if ( ! form ) {
		return;
	}

	const searchBtn = form.querySelector( '.cb-search-btn' );
	if ( searchBtn ) {
		searchBtn.classList.add( 'loading' );
		searchBtn.disabled = true;
	}
} );

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
					const materialContainer = document.querySelector(
						targetPanel + ' #lp-material-container'
					);
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
