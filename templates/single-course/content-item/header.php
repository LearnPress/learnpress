<?php
/**
 * Template for displaying header of single course popup.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/header.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php $course = LP_Global::course(); ?>

<div id="course-item-content-header">

    <div class="course-item-search">
        <form>
            <input type="text" placeholder="<?php esc_attr_e('Search item', 'learnpress');?>"/>
            <button type="button"></button>
        </form>
    </div>

    <h2 class="course-title"><?php echo $course->get_title(); ?></h2>

</div>
