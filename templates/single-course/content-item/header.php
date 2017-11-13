<?php
/**
 * Template for displaying the header of course's popup
 *
 * @author ThimPress
 * @package LearnPress/Templates
 * @version 3.0
 */

defined('ABSPATH') or die();
$course = LP_Global::course();
?>

<div id="course-item-content-header">
    <div class="course-item-search">
        <form>
            <input type="text" placeholder="xx" />
            <button></button>
        </form>
    </div>
    <h2 class="course-title">
        <?php echo $course->get_title();?>
    </h2>
</div>
