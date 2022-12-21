const $ = jQuery;
const { throttle } = lodash;

export const Sidebar = () => {
	// Tungnx - Show/hide sidebar curriculumn
	const elSidebarToggle = document.querySelector( '#sidebar-toggle' );

	// For style of theme
	const toggleSidebar = ( toggle ) => {
		$( 'body' ).removeClass( 'lp-sidebar-toggle__open' );
		$( 'body' ).removeClass( 'lp-sidebar-toggle__close' );

		if ( toggle ) {
			$( 'body' ).addClass( 'lp-sidebar-toggle__close' );
		} else {
			$( 'body' ).addClass( 'lp-sidebar-toggle__open' );
		}
	};

	// For lp and theme
	if ( elSidebarToggle ) {
		if ( $( window ).innerWidth() <= 768 ) {
			elSidebarToggle.setAttribute( 'checked', 'checked' );
		} else if ( LP.Cookies.get( 'sidebar-toggle' ) ) {
			elSidebarToggle.setAttribute( 'checked', 'checked' );
		} else {
			elSidebarToggle.removeAttribute( 'checked' );
		}

		document.querySelector( '#popup-course' ).addEventListener( 'click', ( e ) => {
			if ( e.target.id === 'sidebar-toggle' ) {
				LP.Cookies.set( 'sidebar-toggle', e.target.checked ? true : false );
				toggleSidebar( LP.Cookies.get( 'sidebar-toggle' ) );
			}
		} );
	}
	// End editor by tungnx

	const $curriculum = $( '#learn-press-course-curriculum' );
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
};

