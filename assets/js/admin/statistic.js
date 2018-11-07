;(function ($) {
	$.fn.LP_Chart_Line = function (data, config) {
		return $.each(this, function () {
			var $elem = $(this),
				$canvas = $('<canvas />');
			$elem.html('');
			$canvas.appendTo($elem);
			new Chart($canvas.get(0).getContext('2d')).Line(data, config);
		});
		//
	}

	$.fn.LP_Statistic_Users = function () {
		if(parseInt($(this).length)===0){
			return;
		}
		return $.each(this, function () {
			var $buttons = $('.chart-buttons button').on('click', function () {
					var $button = $(this),
						type = $button.data('type'),
						from = '', to = '',
						$container = $('#learn-press-chart');
					$buttons.not(this).not('[data-type="user-custom-time"]').prop('disabled', false);
					if (type == 'user-custom-time') {
						from = $('#user-custom-time input[name="from"]').val();
						to = $('#user-custom-time input[name="to"]').val();

						if (from == '' || to == '') {
							return false;
						}
					} else {
						$button.prop('disabled', true)
					}
					$container.addClass('loading')
					$.ajax({
						url     : 'admin-ajax.php',
						data    : {
							action: 'learnpress_load_chart',
							type  : type,
							range : [from, to]
						},
						dataType: 'text',
						success : function (response) {
							response = LP.parseJSON(response);
							$container.LP_Chart_Line(response, LP_Chart_Config);
							$container.removeClass('loading');
						}
					});
					return false;
				}),
				$inputs = $('.chart-buttons #user-custom-time input[type="text"]').change(function () {
					var _valid_date = function () {
						if (new Date($inputs[0].value) < new Date($inputs[1].value)) {
							return true;
						}
					}
					$buttons.filter('[data-type="user-custom-time"]').prop('disabled', $inputs.filter(function () {
							return this.value == '';
						}).get().length || !_valid_date());
				})
		});
	}

	$.fn.LP_Statistic_Courses = function () {
		if(parseInt($(this).length)===0){
			return;
		}
		return $.each(this, function () {
			var $buttons = $('.chart-buttons button').on('click', function () {
					var $button = $(this),
						type = $button.data('type'),
						from = '', to = '',
						$container = $('#learn-press-chart');
					$buttons.not(this).not('[data-type="course-custom-time"]').prop('disabled', false);
					if ( type == 'course-custom-time' ) {
						from = $('#course-custom-time input[name="from"]').val();
						to = $('#course-custom-time input[name="to"]').val();

						if (from == '' || to == '') {
							return false;
						}
					} else {
						$button.prop('disabled', true)
					}
					$container.addClass('loading')
					$.ajax({
						url     : 'admin-ajax.php',
						data    : {
							action: 'learnpress_load_chart',
							type  : type,
							range : [from, to]
						},
						dataType: 'text',
						success : function (response) {
							response = LP.parseJSON(response);
							$container.LP_Chart_Line(response, LP_Chart_Config);
							$container.removeClass('loading');
						}
					});
					return false;
				}),
				$inputs = $('.chart-buttons #course-custom-time input[type="text"]').change(function () {
					var _valid_date = function () {
						if (new Date($inputs[0].value) < new Date($inputs[1].value)) {
							return true;
						}
					}
					$buttons.filter('[data-type="course-custom-time"]').prop('disabled', $inputs.filter(function () {
							return this.value == '';
						}).get().length || !_valid_date());
				})
		});
	}

	$.fn.LP_Statistic_Orders = function () {
		if(parseInt($(this).length)===0){
			return;
		}
		$('.panel_report_option').hide();
		$('#panel_report_sales_by_'+$('#report_sales_by').val()).show();
		$('#report_sales_by').on('change', function(){
			$('.panel_report_option').hide();
			$('#panel_report_sales_by_'+$(this).val()).show();
			if('date'==$(this).val()){
				LP_Statistic_Orders_Upgrade_Chart();
			}
		});

		/**
		 * Upgrade Chart for Order Statistics
		 * @returns {Boolean}
		 */
		var LP_Statistic_Orders_Upgrade_Chart =function(){
			console.log('abc');
			var type = '', from = '', to = '', report_sales_by = 'date', cat_id = 0, course_id = 0;
			report_sales_by = $('#report_sales_by').val();
			$container = $('#learn-press-chart');
			$container.addClass('loading');
			// get type
			var $buttons = $('.chart-buttons button:disabled').not('[data-type="order-custom-time"]');
			if( parseInt( $buttons.length ) > 0 ) {
				type	= $($buttons[0]).data('type');
			} else {
				type	= 'order-custom-time';
				from	= $('#order-custom-time input[name="from"]').val();
				to		= $('#order-custom-time input[name="to"]').val();
				if (from == '' || to == '') {
					return false;
				}
			}
			if( 'course' === report_sales_by ) {
				course_id = $('#report-by-course-id').val();
			} else if( 'category' === report_sales_by ) {
				cat_id = $('#report-by-course-category-id').val();
			}
	
			$.ajax({
				url     : 'admin-ajax.php',
				data    : {
					action: 'learnpress_load_chart',
					type  : type,
					range : [from, to],
					report_sales_by: report_sales_by,
					course_id: course_id,
					cat_id: cat_id
				},
				dataType: 'text',
				success : function (response) {
					response = LP.parseJSON(response);
					$container.LP_Chart_Line(response, LP_Chart_Config);
					$container.removeClass('loading');
				}
			});
		};
		
		
		$('#report-by-course-id').select2({
				placeholder:'Select a course',
				minimumInputLength: 1,
				ajax: {
					url: ajaxurl+'?action=learnpress_search_course',
					dataType: 'json',
					quietMillis: 250,
					data: function (term, page) {
						return {
							q: term, // search term
						};
					},
					results: function ( data, page ) {
						return { results: data.items };
					},
					cache: true
				}
			});
		
		$('#report-by-course-id').on('change', function(){
			LP_Statistic_Orders_Upgrade_Chart();
		});
		
		$('#report-by-course-category-id').select2({
				placeholder:'Select a course',
				minimumInputLength: 1,
				ajax: {
					url: ajaxurl+'?action=learnpress_search_course_category',
					dataType: 'json',
					quietMillis: 250,
					data: function (term, page) {
						return {
							q: term, // search term
						};
					},
					results: function ( data, page ) {
						return { results: data.items };
					},
					cache: true
				}
			});

		$('#report-by-course-category-id').on('change', function(){
			LP_Statistic_Orders_Upgrade_Chart();
		});

		var $buttons = $('.chart-buttons button').on('click', function () {
				var $button = $(this),
					type = $button.data('type'),
					from = '', to = '',
					$container = $('#learn-press-chart');
				$buttons.not(this).not('[data-type="order-custom-time"]').prop('disabled', false);
				if (type !== 'order-custom-time') {
					$button.prop('disabled', true);
					$('#order-custom-time input[name="from"]').val('');
					$('#order-custom-time input[name="to"]').val('');
				}
				LP_Statistic_Orders_Upgrade_Chart();
				return false;
			});

		var $inputs = $('.chart-buttons #order-custom-time input[type="text"]').change(function () {
				var _valid_date = function () {
					if (new Date($inputs[0].value) < new Date($inputs[1].value)) {
						return true;
					}
				}
				$buttons.filter('[data-type="order-custom-time"]').prop('disabled', $inputs.filter(function () {
						return this.value == '';
					}).get().length || !_valid_date());
			});

	}
	;
	$(document).ready(function () {
		if (typeof $.fn.datepicker != 'undefined') {
			$(".date-picker").datepicker({
				dateFormat: 'yy/mm/dd'
			});
		}
		$('.learn-press-statistic-users').LP_Statistic_Users();
		$('.learn-press-statistic-courses').LP_Statistic_Courses();
		$('.learn-press-statistic-orders').LP_Statistic_Orders();
		
	});
	return;

	var student_chart;
	window.drawStudentsChart = drawStudentsChart = function (data, config) {
		var $student_chart = $("#lpr-chart-students").clone().attr('style', '').removeAttr("width").removeAttr('height');
		$("#lpr-chart-students").replaceWith($student_chart);
		$student_chart = $student_chart[0].getContext("2d");
		student_chart = new Chart($student_chart).Line(data, config);
	}
	if (typeof last_seven_days == 'undefined') return;
	drawStudentsChart(last_seven_days, config);


	var courses_chart;
	window.drawCoursesChart = drawCoursesChart = function (data, config) {
		var $courses_chart = $("#lpr-chart-courses").clone().attr('style', '').removeAttr("width").removeAttr('height');
		$("#lpr-chart-courses").replaceWith($courses_chart);
		$courses_chart = $courses_chart[0].getContext("2d");
		courses_chart = new Chart($courses_chart).Bar(data, config);
	}
	if (typeof data == 'undefined') return;

	drawCoursesChart(data, config);

})(jQuery);

