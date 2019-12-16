<?php
/**
 * Template for displaying course duration in secondary section.
 *
 * @version 4.0.0
 * @author  ThimPress
 * @package LearnPress/Templates
 */

defined( 'ABSPATH' ) or die;

$course = LP_Global::course();

?>

<div class="meta-item meta-item-duration"><?php echo learn_press_get_post_translated_duration( get_the_ID(), __( 'Lifetime access', 'learnpress' ) ); ?></div>

