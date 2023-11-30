const classCourseFilter = 'lp-form-course-filter';

// Click element
document.addEventListener( 'click', function( e ) {
    const target = e.target;

	if ( target.classList.contains( 'clear-selected-list' ) ) {
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

	window.lpCourseFilterEl.updateSelected( target );

});

window.lpCourseFilterEl = {
	resetList: ( target ) => {
		const form = document.querySelector( `.${ classCourseFilter }` );
		const selectedList   = document.querySelector( '.selected-list' );
		const elSelectedList = selectedList.querySelectorAll( '.selected-item' );

		if ( ! elSelectedList ) {
			return; 
		}

		for ( let i = 0; i < elSelectedList.length; i++ ) {
			elSelectedList[i].remove();
		}
		window.lpCourseFilter.reset( form );
		target.remove();
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
				if ( ! form.elements[ i ].getAttribute('checked')){
					form.elements[ i ].click();
				}else {
					form.elements[ i ].removeAttribute( 'checked' );
				}
			}
		}
		if ( lpGlobalSettings.is_course_archive ) {
			btnSubmit.click();
		}
		if ( lpSelected ) {
			lpSelected.remove();
		}

		window.lpCourseFilter.loadWidgetFilterREST( form );
	},
	updateSelected: ( target ) => {
		const form = document.querySelector( `.${ classCourseFilter }` );
		const selectedList = document.querySelector( '.selected-list' );
		const selectedListItem = document.querySelectorAll( '.selected-item' );
		const btnClear 	=	document.querySelector( '.clear-selected-list' );	
		
		if ( target.tagName === 'INPUT' ) {
			const lpSelectedName = target.getAttribute( 'name' ); 
			const lpSelectedID = target.getAttribute( 'value' );

			btnClear.style.display = 'block';

			if ( ! selectedList ){
				return; 
			}
			const parent = target.closest( '.lp-course-filter__field' );

			if ( ! parent ) {
				return;
			}

			for ( let i = 0; i < selectedListItem.length; i++ ) {
				if( selectedListItem[i].getAttribute( 'data-name' ) ==  lpSelectedName && selectedListItem[i].getAttribute( 'data-value' ) == lpSelectedID ){
					selectedListItem[i].remove();
					return;
				}
			}
			selectedList.innerHTML += '<span class="selected-item" data-name="'+ lpSelectedName +'" data-value="'+ lpSelectedID +'">' + parent.querySelector('label').innerHTML + '<i class="icon-remove-selected fas fa-times"></i></span>';

			window.lpCourseFilter.loadWidgetFilterREST( form );
		}else {
			let elChoice;

			if ( target.classList.contains( 'lp-course-filter__field' ) ) {
				elChoice = target;
			}

			const parent = target.closest( '.lp-course-filter__field' );
			if ( parent ) {
				elChoice = parent;
			}

			if ( ! elChoice ) {
				return;
			}

			for ( let i = 0; i < selectedListItem.length; i++ ) {
				if( selectedListItem[i].getAttribute( 'data-name' ) ==  elChoice.querySelector('input').getAttribute('name') && selectedListItem[i].getAttribute( 'data-value' ) == elChoice.querySelector('input').getAttribute('value') ){
					selectedListItem[i].innerHTML = '';
					return;
				}
			}

			window.lpCourseFilter.loadWidgetFilterREST( form );
		}

		if ( selectedListItem.length > 0 || selectedListItem ) {
			btnClear.style.display = 'block';
		}
	},
}