import SingleCourse from './single-course/index';
//import lpModalOverlayCompleteItem from './show-lp-overlay-complete-item'; // @deprecated 4.5.1 single course page has not .lp-btn-complete-item, .lp-btn-finish-course (still used on page item - single-curriculum.js).
//import lpModalOverlay from '../utils/lp-modal-overlay'; // @deprecated 4.5.1 only used by enroll, purchase course.
//import courseCurriculumSkeleton from './single-curriculum/skeleton'; // @deprecated 4.5.1 REST lazy-load course-curriculum is disabled.
//import lpMaterialsLoad from './material'; // @deprecated 4.5.1 moved to SingleCourse::loadMoreMaterial (assets/src/js/frontend/single-course.js).
//import TabsDragScroll from './tabs-scroll'; // @deprecated 4.5.1 moved to SingleCourse::initTabsDragScroll, scrollTabToCenter (assets/src/js/frontend/single-course.js).
//import { lpSetLoadingEl } from '../../../js/utils.js'; // @deprecated 4.5.1 only used by enroll, purchase course.
//import Toastify from 'toastify-js'; // @deprecated 4.5.1 only used by enroll, purchase course.
import 'toastify-js/src/toastify.css';
//import LPCopyToClipboard from '../../../js/frontend/copy-to-clipboard.js'; // @deprecated 4.5.1 moved to SingleCourse::toggleShare, copyToClipboard (assets/src/js/frontend/single-course.js).

export default SingleCourse;

export const init = () => {
	wp.element.render(
		<SingleCourse />,
	);
};

const $ = jQuery;

/**
 * Set active nav tab, save tab chose to cookie 'course-tab'.
 *
 * @deprecated 4.5.1 moved to SingleCourse::changeCourseTab (assets/src/js/frontend/single-course.js),
 * cookie 'course-tab' is removed (not read).
 */
/*const initCourseTabs = function() {
	$( '#learn-press-course-tabs' ).on(
		'change',
		'input[name="learn-press-course-tab-radio"]',
		function( e ) {
			const selectedTab = $( 'input[name="learn-press-course-tab-radio"]:checked' ).val();

			LP.Cookies.set( 'course-tab', selectedTab );

			$( 'label[for="' + $( e.target ).attr( 'id' ) + '"]' ).closest( 'li' ).addClass( 'active' ).siblings().removeClass( 'active' );
		}
	);
};*/

/**
 * Fixed course sidebar when scroll (add class slide-down, slide-top).
 *
 * @deprecated 4.5.1 replaced by CSS position sticky (assets/src/scss/frontend/_course-sidebar.scss).
 */
/*const initCourseSidebar = function initCourseSidebar() {
	const $sidebar = $( '.course-summary-sidebar' );

	if ( ! $sidebar.length ) {
		return;
	}

	const $window = $( window );
	const $scrollable = $sidebar.children();
	const offset = $sidebar.offset();
	let scrollTop = 0;
	const maxHeight = $sidebar.height();
	const scrollHeight = $scrollable.height();
	const options = {
		offsetTop: 32,
	};

	const onScroll = function() {
		scrollTop = $window.scrollTop();

		const top = scrollTop - offset.top + options.offsetTop;

		if ( top < 0 ) {
			$sidebar.removeClass( 'slide-top slide-down' );
			$scrollable.css( 'top', '' );
			return;
		}

		if ( top > maxHeight - scrollHeight ) {
			$sidebar.removeClass( 'slide-down' ).addClass( 'slide-top' );
			$scrollable.css( 'top', maxHeight - scrollHeight );
		} else {
			$sidebar.removeClass( 'slide-top' ).addClass( 'slide-down' );
			$scrollable.css( 'top', options.offsetTop );
		}
	};

	$window.on( 'scroll.fixed-course-sidebar', onScroll ).trigger( 'scroll.fixed-course-sidebar' );
};*/

/**
 * Enroll, purchase course via REST API lp/v1/courses/enroll-course, purchase-course.
 *
 * @deprecated 4.5.1 moved to SingleCourse::enrollCourse, purchaseCourse (assets/src/js/frontend/single-course.js),
 * AJAX UserCourseAjax::user_enroll_course, user_purchase_course.
 */
