<?php
/**
 * Template for display content of lesson
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or die;
$course = LP_Global::course();
$item   = LP_Global::course_item();
//learn_press_get_template('content-lesson/title.php');
//learn_press_get_template('content-lesson/description.php');
?>
    <div class="content-item-summary">

		<?php

		/**
		 *
		 */
		do_action( 'learn-press/before-content-item-summary/' . $item->get_item_type() );

		do_action( 'learn-press/content-item-summary/' . $item->get_item_type() );

		do_action( 'learn-press/after-content-item-summary/' . $item->get_item_type() );

		/*return;
		?>

		<div id="content-item-nav">
			<div class="content-item-nav-wrap">
				<form>
					<a href="<?php echo $course->get_next_item();?>">Prev</a>
					<button>Next</button>
				</form>
			</div>
		</div>
	*/ ?>
    </div>

<lp_lesson_component></lp_lesson_component>
