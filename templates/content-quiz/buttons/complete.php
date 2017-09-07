<?php
/**
 * Template for displaying Complete button.
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

<form name="complete-quiz" class="complete-quiz form-button" method="post" enctype="multipart/form-data">
    <button type="submit"><?php _e( 'Complete', 'learnpress' ); ?></button>
</form>