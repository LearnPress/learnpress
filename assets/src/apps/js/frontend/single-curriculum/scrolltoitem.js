import lpModalOverlay from '../../utils/lp-modal-overlay';

const $ = jQuery;

const scrollToItemCurrent = {
	init() {
		this.scrollToItemViewing = function() {
			const elItemViewing = $( '.viewing-course-item' );
			if ( elItemViewing.length ) {
				const elCourseCurriculumn = $( '#learn-press-course-curriculum' );
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
				const offset = elItemCurrent.offset().top - elCourseCurriculumn.offset().top +
					elCourseCurriculumn.scrollTop();

				elCourseCurriculumn.animate( {
					scrollTop: LP.Hook.applyFilters( 'scroll-item-current', offset - heightSectionHeader ),
				}, 800 );
			}
		};
		this.scrollToItemViewing();
	},
};

export default scrollToItemCurrent;
