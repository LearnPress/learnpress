<?php
/**
 * Template for displaying course level in secondary section.
 *
 * @version 4.0.0
 * @author  ThimPress
 * @package LearnPress/Templates
 */

defined( 'ABSPATH' ) or die;

$course = LP_Global::course();

?>

<div class="meta-item meta-item-level"><?php echo learn_press_get_post_level( get_the_ID() ); ?></div>