/*const enrollCourse = () => {
	const formEnrolls = document.querySelectorAll( 'form.enroll-course' );

	if ( formEnrolls.length > 0 ) {
		formEnrolls.forEach( ( formEnroll ) => {
			const submit = async ( id, btnEnroll ) => {
				const status = 'error';

				try {
					const response = await wp.apiFetch( {
						path: 'lp/v1/courses/enroll-course',
						method: 'POST',
						data: { id },
					} );

					const { status, data: { redirect }, message } = response;

					if ( status === 'success' ) {
						btnEnroll.remove();
					} else {
						lpSetLoadingEl( btnEnroll, 0 );
						throw new Error( message );
					}

					if ( message && status ) {
						formEnroll.innerHTML += `<div class="learn-press-message ${ status }">${ message }</div>`;
						if ( redirect ) {
							window.location.href = redirect;
						}
					}
				} catch ( error ) {
					Toastify( {
						text: error.message,
						gravity: lpData.toast.gravity, // `top` or `bottom`
						position: lpData.toast.position, // `left`, `center` or `right`
						className: `${ lpData.toast.classPrefix } ${ status }`,
						close: lpData.toast.close == 1,
						stopOnFocus: lpData.toast.stopOnFocus == 1,
						duration: lpData.toast.duration,
					} ).showToast();
				}
			};

			formEnroll.addEventListener( 'submit', ( event ) => {
				event.preventDefault();
				const id = formEnroll.querySelector( 'input[name=enroll-course]' ).value;
				const btnEnroll = formEnroll.querySelector( 'button.button-enroll-course' );
				lpSetLoadingEl( btnEnroll, 1 );
				submit( id, btnEnroll );
			} );
		} );
	}

	// Reload when press back button in chrome.
	if ( document.querySelector( '.course-detail-info' ) !== null ) {
		window.addEventListener( 'pageshow', ( event ) => {
			const hasCache = event.persisted || ( typeof window.performance != 'undefined' && String( window.performance.getEntriesByType( 'navigation' )[ 0 ].type ) == 'back_forward' );
			if ( hasCache ) {
				location.reload();
			}
		} );
	}
};

// Rest API purchase course - Nhamdv.
const purchaseCourse = () => {
	const forms = document.querySelectorAll( 'form.purchase-course' );

	if ( forms.length > 0 ) {
		forms.forEach( ( form ) => {
			// Allow Repurchase.
			const allowRepurchase = () => {
				const continueRepurchases = document.querySelectorAll( '.lp_allow_repurchase_select' );

				continueRepurchases.forEach( ( repurchase ) => {
					const radios = repurchase.querySelectorAll( '[name=_lp_allow_repurchase_type]' );

					for ( let i = 0, length = radios.length; i < length; i++ ) {
						if ( radios[ i ].checked ) {
							const repurchaseType = radios[ i ].value;
							const id = form.querySelector( 'input[name=purchase-course]' ).value;

							const btnBuyNow = form.querySelector( 'button.button-purchase-course' );
							lpSetLoadingEl( btnBuyNow, 1 );

							submit( id, btnBuyNow, repurchaseType );
							break;
						}
					}
				} );
			};

			const submit = async ( id, btn, repurchaseType = false ) => {
				const status = 'error';

				try {
					const formData = new FormData( form );
					const dataSend = Object.fromEntries( Array.from( formData.keys(), ( key ) => {
						const val = formData.getAll( key );

						return [ key, val.length > 1 ? val : val.pop() ];
					} ) );

					dataSend.id = id;
					dataSend.repurchaseType = repurchaseType;

					const response = await wp.apiFetch( {
						path: 'lp/v1/courses/purchase-course',
						method: 'POST',
						data: dataSend,
					} );

					const { status, data: { redirect, type, html, titlePopup }, message } = response;

					if ( status === 'success' ) {
						if ( type === 'allow_repurchase' ) {
							lpSetLoadingEl( btn, 0 );
						} else {
							btn.remove();
						}
					} else {
						lpSetLoadingEl( btn, 0 );
						throw new Error( message );
					}

					if ( type === 'allow_repurchase' && status === 'success' ) {
						if ( ! form.querySelector( '.lp_allow_repurchase_select' ) ) {
							if ( ! lpModalOverlay.init() ) {
								return;
							}

							lpModalOverlay.elLPOverlay.show();

							lpModalOverlay.setTitleModal( titlePopup || '' );

							lpModalOverlay.setContentModal( html );

							lpModalOverlay.callBackYes = () => {
								lpModalOverlay.elLPOverlay.hide();

								allowRepurchase();
							};
						}
					} else if ( message && status ) {
						form.innerHTML += `<div class="learn-press-message ${ status }">${ message }</div>`;

						if ( 'success' === status && redirect ) {
							window.location.href = redirect;
						}
					}
				} catch ( error ) {
					Toastify( {
						text: error.message,
						gravity: lpData.toast.gravity, // `top` or `bottom`
						position: lpData.toast.position, // `left`, `center` or `right`
						className: `${ lpData.toast.classPrefix } ${ status }`,
						close: lpData.toast.close == 1,
						stopOnFocus: lpData.toast.stopOnFocus == 1,
						duration: lpData.toast.duration,
					} ).showToast();
				}
			};

			form.addEventListener( 'submit', ( event ) => {
				event.preventDefault();
				const id = form.querySelector( 'input[name=purchase-course]' ).value;
				const btn = form.querySelector( 'button.button-purchase-course' );
				lpSetLoadingEl( btn, 1 );
				submit( id, btn );
			} );
		} );
	}
};*/

