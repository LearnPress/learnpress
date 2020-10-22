import SingleCourse from './single-course/index';

export default SingleCourse;

export const init = () => {
	wp.element.render(
		<SingleCourse />,
	);
};

const $ = jQuery;
const { debounce, throttle } = lodash;

const { _x } = wp.i18n;

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

const AjaxSearchCourses = function( el ) {
	const $form = $( el );
	const $ul = $( '<ul class="search-results"></ul>' ).appendTo( $form );
	const $input = $form.find( 'input[name="s"]' );
	let paged = 1;

	const submit = async function( e ) {
		e.preventDefault();
		const response = await wp.apiFetch( {
			path: 'lp/v1/courses/search?s=' + $input.val() + '&page=' + paged,
		} );

		const {
			courses,
			num_pages,
			page,
		} = response.results;
		$ul.html( '' );

		if ( courses.length ) {
			courses.map( ( course ) => {
				$ul.append( `<li class="search-results__item">
                    <a href="${ course.url }">
                    ` + ( course.thumbnail.small ? `<img src="${ course.thumbnail.small }" />` : '' ) + `
                        <h4 class="search-results__item-title">${ course.title }</h4>
                        <span class="search-results__item-author">${ course.author }</span>
                        ${ course.price_html }
                        </a>
                    </li>` );
			} );

			if ( num_pages > 1 ) {
				$ul.append( `<li class="search-results__pagination">
                  ` + ( [ ...Array( num_pages ).keys() ].map( ( i ) => {
					return i === paged - 1 ? '<span>' + ( i + 1 ) + '</span>' : '<a data-page="' + ( i + 1 ) + '">' + ( i + 1 ) + '</a>';
				} ) ).join( '' ) + `
                </li>` );
			}
		} else {
			$ul.append( '<li class="search-results__not-found">' + _x( 'No course found!', 'ajax search course not found', 'learnpress' ) + '</li>' );
		}

		$form.addClass( 'searching' );

		return false;
	};

	$input.on( 'keyup', debounce( function( e ) {
		paged = 1;
		if ( e.target.value.length < 3 ) {
			return;
		}
		submit( e );
	}, 300 ) );
	$form.on( 'click', '.clear', () => {
		$form.removeClass( 'searching' );
		$input.val( '' );
	} ).on( 'click', '.search-results__pagination a', ( e ) => {
		e.preventDefault();
		paged = $( e.target ).data( 'page' );
		submit( e );
	} );
};

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

export {
	initCourseTabs,
	initCourseSidebar,
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
