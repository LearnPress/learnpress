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
		return $.each(this, function () {
			var $buttons = $('.chart-buttons button').on('click', function () {
					var $button = $(this),
						type = $button.data('type'),
						from = '', to = '',
						$container = $('#learn-press-chart');
					$buttons.not(this).not('[data-type="course-custom-time"]').prop('disabled', false);
					if (type == 'course-custom-time') {
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
		return $.each(this, function () {
			var $buttons = $('.chart-buttons button').on('click', function () {
					var $button = $(this),
						type = $button.data('type'),
						from = '', to = '',
						$container = $('#learn-press-chart');
					$buttons.not(this).not('[data-type="order-custom-time"]').prop('disabled', false);
					if (type == 'order-custom-time') {
						from = $('#order-custom-time input[name="from"]').val();
						to = $('#order-custom-time input[name="to"]').val();

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
				$inputs = $('.chart-buttons #order-custom-time input[type="text"]').change(function () {
					var _valid_date = function () {
						if (new Date($inputs[0].value) < new Date($inputs[1].value)) {
							return true;
						}
					}
					$buttons.filter('[data-type="order-custom-time"]').prop('disabled', $inputs.filter(function () {
							return this.value == '';
						}).get().length || !_valid_date());
				})
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
	})
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