/**
 * Retake course via REST API lp/v1/courses/retake-course.
 *
 * @deprecated 4.5.1 moved to assets/src/js/frontend/single-course.js (AJAX UserCourseAjax::user_retake_course).
 */
/*const retakeCourse = () => {
	const elFormRetakeCourses = document.querySelectorAll( '.lp-form-retake-course' );

	if ( ! elFormRetakeCourses.length ) {
		return;
	}

	elFormRetakeCourses.forEach( ( elFormRetakeCourse ) => {
		const elButtonRetakeCourses = elFormRetakeCourse.querySelector( '.button-retake-course' );
		const elCourseId = elFormRetakeCourse.querySelector( '[name=retake-course]' ).value;
		const elAjaxMessage = elFormRetakeCourse.querySelector( '.lp-ajax-message' );
		const submit = ( elButtonRetakeCourse ) => {
			wp.apiFetch( {
				path: '/lp/v1/courses/retake-course',
				method: 'POST',
				data: { id: elCourseId },
			} ).then( ( res ) => {
				const { status, message, data } = res;
				elAjaxMessage.innerHTML = message;

				if ( undefined != status && status === 'success' ) {
					elButtonRetakeCourse.style.display = 'none';
					setTimeout( () => {
						window.location.replace( data.url_redirect );
					}, 1000 );
				} else {
					elAjaxMessage.classList.add( 'error' );
				}
			} ).catch( ( err ) => {
				elAjaxMessage.classList.add( 'error' );
				elAjaxMessage.innerHTML = 'error: ' + err.message;
			} ).then( () => {
				elButtonRetakeCourse.classList.remove( 'loading' );
				elButtonRetakeCourse.disabled = false;
				elAjaxMessage.style.display = 'block';
			} );
		};

		elFormRetakeCourse.addEventListener( 'submit', ( e ) => {
			e.preventDefault();
		} );

		elButtonRetakeCourses.addEventListener(
			'click',
			( e ) => {
				e.preventDefault();
				elButtonRetakeCourses.classList.add( 'loading' );
				elButtonRetakeCourses.disabled = true;
				submit( elButtonRetakeCourses );
			}
		);
	} );
};*/

// Rest API load content course progress - Nhamdv.
/*const courseProgress = () => {
	const elements = document.querySelectorAll( '.lp-course-progress-wrapper' );

	if ( ! elements.length ) {
		return;
	}

	if ( 'IntersectionObserver' in window ) {
		const eleObserver = new IntersectionObserver( ( entries, observer ) => {
			entries.forEach( ( entry ) => {
				if ( entry.isIntersecting ) {
					const ele = entry.target;

					setTimeout( function() {
						getResponse( ele );
					}, 600 );

					eleObserver.unobserve( ele );
				}
			} );
		} );

		[ ...elements ].map( ( ele ) => eleObserver.observe( ele ) );
	}

	const getResponse = async ( ele ) => {
		let url = 'lp/v1/lazy-load/course-progress';
		if ( lpData.urlParams.hasOwnProperty( 'lang' ) ) {
			url += '?lang=' + lpData.urlParams.lang;
		}

		const response = await wp.apiFetch( {
			path: url,
			method: 'POST',
			data: {
				courseId: lpGlobalSettings.post_id || '',
				userId: lpData.user_id || '',
			},
		} );

		const { data } = response;

		ele.innerHTML = data;
	};
};*/

