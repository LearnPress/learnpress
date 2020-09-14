import SingleCourse from './single-course/index';
import { apiFetch } from './data-controls';

const $ = jQuery;
const {
	debounce,
} = lodash;

const { _x } = wp.i18n;

export function formatDuration( seconds ) {
	let html;
	let x, d;
	const day_in_seconds = 3600 * 24;

	if ( seconds > day_in_seconds ) {
		d = ( seconds - seconds % day_in_seconds ) / day_in_seconds;
		seconds = seconds % day_in_seconds;
	} else if ( seconds == day_in_seconds ) {
		return '24:00';
	}

	x = ( new Date( seconds * 1000 ).toUTCString() ).match( /\d{2}:\d{2}:\d{2}/ )[ 0 ].split( ':' );

	if ( x[ 2 ] === '00' ) {
		x.splice( 2, 1 );
	}

	if ( x[ 0 ] === '00' ) {
		x[ 0 ] = 0;
	}

	if ( d ) {
		x[ 0 ] = parseInt( x[ 0 ] ) + d * 24;
	}

	html = x.join( ':' );

	return html;
}

const toggleSidebarHandler = function toggleSidebarHandler( event ) {
	LP.Cookies.set( 'sidebar-toggle', event.target.checked );
};

export { toggleSidebarHandler };

const createCustomScrollbar = function( element ) {
	[].map.call( arguments, ( element ) => {
		$( element ).each( function() {
			$( this )
				.addClass( 'scrollbar-light' )
				.css( {
					opacity: 1,
				} )
				.scrollbar( {
					scrollx: false,
				} )
				.parent()
				.css( {
					position: 'absolute',
					top: 0,
					bottom: 0,
					width: '100%',
					opacity: 1,
				} );
		} );
	} );
};

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

const AjaxSearchCourseContent = function AjaxSearchCourseContent( el ) {
	const $form = $( el );
	const $list = $( '#learn-press-course-curriculum' );
	const $input = $form.find( 'input[name="s"]' );
	let paged = 1;
	const $sections = $list.find( '.section' );
	const $items = $list.find( '.course-item' );
	let isSearching = false;
	let oldSearch = '';

	var submit = async function( e ) {
		e.preventDefault();

		if ( isSearching ) {
			return false;
		}

		if ( $input.val().length < 3 ) {
			$items.removeClass( 'hide-if-js' );
			$sections.removeClass( 'hide-if-js' );
			return;
		}

		isSearching = true;
		oldSearch = $input.val();
		$form.addClass( 'searching' );

		const response = await wp.apiFetch( {
			path: 'lp/v1/courses/' + lpGlobalSettings.post_id + '/search-content?s=' + $input.val(),
		} );

		$items.each( function() {
			const $it = $( this );
			if ( response.items.indexOf( $it.data( 'id' ) ) !== -1 ) {
				$it.removeClass( 'hide-if-js' );
			} else {
				$it.addClass( 'hide-if-js' );
			}
		} );

		$sections.each( function() {
			const $section = $( this );
			if ( $section.find( '.course-item:not(.hide-if-js)' ).length === 0 ) {
				$section.addClass( 'hide-if-js' );
			} else {
				$section.removeClass( 'hide-if-js' );
			}
		} );

		isSearching = false;

		if ( oldSearch !== $input.val() ) {
			return submit( e );
		}

		return false;
	};

	$input.on( 'keyup', debounce( function( e ) {
		paged = 1;
		submit( e );
	}, 300 ) );

	$form.on( 'submit', submit );

	$form.on( 'click', '.clear', ( e ) => {
		$form.removeClass( 'searching' );
		$input.val( '' );
		submit( e );
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

const initItemComments = function initItemComments() {
	const $toggle = $( '#learn-press-item-comments-toggle' );
	$toggle.on( 'change', async function() {
		console.log( this.checked );
		if ( ! $toggle[ 0 ].checked ) {
			return;
		}

		const response = await wp.apiFetch( {
			path: 'lp/v1/courses/14242/item-comments/14266',
		} );

		$( '.learn-press-comments' ).html( response.comments );

		new LP.IframeSubmit( '#commentform' );
	} );
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
		$curriculum.scroll( lodash.throttle( function() {
			const $self = $( this );

			$self.addClass( 'scrolling' );
			timerClearScroll && clearTimeout( timerClearScroll );
			timerClearScroll = setTimeout( () => {
				$self.removeClass( 'scrolling' );
			}, 1000 );
		}, 500 ) );

		$( '#sidebar-toggle' ).on( 'change', toggleSidebarHandler );

		new AjaxSearchCourseContent( $popup.find( '.search-course' ) );

		createCustomScrollbar( $curriculum.find( '.curriculum-scrollable' ), $( '#popup-content' ).find( '.content-item-scrollable' ) );

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
	initItemComments();

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
			//$section.find('.section-content').slideToggle();
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

	$( window ).on( 'resize.popup-nav', debounce( () => {
		const marginLeft = $( '#popup-sidebar' ).width() / 2;
		const width = $( '#learn-press-quiz-app' ).width();

		$( '.quiz-buttons .button-left.fixed' ).css( { width, marginLeft } );
	}, 300 ) ).trigger( 'resize.popup-nav' );
} );
