import SingleCourse from './single-course/index';

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
	const formEnroll = document.querySelector( 'form.enroll-course' );

	if ( ! formEnroll || ! document.body.classList.contains( 'logged-in' ) ) {
		return;
	}

	const submit = async ( id, btnEnroll ) => {
		const response = await wp.apiFetch(
			{
				path: 'lp/v1/courses/enroll-course',
				method: 'POST',
				data: { id },
			}
		);

		btnEnroll.classList.remove( 'loading' );
		btnEnroll.disabled = false;

		const { status, data: { redirect }, message } = response;

		if ( message && status ) {
			formEnroll.innerHTML += `<div class="lp-enroll-notice ${ status }">${ message }</div>`;

			if ( 'success' === status && undefined !== redirect ) {
				window.location.href = redirect;
			}
		}
	};

	formEnroll.addEventListener(
		'submit',
		( event ) => {
			event.preventDefault();
			const id = formEnroll.querySelector( 'input[name=enroll-course]' ).value;
			const btnEnroll = formEnroll.querySelector( 'button.button-enroll-course' );
			btnEnroll.classList.add( 'loading' );
			btnEnroll.disabled = true;
			submit( id, btnEnroll );
		}
	);

	// Reload when press back button in chrome.
	if ( document.querySelector( '.course-detail-info' ) !== null ) {
		window.addEventListener(
			'pageshow',
			( event ) => {
				const hasCache = event.persisted || ( typeof window.performance != 'undefined' && String( window.performance.getEntriesByType( 'navigation' )[ 0 ].type ) == 'back_forward' );
				if ( hasCache ) {
					location.reload();
				}
			}
		);
	}
};

export {
	initCourseTabs,
	initCourseSidebar,
	enrollCourse,
};

$( window ).on(
	'load',
	() => {
		const $popup = $( '#popup-course' );
		let timerClearScroll;
		const $curriculum = $( '#learn-press-course-curriculum' );

		initCourseTabs();
		initCourseSidebar();
		enrollCourse();
	}
);
