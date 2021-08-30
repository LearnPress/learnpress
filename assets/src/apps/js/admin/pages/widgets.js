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

function autocompleteWidget( widget = null ) {
	const searchs = $( '.lp-widget_select_course' );
	const wpRestUrl = searchs.data( 'rest-url' );
	const postType = searchs.data( 'post_type' ) || 'lp_course';

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

document.addEventListener( 'DOMContentLoaded', function( event ) {
	if ( document.querySelector( '#widgets-editor' ) ) {
		$( document ).on( 'widget-added', function( event, widget ) {
			autocompleteWidget( widget );
		} );
	} else {
		$( document ).on( 'learnpress/widgets/select', function() {
			autocompleteWidget();
		} );

		autocompleteWidget();
	}
} );
