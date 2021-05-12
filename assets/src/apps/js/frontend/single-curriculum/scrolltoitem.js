const $ = jQuery;

const scrollToItemCurrent = {
	init() {
		this.scrollToItemViewing = function() {
			const elItemViewing = $( '.viewing-course-item' );
			if ( elItemViewing.length ) {
				const heightCourseItemContentHeader = $( '#popup-sidebar' ).outerHeight();
				const heightSectionTitle = $( '.section-title' ).outerHeight();
				const heightSectionHeader = $( '.section-header' ).outerHeight();
				const regex = new RegExp( '^viewing-course-item-([0-9].*)' );
				const classList = elItemViewing.attr( 'class' );
				const classArr = classList.split( /\s+/ );
				let idItem = 0;

				$.each( classArr, function( i, className ) {
					const compare = regex.exec( className );

					if ( compare ) {
						idItem = compare[ 1 ];
						return false;
					}
				} );

				if ( 0 === idItem ) {
					return;
				}

				const elItemCurrent = $( '.course-item-' + idItem );
				const offSetTop = elItemCurrent.offset().top;
				let numberOffSetTop = offSetTop - heightCourseItemContentHeader;

				if ( undefined === heightSectionTitle ) {
					numberOffSetTop = numberOffSetTop - heightSectionHeader + 20;
				} else {
					numberOffSetTop = numberOffSetTop - heightSectionTitle;
				}
				$( '#learn-press-course-curriculum' ).animate( {
					scrollTop: numberOffSetTop + 300,
				}, 800 );
			}
		};
		this.scrollToItemViewing();
	},
};

export default scrollToItemCurrent;
