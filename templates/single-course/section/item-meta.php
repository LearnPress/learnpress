<?php
global $course;
?>
<div class="course-item-meta">

<?php if( $item->post_type == 'lp_quiz' ){ ?>

<span class="lp-label lp-label-quiz"><?php _e( 'Quiz', 'learn_press' );?></span>

	<?php if( $course->final_quiz == $item->ID ){?>

		<span class="lp-label lp-label-final"><?php _e( 'Final', 'learn_press' );?></span>

	<?php }?>

<?php }elseif( $item->post_type == 'lp_lesson' ){ ?>

	<span class="lp-label lp-label-lesson"><?php _e( 'Lesson', 'learn_press' );?></span>

	<?php if( get_post_meta( $item->ID, '_lp_is_previewable' ) ){?>

		<span class="lp-label lp-label-preview"><?php _e( 'Preview', 'learn_press' );?></span>

	<?php }?>

<?php } ?>

</div>
