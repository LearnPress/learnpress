/**
 * Plugin: Tabs
 */
( function( $ ) {
	const adminTabs = function( $el, options ) {
		let $tabs = $el.find( '.tabs-nav' ).find( 'li' ),
			$tabsWrap = $tabs.parent(),
			$contents = $el.find( '.tabs-content-container > li' ),
			$currentTab = null,
			$currentContent = null;

		function selectTab( $tab ) {
			const index = $tabs.index( $tab ),
				url = $tab.find( 'a' ).attr( 'href' );

			$currentContent = $contents.eq( index );

			$tab.addClass( 'active' ).siblings( 'li.active' ).removeClass( 'active' );
			$currentContent.show().css( { visibility: 'hidden' } );
			calculateHeight( $currentContent );
			$currentContent.hide();
			$currentContent.show();
			$currentContent.siblings( 'li.active' ).fadeOut( 0, function() {
				$currentContent.addClass( 'active' ).siblings( 'li.active' ).removeClass( 'active' );
			} );

			LP.setUrl( url );
		}

		function calculateHeight( $currentContent ) {
			let contentHeight = $currentContent.height(),
				tabsHeight = $tabsWrap.outerHeight();

			if ( contentHeight < tabsHeight ) {
				contentHeight = tabsHeight + parseInt( $tabsWrap.css( 'margin' ) ) * 2;
			} else {
				contentHeight = undefined;
			}
			$currentContent.css( 'visibility', '' ).css( { height: contentHeight } );
		}

		function selectDefaultTab() {
			$currentTab = $tabs.filter( '.active' );
			if ( ! $currentTab.length ) {
				$currentTab = $tabs.first();
			}
			$currentTab.find( 'a' ).trigger( 'click' );
		}

		function initEvents() {
			$el.on( 'click', '.tabs-nav a', function( event ) {
				event.preventDefault();
				$currentTab = $( this ).parent();
				selectTab( $currentTab );
			} );
		}

		function init() {
			initEvents();
			selectDefaultTab();
			$( window ).on( 'resize.calculate-tab', function() {
				const $currentContent = $el.find( '.tabs-content-container .active' ).css( 'height', '' );
				calculateHeight( $currentContent );
			} );
		}

		init();
	};
	$.fn.LP( 'AdminTab', function( options ) {
		options = $.extend( {}, options || {} );
		return $.each( this, function() {
			let $el = $( this ),
				tabs = $el.data( 'learn-press/tabs' );

			if ( ! tabs ) {
				tabs = new adminTabs( $el, options );
				$el.data( 'learn-press/tabs', tabs );
			}
			return $el;
		} );
	} );
}( jQuery ) );
