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
			sortItem();
			showItems();
		} );

		autocompleteWidget();
	}
} );

//Sortable checkbox
document.addEventListener( 'DOMContentLoaded', function( event ) {
	sortItem();
	showItems();
} );

$( document ).on( 'widget-added widget-updated', function( event ) {
	sortItem();
	showItems();
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

function showItems() {
	const enableInputs = $( '.widget-content input.enable_widget' );

	enableInputs.each( function() {
		if ( ! this.checked ) {
			$( this ).closest( 'form' ).find( '.widget-content > div,p:not(:has(.enable_widget))' ).hide();
		}

		$( this ).change( function() {
			const element = $( this ).closest( 'form' ).find( '.widget-content > div,p:not(:has(.enable_widget))' );
			if ( this.checked ) {
				element.show();
			} else {
				element.hide();
			}
		} );
	} );
}
