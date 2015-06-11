<div>
	<?php
	do_action( 'learn_press_before_own_course_title' );
	the_title( sprintf( '<h2 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' );
	do_action( 'learn_press_after_own_course_title' );

	do_action( 'learn_press_before_own_course_price' );
	printf(
		'<p class="course-price">%s: %d</p>',
		__( 'Price', 'learn_press' ),
		learn_press_get_course_price( null, true )
	);
	do_action( 'learn_press_after_own_course_price' );

	do_action( 'learn_press_before_student_enrolled' );
	printf(
		'<p class="student-enrolled">%s: %d</p>',
		__( 'Students enrolled', 'learn_press' ),
		learn_press_count_students_enrolled( get_the_ID() )
	);
	do_action( 'learn_press_after_student_enrolled' );

	do_action( 'learn_press_before_student_passed' );
	printf(
		'<p class="student-passed">%s: %d</p>',
		__( 'Students passed', 'learn_press' ),
		learn_press_count_students_passed( get_the_ID() )
	);
	do_action( 'learn_press_after_student_passed' );
	?>
</div>