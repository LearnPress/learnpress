<?php
/**
 * Admin View: Question assigned Meta box
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

global $post;
$curd = new LP_Question_CURD();
$quiz = $curd->get_quiz( $post->ID );
?>

<div class="lp-item-assigned">
	<?php if ( $quiz ) : ?>
		<?php $courses = learn_press_get_item_courses( $quiz->ID ); ?>

		<?php if ( $courses ) : ?>
			<ul class="parent-courses">
				<?php foreach ( $courses as $course ) : ?>
					<li>
						<strong>
							<a href="<?php echo get_edit_post_link( $course->ID ); ?>" target="_blank"><?php echo get_the_title( $course->ID ); ?></a>
						</strong>
						&#8212;
						<a href="<?php echo learn_press_get_course_permalink( $course->ID ); ?>" target="_blank"><?php esc_html_e( 'View', 'learnpress' ); ?></a>
						<ul class="parent-quizzes">
							<li>
								<strong>
									<a href="<?php echo get_edit_post_link( $quiz->ID ); ?>" target="_blank">
										&#8212; &#8212;
										<?php echo get_the_title( $quiz->ID ); ?></a>
								</strong>
								&#8212;
								<a href="<?php echo learn_press_get_course_item_permalink( $course->ID, $quiz->ID ); ?>" target="_blank"><?php esc_html_e( 'View', 'learnpress' ); ?></a>
							</li>
						</ul>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<strong><a href="<?php echo get_edit_post_link( $quiz->ID ); ?>" target="_blank"><?php echo get_the_title( $quiz->ID ); ?></a></strong>
		<?php endif; ?>
		<?php
	else :
		esc_html_e( 'Not assigned yet', 'learnpress' );
	endif;
	?>
</div>
