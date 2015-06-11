jQuery(document).ready(function ($) {
	if( typeof $.fn.datepicker == 'undefined' ) return;
	$( ".lpr-time" ).datepicker();
});
jQuery(document).ready(function ($) {

	var student_chart;
	window.drawStudentsChart = drawStudentsChart = function( data,config ) {
		var $student_chart = $("#lpr-chart-students").clone().attr('style', '').removeAttr("width").removeAttr('height');
		$("#lpr-chart-students").replaceWith($student_chart);
		$student_chart = $student_chart[0].getContext("2d");
		student_chart = new Chart($student_chart).Line(data, config);
	}
	if (typeof last_seven_days == 'undefined') return;
	drawStudentsChart( last_seven_days, config );

});
jQuery(document).ready(function ($) {

	var courses_chart;
	window.drawCoursesChart = drawCoursesChart = function( data,config ) {
		var $courses_chart= $("#lpr-chart-courses").clone().attr('style', '').removeAttr("width").removeAttr('height');
		$("#lpr-chart-courses").replaceWith($courses_chart);
		$courses_chart = $courses_chart[0].getContext("2d");
		courses_chart = new Chart($courses_chart).Bar(data, config);
	}
	if (typeof data == 'undefined') return;

	drawCoursesChart( data, config );

});

