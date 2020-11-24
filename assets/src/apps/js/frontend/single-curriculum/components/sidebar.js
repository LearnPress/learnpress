const $ = jQuery;
const { throttle } = lodash;

export const Sidebar = () => {
	const $popup = $( '#popup-course' );
	const $curriculum = $( '#learn-press-course-curriculum' );
	let timerClearScroll;

	$( '#sidebar-toggle' ).on( 'change', ( event ) => {
		LP.Cookies.set( 'sidebar-toggle', event.target.checked );

		toggleSidebar( event.target.checked );
	} );

	const toggleSidebar = ( toggle ) => {
		$( 'body' ).removeClass( 'lp-sidebar-toggle__open' );
		$( 'body' ).removeClass( 'lp-sidebar-toggle__close' );

		if ( toggle ) {
			$( 'body' ).addClass( 'lp-sidebar-toggle__close' );
		} else {
			$( 'body' ).addClass( 'lp-sidebar-toggle__open' );
		}
	};

	toggleSidebar( LP.Cookies.get( 'sidebar-toggle' ) );

	$curriculum.find( '.section-desc' ).each( ( i, el ) => {
		const a = $( '<span class="show-desc"></span>' ).on( 'click', () => {
			b.toggleClass( 'c' );
		} );
		const b = $( el ).siblings( '.section-title' ).append( a );
	} );

	$( '.section' ).each( function() {
		const $section = $( this ),
			$toggle = $section.find( '.section-left' );

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

		LP.toElement( '.course-item.current', { container: '.curriculum-scrollable:eq(1)', offset: 100, duration: 1 } );
	}
};

