import SingleCourse from './single-course/index';

export default SingleCourse;

export const init = () => {
	wp.element.render(
		<SingleCourse />,
	);
};

const $ = jQuery;
const { debounce, throttle } = lodash;

export function formatDuration( seconds ) {
	let d;

	const dayInSeconds = 3600 * 24;

	if ( seconds > dayInSeconds ) {
		d = ( seconds - ( seconds % dayInSeconds ) ) / dayInSeconds;
		seconds = seconds % dayInSeconds;
	} else if ( seconds == dayInSeconds ) {
		return '24:00';
	}

	const x = ( new Date( seconds * 1000 ).toUTCString() ).match( /\d{2}:\d{2}:\d{2}/ )[ 0 ].split( ':' );

	if ( x[ 2 ] === '00' ) {
		x.splice( 2, 1 );
	}

	if ( x[ 0 ] === '00' ) {
		x[ 0 ] = 0;
	}

	if ( d ) {
		x[ 0 ] = parseInt( x[ 0 ] ) + ( d * 24 );
	}

	const html = x.join( ':' );

	return html;
}

const toggleSidebarHandler = function toggleSidebarHandler( event ) {
	LP.Cookies.set( 'sidebar-toggle', event.target.checked );
};

export { toggleSidebarHandler };

const initCourseTabs = function() {
	$( '#learn-press-course-tabs' ).on( 'change', 'input[name="learn-press-course-tab-radio"]', function() {
		const selectedTab = $( 'input[name="learn-press-course-tab-radio"]:checked' ).val();

		LP.Cookies.set( 'course-tab', selectedTab );

		$( 'label[for="' + $( event.target ).attr( 'id' ) + '"]' ).closest( 'li' ).addClass( 'active' ).siblings().removeClass( 'active' );
	} );
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
		const response = await wp.apiFetch( {
			path: 'lp/v1/courses/enroll-course',
			method: 'POST',
			data: { id },
		} );

		btnEnroll.classList.remove( 'loading' );
		btnEnroll.disabled = false;

		const { status, redirect, message } = response;

		if ( message && status ) {
			formEnroll.innerHTML += `<div class="lp-enroll-notice ${ status }">${ message }</div>`;
		}

		if ( status === 'success' && redirect ) {
			window.location.href = redirect;
		}

		return response;
	};

	formEnroll.addEventListener( 'submit', ( event ) => {
		event.preventDefault();

		const id = formEnroll.querySelector( 'input[name=enroll-course]' ).value;
		const btnEnroll = formEnroll.querySelector( 'button.button-enroll-course' );

		btnEnroll.classList.add( 'loading' );
		btnEnroll.disabled = true;

		submit( id, btnEnroll );
	} );
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

	// Popup only
	if ( $popup.length ) {
		$curriculum.on( 'scroll', throttle( function() {
			const $self = $( this );

			$self.addClass( 'scrolling' );

			timerClearScroll && clearTimeout( timerClearScroll );

			timerClearScroll = setTimeout( () => {
				$self.removeClass( 'scrolling' );
			}, 1000 );
		}, 500 ) );

		$( '#sidebar-toggle' ).on( 'change', toggleSidebarHandler );

		LP.toElement( '.course-item.current', { container: '.curriculum-scrollable:eq(1)', offset: 100, duration: 1 } );
	}

	$curriculum.find( '.section-desc' ).each( ( i, el ) => {
		const a = $( '<span class="show-desc"></span>' ).on( 'click', () => {
			b.toggleClass( 'c' );
		} );
		const b = $( el ).siblings( '.section-title' ).append( a );
	} );

	initCourseTabs();
	initCourseSidebar();
	enrollCourse();

	$( '.section' ).each( function() {
		const $section = $( this ),
			$toggle = $section.find( '.section-toggle' );
		$toggle.on( 'click', function() {
			const isClose = $section.toggleClass( 'closed' ).hasClass( 'closed' );
			const sections = LP.Cookies.get( 'closed-section-' + lpGlobalSettings.post_id ) || [];
			const sectionId = parseInt( $section.data( 'section-id' ) );
			const at = sections.findIndex( ( id ) => {
				return id == sectionId;
			} );

			if ( isClose ) {
				sections.push( parseInt( $section.data( 'section-id' ) ) );
			} else {
				sections.splice( at, 1 );
			}

			LP.Cookies.remove( 'closed-section-(.*)' );
			LP.Cookies.set( 'closed-section-' + lpGlobalSettings.post_id, [ ...new Set( sections ) ] );
		} );
	} );

	$( '.learn-press-progress' ).each( function() {
		const $progress = $( this );
		const $active = $progress.find( '.learn-press-progress__active' );
		const value = $active.data( 'value' );

		if ( value === undefined ) {
			return;
		}

		$active.css( 'left', -( 100 - parseInt( value ) ) + '%' );
	} );

	LP.Hook.doAction( 'course-ready' );
} );
