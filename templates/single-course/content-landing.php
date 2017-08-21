<?php
/**
 * Template for displaying content of landing course.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<?php
/**
 * @deprecated
 */
do_action( 'learn_press_before_content_landing' );
?>

<div class="course-landing-summary">

	<?php
	/**
	 * @deprecated
	 */
	do_action( 'learn_press_content_landing_summary' );

	/**
	 * @since 3.x.x
	 */
	do_action( 'learn-press/content-landing-summary' );
	?>

</div>

<?php
/**
 * @deprecated
 */
do_action( 'learn_press_after_content_landing' );
?>
