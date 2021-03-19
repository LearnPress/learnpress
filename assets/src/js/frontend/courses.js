( function( $ ) {
	const { debounce } = lodash;

	const fetchCourses = function( args ) {
		const url = args.url || lpGlobalSettings.courses_url;
		const $wrapElement = args.wrapElement || '.lp-archive-courses';

		delete args.url;
		delete args.wrapElement;

		LP.setUrl( url );

		$( '.lp-archive-courses' ).addClass( 'loading' );

		return new Promise( ( resolve, reject ) => {
			$.ajax( {
				url,
				data: $.extend( {}, args || {} ),
				type: 'POST',
				success: ( response ) => {
					let newEl = $( response ).contents().find( $wrapElement );

					if ( $( 'body' ).hasClass( 'twentytwenty' ) && $wrapElement === '.lp-archive-courses' ) {
						newEl = $( response ).filter( $wrapElement );
					}

					if ( newEl.length > 0 ) {
						$( $wrapElement ).replaceWith( newEl );
					} else {
						$( $wrapElement ).html( 'LearnPress: No Content.' );
					}

					bindEventCoursesLayout();

					$( 'html, body' ).animate( {
						scrollTop: ( $( $wrapElement ).offset().top - 100 ),
					}, 200 );

					resolve( newEl );

					$( '.lp-archive-courses' ).removeClass( 'loading' );
				},
				error: ( response ) => {
					reject();
					$( '.lp-archive-courses' ).removeClass( 'loading' );
				},
			} );
		} );
	};

	/**
	 * Ajax searching when user typing on search-box.
	 *
	 * @param event
	 */
	const searchCourseHandler = debounce( ( event ) => {
		event.preventDefault();

		fetchCourses( {
			s: $( event.target ).val(),
			post_type: 'lp_course',
			wrapElement: '.learn-press-courses',
		} );
	}, 600 );

	/**
	 * Switch layout between Grid and List.
	 *
	 * @param event
	 */
	const switchCoursesLayoutHandler = function( event ) {
		let $target;
		let $parent = $( this ).parent();

		while ( ! $target || ! $target.length ) {
			$target = $parent.find( '.learn-press-courses' );
			$parent = $parent.parent();
		}

		$target.attr( 'data-layout', this.value );
		LP.Cookies.set( 'courses-layout', this.value );
	};

	const selectCoursesLayout = function() {
		const coursesLayout = LP.Cookies.get( 'courses-layout' );
		const switches = $( '.lp-courses-bar .switch-layout' ).find( '[name="lp-switch-layout-btn"]' );

		if ( coursesLayout ) {
			switches
				.filter( '[value="' + coursesLayout + '"]' )
				.prop( 'checked', true )
				.trigger( 'change' );
		}
	};

	const coursePaginationHandler = function( event ) {
		event.preventDefault();

		let permalink = $( event.target ).attr( 'href' );
		const s = $( '.search-courses input[name="s"]' ).val();

		if ( ! permalink ) {
			return;
		}

		if ( s ) {
			permalink = permalink.addQueryVar( 's', s );
		} else {
			permalink = permalink.removeQueryVar( 's' );
		}

		fetchCourses( {
			url: permalink,
		} );
	};

	const bindEventCoursesLayout = function() {
		$( '.lp-archive-courses' )
			.on( 'keyup', '.search-courses input[name="s"]', searchCourseHandler )
			.on( 'change', 'input[name="lp-switch-layout-btn"]', switchCoursesLayoutHandler )
			.on( 'click', '.learn-press-pagination .page-numbers', coursePaginationHandler );
	};

	$( document ).ready( function() {
		bindEventCoursesLayout();
		selectCoursesLayout();
	} );
}( jQuery ) );
