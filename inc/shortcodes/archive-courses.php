<?php
/**
 * Shortcodes to display archive courses
 */

defined( 'ABSPATH' ) || exit();

/**
 * Shortcode to display all newest courses
 * @param  [type] $atts [description]
 * @return [type]       [description]
 */
function newest_courses_shortcode( $atts ) {
	$a = shortcode_atts( array(
			'number' => '1000000000'
		), $atts
	);
	global $wpdb;
	$courses   = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT DISTINCT p.ID FROM $wpdb->posts AS p
			WHERE p.post_type = %s
			AND p.post_status = %s
			LIMIT %d",
		LP()->course_post_type,
		'publish',
		(int)$a['number'])
	);
		foreach( $courses as $course ) :
	?>
	<a href="<?php echo get_the_permalink( $course->ID ) ?>">
		<div class="archive-course">
			<div class="course-cover"><?php echo get_the_post_thumbnail( $course->ID ) ?></div>
			<div class="course-detail">
				<div class="course-title">
					<?php echo get_the_title( $course->ID ) ?>
				</div>
				<div class="course-student-number"><?php echo learn_press_count_students_enrolled( $course->ID ) . __( ' students', 'learnpress' ) ?></div>
				<div class="course-lesson-number"><?php echo lpr_get_number_lesson( $course->ID ) . __( ' lessons', 'learnpress' ) ?></div>
			</div>
		</div>
	</a>
		<div class="clearfix"></div>
	<?php
		endforeach;
}
add_shortcode( 'newest_course', 'newest_courses_shortcode' );

/**
 * Shortcode to display all free courses
 * @param  [type] $atts [description]
 * @return [type]       [description]
 */
function free_course_shortcode( $atts ) {
	$a = shortcode_atts( array(
			'number' => '1000000000'
		), $atts
	);
	global $wpdb;
	$courses   = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT p.ID, pm.meta_value FROM $wpdb->posts AS p
			INNER JOIN $wpdb->postmeta AS pm ON p.ID = pm.post_id
			WHERE p.post_type = %s
			AND p.post_status = %s
			AND pm.meta_key = %s
			AND pm.meta_value = %s
			LIMIT %d",
		LP()->course_post_type,
		'publish',
		'_lpr_course_payment',
		'free',
		(int)$a['number'])
	);
		foreach( $courses as $course ) :
	?>
	<a href="<?php echo get_the_permalink( $course->ID ) ?>">
		<div class="archive-course">
			<div class="course-cover"><?php echo get_the_post_thumbnail( $course->ID ) ?></div>
			<div class="course-detail">
				<div class="course-title">
					<?php echo get_the_title( $course->ID ) ?>
				</div>
				<div class="course-student-number"><?php echo learn_press_count_students_enrolled( $course->ID ) . __( ' students', 'learnpress' ) ?></div>
				<div class="course-lesson-number"><?php echo lpr_get_number_lesson( $course->ID ) . __( ' lessons', 'learnpress' ) ?></div>
			</div>
		</div>
	</a>
		<div class="clearfix"></div>
	<?php
		endforeach;
}
add_shortcode( 'free_course', 'free_course_shortcode' );

/**
 * Shortcode to display paid course
 * @param  [type] $atts [description]
 * @return [type]       [description]
 */
function paid_courses_shortcode( $atts ) {
	$a = shortcode_atts( array(
			'number' => '1000000000'
		), $atts
	);
	global $wpdb;
	$courses   = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT p.ID, pm.meta_value FROM $wpdb->posts AS p
			INNER JOIN $wpdb->postmeta AS pm ON p.ID = pm.post_id
			WHERE p.post_type = %s
			AND p.post_status = %s
			AND pm.meta_key = %s
			AND pm.meta_value = %s
			LIMIT %d",
		LP()->course_post_type,
		'publish',
		'_lpr_course_payment',
		'not_free',
		(int)$a['number'])
	);
		foreach( $courses as $course ) :
	?>
	<a href="<?php echo get_the_permalink( $course->ID ) ?>">
		<div class="archive-course">
			<div class="course-cover"><?php echo get_the_post_thumbnail( $course->ID ) ?></div>
			<div class="course-detail">
				<div class="course-title">
					<?php echo get_the_title( $course->ID ) ?>
				</div>
				<div class="course-student-number"><?php echo learn_press_count_students_enrolled( $course->ID ) . __( ' students', 'learnpress' ) ?></div>
				<div class="course-lesson-number"><?php echo lpr_get_number_lesson( $course->ID ) . __( ' lessons', 'learnpress' ) ?></div>
			</div>
		</div>
	</a>
		<div class="clearfix"></div>
	<?php
		endforeach;
}
add_shortcode( 'paid_course', 'paid_courses_shortcode' );
