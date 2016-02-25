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
			//'click .curriculum-sections .section-content > li.course-lesson a': '_loadLesson',
			//'click .course-item-nav a': '_loadLesson'
			'click #learn-press-finish-course': '_finishCourse'
		},
		initialize: function(){
			_.bindAll( this, '_sanitizeProgress' )
			this.$doc = $(document);
			this.$body = $(document.body);
			this._sanitizeProgress();
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
		},
		_finishCourse: function(e){
			var $button = $(e.target),
				data = $button.dataToJSON();
			console.log(data);
			//LearnPress.finishCourse( );
		},
		finishCourse: function(){
			if (!confirm(confirm_finish_course.confirm_finish_course)) return;
			$.ajax({
				type   : "POST",
				url    : ajaxurl,
				data   : {
					action   : 'learnpress_finish_course',
					course_id: course_id
				},
				success: function (response) {
					if (response.finish) {
						LearnPress.reload();
					}
				}
			});
		},
		_sanitizeProgress: function(){
			var $el = $( '.lp-course-progress'),
				$progress = $('.lp-progress-value', $el),
				$passing = $('.lp-passing-conditional', $el),
				value = parseFloat( $el.attr('data-value') ),
				passing_condition = parseFloat( $el.attr('data-passing-condition')),
				_done = function(){
					var progress = parseInt($progress.css('width')),
						passing = parseInt( $passing.css('left'));
					if( progress < $('span', $progress).outerWidth()){
						$progress.addClass('left')
					}else{
						$progress.removeClass('left')
					}
					if( ($el.outerWidth() - passing) < $('span', $passing).outerWidth() ){
						$passing.addClass('right')
					}else{
						$passing.removeClass('right')
					}
					if(value >= passing_condition){
						$el.addClass('passed');
					}
				};
				$progress.css('width', value + '%').find('span span').html(value);
				setTimeout(_done, 500);

		}
	});

	$(document).ready(function(){
		//LearnPress.Course.init( $(this), $(document.body) );
		LearnPress.Course = new LearnPress_View_Course();
	});
})(jQuery);