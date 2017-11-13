<?php
/**
 * Admin View: Question assigned Meta box
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php global $post; ?>

<?php $quizzes = learn_press_get_question_quizzes( $post->ID ); ?>

<?php if ( $quizzes ) { ?>

	<?php foreach ( $quizzes as $quiz ) { ?>
        <div>
            <a href="<?php echo get_edit_post_link( $quiz->ID ); ?> "
               target="_blank"><?php echo get_the_title( $quiz->ID ); ?></a>
        </div>
	<?php } ?>

<?php } else { ?>

	<?php _e( 'Not assigned yet', 'learnpress' ); ?>

<?php } ?>