/**
 * Single course functions
 */
if( typeof LearnPress == 'undefined' ){
	window.LearnPress = {}
}
;(function($){
	LearnPress = $.extend( LearnPress, {
		Course: {
			$doc: null,
			$body: null,
			init: function( $doc, $body ){
				this.$doc = $doc;
				this.$body = $body;

				this.$doc.on('click', '.curriculum-sections .section-content > li.course-lesson a', $.proxy(function(e){
					e.preventDefault();
					this.loadLesson( $(e.target).attr('href') );
				}, this))
			},
			loadLesson: function( permalink ){
				LearnPress.setUrl( permalink );
				$.ajax({
					url    : permalink,
					success: function (response) {
						var $html = $(response),
							$newLesson = $html.find('#learn-press-course-lesson-summary'),
							$newHeading = $html.find('#learn-press-course-lesson-heading');

						$('title').html($html.filter('title').text());
						$('#learn-press-course-description-heading, #learn-press-course-lesson-heading').replaceWith($newHeading)
						$('#learn-press-course-description, #learn-press-course-lesson-summary').replaceWith($newLesson);

						LearnPress.toElement( '#learn-press-course-lesson-heading' );
					},
					error: function(){
						// TODO: handle the error here
					}
				})
			}
		}
	});
	$(document).ready(function(){
		LearnPress.Course.init( $(this), $(document.body) );
	});
})(jQuery);