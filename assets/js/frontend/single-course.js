/**
 * Single course functions
 */
if( typeof LearnPress == 'undefined' ){
	window.LearnPress = {}
}
;(function($){
	var LearnPress_View_Course = window.LearnPress_View_Course = Backbone.View.extend({
		$doc: null,
		$body: null,
		el: '.course-summary',
		events:{
			'click .curriculum-sections .section-content > li.course-lesson a': '_loadLesson',
			'click .course-item-nav a': '_loadLesson'
		},
		initialize: function(){
			this.$doc = $(document);
			this.$body = $(document.body);

		},
		_loadLesson: function(e){
			e.preventDefault();
			this.loadLesson( $(e.target).attr('href') );
		},
		loadLesson: function( permalink ){
			LearnPress.MessageBox.blockUI();
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
					LearnPress.MessageBox.hide();
				},
				error: function(){
					// TODO: handle the error here
					LearnPress.MessageBox.hide();
				}
			})
		}
	});

	$(document).ready(function(){
		//LearnPress.Course.init( $(this), $(document.body) );
		LearnPress.Course = new LearnPress_View_Course();
	});
})(jQuery);