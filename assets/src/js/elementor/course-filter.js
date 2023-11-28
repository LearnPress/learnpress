const classCourseFilter = 'lp-form-course-filter';

// Click element
document.addEventListener( 'click', function( e ) {
    const target = e.target;

	if ( target.classList.contains( 'course-filter-reset' ) ) {
		e.preventDefault();
		window.lpCourseFilterEl.resetList( target );
	}

    if ( target.classList.contains( 'lp-button-popup' ) || target.classList.contains( 'icon-align-right' ) || target.classList.contains( 'icon-align-left' ) || target.classList.contains( 'selected-filter' ) ) {
		e.preventDefault();
		const elLpCourseFilter = target.closest( '.elementor-widget-learnpress_filter_course' );
		if ( ! elLpCourseFilter ) {
			return;
		}
		elLpCourseFilter.classList.toggle("filter-popup-show");
	}

	if ( target.classList.contains( 'icon-toggle-filter' )) {
		e.preventDefault();
		const toggleContent = target.closest( '.toggle-content' );
		if ( ! toggleContent ) {
			return;
		}
		const toggleOn = target.closest( '.toggle-on' );
		
		if (  ! toggleOn ) {
			toggleContent.classList.add("toggle-on");
		}else {
			toggleContent.classList.remove("toggle-on");
		}
	}

	if ( target.classList.contains( 'filter-bg' ) ) {
		const elLpCourseFilter = target.closest( '.elementor-widget-learnpress_filter_course' );
		if ( ! elLpCourseFilter ) {
			return;
		}
		elLpCourseFilter.classList.remove("filter-popup-show");
	}

	if ( target.classList.contains( 'icon-remove-selected' ) ) {
		e.preventDefault();
		window.lpCourseFilterEl.resetSelected( target );
	}
});

window.lpCourseFilterEl = {
	resetList: ( btnReset ) => {
		const elSelectedList = document.querySelector( '.selected-list' );
		if ( elSelectedList ) {
			elSelectedList.remove();
		}
	},
    resetSelected: ( target ) => {
		const form = document.querySelector( `.${ classCourseFilter }` );
		const btnSubmit = form.querySelector( '.course-filter-submit' );
		const lpSelected = target.closest( '.selected-item' );
		const lpSelectedName = lpSelected.getAttribute( 'data-name' ); 
		const lpSelectedID = lpSelected.getAttribute( 'data-value' );

		if ( ! lpSelected ) {
			return;
		}
		for ( let i = 0; i < form.elements.length; i++ ) {
			if(form.elements[ i ].getAttribute('name') ==  lpSelectedName && form.elements[ i ].getAttribute('value') == lpSelectedID){
				form.elements[ i ].removeAttribute( 'checked' );
			}
		}
		if ( lpGlobalSettings.is_course_archive ) {
			btnSubmit.click();
		}
		if ( lpSelected ) {
			lpSelected.remove();
		}
		// Load AJAX widget by params
		window.lpCourseFilter.loadWidgetFilterREST( form );
	},
}