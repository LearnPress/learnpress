import SingleCourse from './single-course/index';
import { addQueryArgs } from '@wordpress/url';
import lpModalOverlayCompleteItem from './show-lp-overlay-complete-item';
import lpModalOverlay from '../utils/lp-modal-overlay';
import courseCurriculumSkeleton from './single-curriculum/skeleton';
import lpMaterialsLoad from './material';

export default SingleCourse;

export const init = () => {
	wp.element.render(
		<SingleCourse />,
	);
};

const $ = jQuery;

const initCourseTabs = function() {
	$( '#learn-press-course-tabs' ).on(
		'change',
		'input[name="learn-press-course-tab-radio"]',
		function( e ) {
			const selectedTab = $( 'input[name="learn-press-course-tab-radio"]:checked' ).val();

			LP.Cookies.set( 'course-tab', selectedTab );

			$( 'label[for="' + $( e.target ).attr( 'id' ) + '"]' ).closest( 'li' ).addClass( 'active' ).siblings().removeClass( 'active' );
		}
	);
};

const initCourseSidebar = function initCourseSidebar() {
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
};

// Rest API Enroll course - Nhamdv.
const enrollCourse = () => {
	const formEnrolls = document.querySelectorAll( 'form.enroll-course' );

	if ( formEnrolls.length > 0 ) {
		formEnrolls.forEach( ( formEnroll ) => {
			const submit = async ( id, btnEnroll ) => {
				try {
					const response = await wp.apiFetch( {
						path: 'lp/v1/courses/enroll-course',
						method: 'POST',
						data: { id },
					} );

					btnEnroll.classList.remove( 'loading' );
					btnEnroll.disabled = false;

					const { status, data: { redirect }, message } = response;

					if ( message && status ) {
						btnEnroll.style.display = 'none';
						formEnroll.innerHTML += `<div class="lp-enroll-notice ${ status }">${ message }</div>`;

						if ( redirect ) {
							window.location.href = redirect;
						}
					}
				} catch ( error ) {
					form.innerHTML += `<div class="lp-enroll-notice error">${ error.message && error.message }</div>`;
				}
			};

			formEnroll.addEventListener( 'submit', ( event ) => {
				event.preventDefault();
				const id = formEnroll.querySelector( 'input[name=enroll-course]' ).value;
				const btnEnroll = formEnroll.querySelector( 'button.button-enroll-course' );
				btnEnroll.classList.add( 'loading' );
				btnEnroll.disabled = true;
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
				const continueRepurchases = document.querySelectorAll( '.lp_allow_repuchase_select' );

				continueRepurchases.forEach( ( repurchase ) => {
					const radios = repurchase.querySelectorAll( '[name=_lp_allow_repurchase_type]' );

					for ( let i = 0, length = radios.length; i < length; i++ ) {
						if ( radios[ i ].checked ) {
							const repurchaseType = radios[ i ].value;
							const id = form.querySelector( 'input[name=purchase-course]' ).value;

							const btnBuynow = form.querySelector( 'button.button-purchase-course' );
							btnBuynow.classList.add( 'loading' );
							btnBuynow.disabled = true;

							submit( id, btnBuynow, repurchaseType );
							break;
						}
					}
				} );
			};

			const submit = async ( id, btn, repurchaseType = false ) => {
				try {
					const response = await wp.apiFetch( {
						path: 'lp/v1/courses/purchase-course',
						method: 'POST',
						data: { id, repurchaseType },
					} );

					if ( btn ) {
						btn.classList.remove( 'loading' );
						btn.disabled = false;
					}

					const { status, data: { redirect, type, html, titlePopup }, message } = response;

					if ( type === 'allow_repurchase' && status === 'success' ) {
						if ( ! form.querySelector( '.lp_allow_repuchase_select' ) ) {
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
						form.innerHTML += `<div class="lp-enroll-notice ${ status }">${ message }</div>`;

						if ( 'success' === status && redirect ) {
							window.location.href = redirect;
						}
					}
				} catch ( error ) {
					form.innerHTML += `<div class="lp-enroll-notice error">${ error.message && error.message }</div>`;
				}
			};

			form.addEventListener( 'submit', ( event ) => {
				event.preventDefault();
				const id = form.querySelector( 'input[name=purchase-course]' ).value;
				const btn = form.querySelector( 'button.button-purchase-course' );
				btn.classList.add( 'loading' );
				btn.disabled = true;

				submit( id, btn );
			} );
		} );
	}
};

const retakeCourse = () => {
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
};

// Rest API load content course progress - Nhamdv.
const courseProgress = () => {
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
		const response = await wp.apiFetch( {
			path: 'lp/v1/lazy-load/course-progress',
			method: 'POST',
			data: {
				courseId: lpGlobalSettings.post_id || '',
				userId: lpGlobalSettings.user_id || '',
			},
		} );

		const { data } = response;

		ele.innerHTML = data;
	};
};

const accordionExtraTab = () => {
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
};

const courseContinue = () => {
	const formContinue = document.querySelectorAll( 'form.continue-course' );

	if ( formContinue != null && lpGlobalSettings.user_id > 0 ) {
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
					form.style.display = 'block';
					form.action = result.data;
				} );
			}
		} );
	}
};

export {
	initCourseTabs,
	initCourseSidebar,
	enrollCourse,
};

$( window ).on( 'load', () => {
	const $popup = $( '#popup-course' );
	let timerClearScroll;
	const $curriculum = $( '#learn-press-course-curriculum' );
	accordionExtraTab();
	initCourseTabs();
	initCourseSidebar();
	enrollCourse();
	purchaseCourse();
	retakeCourse();
	courseProgress();
	courseContinue();
	lpModalOverlayCompleteItem.init();
	lpMaterialsLoad();
	//courseCurriculumSkeleton();
} );

const detectedElCurriculum = setInterval( function() {
	const elementCurriculum = document.querySelector( '.learnpress-course-curriculum' );
	if ( elementCurriculum ) {
		courseCurriculumSkeleton();
		
		clearInterval( detectedElCurriculum );
	}
}, 1 );

// Add callback for Thimkits
LP.Hook.addAction( 'lp_course_curriculum_skeleton', function( id ) {
	courseCurriculumSkeleton( id );
} );

