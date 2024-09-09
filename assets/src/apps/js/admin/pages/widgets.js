const $ = jQuery;

function formatCourse( repo ) {
	if ( repo.loading ) {
		return repo.text;
	}
	const markup = "<div class='select2-result-course_title'>" + repo.id + ' - ' + repo.title.rendered + '</div>';
	return markup;
}

function formatCourseSelection( repo ) {
	return repo.title.rendered || repo.text;
}

/*function autocompleteWidget( widget = null ) {
	const searchs = $( '.lp-widget_select_course' );
	const wpRestUrl = searchs.data( 'rest-url' );
	const postType = searchs.data( 'post_type' ) || 'lp_course';

	if ( postType === 'lp_course' ) {
		const dataStruct = {
			urlApi: Api.admin.apiSearchCourses,
			dataType: 'courses',
			keyGetValue: {
				value: 'ID',
				text: '{{post_title}} (#{{ID}})',
				key_render: {
					post_title: 'post_title',
					ID: 'ID',
				},
			},
		};
		const dataStructJson = JSON.stringify( dataStruct );
		const widgetListEl = document.querySelector( '#widget-list' );
		let nonTomSelectWidgetEls = '';
		if ( widgetListEl ) {
			const tomSelectWidgetEls = Array.prototype.slice.call( widgetListEl.querySelectorAll( '.lp-widget_select_course' ) );
			nonTomSelectWidgetEls = Array.prototype.slice.call( searchs ).filter( ( el ) => ! tomSelectWidgetEls.includes( el ) );
		}

		nonTomSelectWidgetEls.forEach( ( nonTomSelectWidgetEl ) => {
			$( nonTomSelectWidgetEl ).attr( 'data-struct', dataStructJson );
			initTomSelect( nonTomSelectWidgetEl );
		} );
	} else {
		searchs.select2( {
			ajax: {
				method: 'GET',
				url: wpRestUrl + 'wp/v2/' + postType,
				dataType: 'json',
				delay: 250,
				data( params ) {
					return {
						search: params.term,
					};
				},
				processResults( data, params ) {
					params.page = params.page || 1;

					return {
						results: data,
					};
				},
				cache: true,
			},
			escapeMarkup( markup ) {
				return markup;
			},
			minimumInputLength: 2,
			templateResult: formatCourse, // omitted for brevity, see the source of this page
			templateSelection: formatCourseSelection, // omitted for brevity, see the source of this page
		} );
	}
}*/

document.addEventListener( 'DOMContentLoaded', function( event ) {
	if ( document.querySelector( '#widgets-editor' ) ) {
		$( document ).on( 'widget-added', function( event, widget ) {
			//autocompleteWidget( widget );
		} );
	} else {
		$( document ).on( 'learnpress/widgets/select', function() {
			//autocompleteWidget();
			sortItem();
		} );

		//autocompleteWidget();
	}
} );

//Sortable checkbox
document.addEventListener( 'DOMContentLoaded', function( event ) {
	sortItem();
} );

$( document ).on( 'widget-added widget-updated', function( event ) {
	sortItem();
} );

function sortItem() {
	$( '.widget-content .sortable' ).sortable( {
		handle: '.drag',

		start( event, ui ) {

		},
		update( event, ui ) {
			const value = [];
			$( this ).children().map( function() {
				value.push( $( this ).find( 'input' ).val() );
			} );

			value.join( ',' );

			const fieldSort = $( this ).closest( '.widget-content' ).find( 'input.fields-sort' );
			fieldSort.val( value );
			fieldSort.trigger( 'change' );
		},
		stop( event, ui ) {

		},
	} );
}
