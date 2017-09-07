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
?>
<form name="continue-quiz" class="continue-quiz form-button" method="post"
      action="<?php echo $user->get_current_question( $user->get_current_item( get_the_ID() ), get_the_ID(), true ); ?>">
    <button type="submit"><?php _e( 'Continue', 'learnpress' ); ?></button>
</form>
