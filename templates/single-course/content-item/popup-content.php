<?php
/**
 * Content Poup.
 * Use for React Quiz.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

?>

<div id="popup-content">
	<?php
	/**
	* @since 4.0.6
	* @see single-button-toggle-sidebar - 5
	*/
	do_action( 'learn-press/single-button-toggle-sidebar' ); 

	LearnPress::instance()->template( 'course' )->course_content_item();

	LearnPress::instance()->template( 'course' )->course_item_comments();
	?>
</div>
