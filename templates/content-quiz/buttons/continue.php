<?php
/**
 * Template for displaying Continue button.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

defined( 'ABSPATH' ) or die();

$user = LP_Global::user();
$quiz = LP_Global::course_item_quiz();
$data = $user->get_course_data( get_the_ID() );
$item = $data->get_viewing_item();
?>
<form name="continue-quiz" class="continue-quiz form-button" method="post" action="<?php echo $quiz->get_question_link( $item->get_current_question() ); ?>">
    <button type="submit"><?php _e( 'Continue', 'learnpress' ); ?></button>
</form>
