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

<?php
// question curd
$curd = new LP_Question_CURD();
// get quiz
$quiz = $curd->get_quiz( $post->ID );
?>

<?php if ( $quiz ) { ?>
    <div>
        <a href="<?php echo get_edit_post_link( $quiz->ID ); ?> "
           target="_blank"><?php echo get_the_title( $quiz->ID ); ?></a>
    </div>
<?php } else { ?>
	<?php _e( 'Not assigned yet', 'learnpress' ); ?>
<?php } ?>