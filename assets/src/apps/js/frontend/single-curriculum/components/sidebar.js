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
	}
	// End editor by tungnx

	document.addEventListener( 'click', ( e ) => {
		if ( e.target.id === 'sidebar-toggle' ) {
			LP.Cookies.set( 'sidebar-toggle', e.target.checked ? true : false );
			toggleSidebar( LP.Cookies.get( 'sidebar-toggle' ) );
		}
	} );

	// Right Sidebar Toggle (open / close)
	const rightSidebar = document.querySelector( '#popup-right-sidebar' );
	if ( rightSidebar ) {
		const isExpandedCookie = window.LP?.Cookies ? LP.Cookies.get( 'popup-right-sidebar-expanded' ) : null;

		// Restore saved state from cookie
		if ( isExpandedCookie === 'no' ) {
			rightSidebar.classList.remove( 'is-expanded' );
			rightSidebar.classList.add( 'is-collapsed' );
		} else {
			rightSidebar.classList.add( 'is-expanded' );
			rightSidebar.classList.remove( 'is-collapsed' );
		}
		const toggleRightSidebar = () => {
			const willExpand = ! rightSidebar.classList.contains( 'is-expanded' );
			rightSidebar.classList.toggle( 'is-expanded', willExpand );
			rightSidebar.classList.toggle( 'is-collapsed', ! willExpand );
			if ( window.LP?.Cookies ) {
				LP.Cookies.set( 'popup-right-sidebar-expanded', willExpand ? 'yes' : 'no' );
			}
		};

		const toggleBtn = rightSidebar.querySelector( '.popup-right-sidebar__toggle' );
		if ( toggleBtn ) {
			toggleBtn.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				toggleRightSidebar();
			} );
		}
	}
};
