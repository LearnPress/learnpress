<?php
/**
 * Template for displaying the quizzes in profile
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

$user = $user->get_user();
global $post;
$args = array();
$quizzes = learn_press_get_quizzes( $user->get_id(), $args );
?>

<?php if ( $quizzes ) : ?>
	<ul>
		<?php foreach ( $quizzes as $post ) {
			setup_postdata( $post ); ?>

			<?php learn_press_get_template( 'profile/quiz-content.php', array( 'user' => $user ) ); ?>

		<?php } ?>
	</ul>
<?php else : ?>

	<?php learn_press_display_message( __( 'You haven\'t started any quiz!', 'learnpress' ) );?>

<?php endif; ?>

<?php wp_reset_postdata();?>
