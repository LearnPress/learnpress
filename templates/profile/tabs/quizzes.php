<?php
/**
 * Template for displaying user profile quizzes tab.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php
global $post;

$user = $user->get_user();
$args    = array();
$quizzes = learn_press_get_quizzes( $user->get_id(), $args );
?>

<?php if ( $quizzes ) : ?>
    <ul>
		<?php foreach ( $quizzes as $post ) {
			setup_postdata( $post ); ?>

			<?php learn_press_get_template( 'profile/tabs/quizzes/quiz-content.php', array( 'user' => $user ) ); ?>

		<?php } ?>
    </ul>
<?php else : ?>

	<?php learn_press_display_message( __( 'You haven\'t started any quiz!', 'learnpress' ) ); ?>

<?php endif; ?>

<?php wp_reset_postdata(); ?>
