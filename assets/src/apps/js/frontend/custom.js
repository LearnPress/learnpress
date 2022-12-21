/**
 * Custom functions for frontend quiz.
 */

const { Hook } = LP;

const $ = window.jQuery || jQuery;

Hook.addFilter( 'question-blocks', function( blocks ) {
	return blocks; ///[ 'answer-options', 'title', 'content', 'hint', 'explanation'];
} );

Hook.addAction( 'before-start-quiz', function() {
} );

Hook.addAction( 'quiz-started', function( results, id ) {
	$( `.course-item-${ id }` ).removeClass( 'status-completed failed passed' ).addClass( 'has-status status-started' );

	window.onbeforeunload = function() {
		return 'Warning!';
	};
} );

Hook.addAction( 'quiz-submitted', function( response, id ) {
	$( `.course-item-${ id }` ).removeClass( 'status-started passed failed' ).addClass( `has-status status-completed ${ response.results.graduation }` );
	window.onbeforeunload = null;
} );
