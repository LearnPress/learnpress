const $ = jQuery;
const { throttle } = lodash;

export const Sidebar = () => {
	// Tungnx - Show/hide sidebar curriculumn
	const elSidebarToggle = document.querySelector( '#sidebar-toggle' );

	// For style of theme
	const toggleSidebar = ( toggle ) => {
		$( 'body' ).removeClass( 'lp-sidebar-toggle__open' );
		$( 'body' ).removeClass( 'lp-sidebar-toggle__close' );

		const wrapper = document.querySelector( '.js-block-sidebar' );
		const computedBasis =
			wrapper.dataset.originalFlexBasis || window.getComputedStyle( wrapper ).flexBasis;

		if ( ! wrapper.dataset.originalFlexBasis && computedBasis !== '0px' ) {
			wrapper.dataset.originalFlexBasis = computedBasis;
		}

		if ( toggle ) {
			wrapper.style.flexBasis = '0';
			$( 'body' ).addClass( 'lp-sidebar-toggle__close' );
		} else {
			wrapper.style.flexBasis = computedBasis || 'auto';
			$( 'body' ).addClass( 'lp-sidebar-toggle__open' );
		}
	};

	// For lp and theme
	if ( elSidebarToggle ) {
		if ( $( window ).innerWidth() <= 768 ) {
			elSidebarToggle.setAttribute( 'checked', 'checked' );
		} else if ( LP.Cookies.get( 'sidebar-toggle' ) ) {
			const wrapper = document.querySelector( '.js-block-sidebar' );
			const computedBasis =
				wrapper.dataset.originalFlexBasis || window.getComputedStyle( wrapper ).flexBasis;

			if ( ! wrapper.dataset.originalFlexBasis && computedBasis !== '0px' ) {
				wrapper.dataset.originalFlexBasis = computedBasis;
			}
			wrapper.style.flexBasis = '0';

			elSidebarToggle.setAttribute( 'checked', 'checked' );
		} else {
			elSidebarToggle.removeAttribute( 'checked' );
		}

		document.querySelector( '#sidebar-toggle' ).addEventListener( 'click', ( e ) => {
			LP.Cookies.set( 'sidebar-toggle', e.target.checked ? true : false );
			toggleSidebar( LP.Cookies.get( 'sidebar-toggle' ) );
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

	$( '.section' ).each( function () {
		const $section = $( this ),
			$toggle = $section.find( '.section-left' );

		$toggle.on( 'click', function () {
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