/**
 * Accordion extra boxes (requirements, features, target audiences).
 *
 * @deprecated 4.5.1 moved to SingleCourse::toggleExtraBox (assets/src/js/frontend/single-course.js).
 */
/*const accordionExtraTab = () => {
	const elements = document.querySelectorAll( '.course-extra-box' );
	[ ...elements ].map( ( ele ) => {
		const title = ele.querySelector( '.course-extra-box__title' );
		ele.classList.remove( 'active' );
		const content = ele.querySelector( '.course-extra-box__content' );
		content.style.height = '0';

		title.addEventListener( 'click', () => {
			const isActive = ele.classList.contains( 'active' );

			[ ...elements ].forEach( ( otherEle ) => {
				if ( otherEle !== ele ) {
					otherEle.classList.remove( 'active' );
					otherEle.querySelector( '.course-extra-box__content' ).style.height = '0';
				}
			} );

			if ( isActive ) {
				ele.classList.remove( 'active' );
				content.style.height = '0';
			} else {
				ele.classList.add( 'active' );
				content.style.height = content.scrollHeight + 'px';
			}
		} );
	} );
};*/

/**
 * Show button continue course, get link via REST API lp/v1/courses/continue-course.
 *
 * @deprecated 4.5.1 button continue is rendered with link by UserCourseTemplate::html_btn_continue,
 * template single-course/buttons/continue.php (form.continue-course) is not loaded.
 */
/*const courseContinue = () => {
	const formContinue = document.querySelectorAll( 'form.continue-course' );
	if ( formContinue.length && lpData.user_id > 0 ) {
		const getResponse = async ( ele ) => {
			const response = await wp.apiFetch( {
				path: 'lp/v1/courses/continue-course',
				method: 'POST',
				data: {
					courseId: lpGlobalSettings.post_id || '',
					userId: lpGlobalSettings.user_id || '',
				},
			} );

			return response;
		};

		getResponse( formContinue ).then( function( result ) {
			if ( result.status === 'success' ) {
				formContinue.forEach( ( form ) => {
					form.style.display = 'inline-block';
					form.action = result.data;
				} );
			}
		} );
	}
};*/

export {
	//initCourseTabs, // @deprecated 4.5.1
	//initCourseSidebar, // @deprecated 4.5.1
	//enrollCourse, // @deprecated 4.5.1
};

document.addEventListener( 'DOMContentLoaded', function() {
	const $popup = $( '#popup-course' );
	let timerClearScroll;
	const $curriculum = $( '#learn-press-course-curriculum' );
	//accordionExtraTab(); // @deprecated 4.5.1
	//initCourseTabs(); // @deprecated 4.5.1
	//initCourseSidebar(); // @deprecated 4.5.1
	//enrollCourse(); // @deprecated 4.5.1
	//purchaseCourse(); // @deprecated 4.5.1
	//retakeCourse(); // @deprecated 4.5.1
	//courseProgress();
	//courseContinue(); // @deprecated 4.5.1
	//lpModalOverlayCompleteItem.init(); // @deprecated 4.5.1
	//lpMaterialsLoad(); // @deprecated 4.5.1
	//courseCurriculumSkeleton();
	//TabsDragScroll(); // @deprecated 4.5.1
	//LPCopyToClipboard(); // @deprecated 4.5.1
} );

/**
 * Lazy load curriculum via REST API lp/v1/lazy-load/course-curriculum (old template has .learnpress-course-curriculum).
 *
 * @deprecated 4.5.1 REST API course-curriculum, course-curriculum-items are disabled,
 * tab curriculum uses SingleCourseTemplate::html_curriculum (no .learnpress-course-curriculum, setInterval never stopped).
 */
/*const detectedElCurriculum = setInterval( function() {
	const elementCurriculum = document.querySelector( '.learnpress-course-curriculum' );
	if ( elementCurriculum ) {
		courseCurriculumSkeleton();

		clearInterval( detectedElCurriculum );
	}
}, 1 );

// Add callback for Thimkits
LP.Hook.addAction( 'lp_course_curriculum_skeleton', function( id ) {
	courseCurriculumSkeleton( id );
} );*/

