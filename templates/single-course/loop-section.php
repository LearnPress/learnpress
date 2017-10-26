<?php
/**
 * Template for displaying loop of section
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! isset( $section ) ) {
	return;
}
?>

<li class="section" id="section-<?php echo $section->get_slug(); ?>" data-id="<?php echo $section->get_slug(); ?>" data-section-id="<?php echo $section->get_id();?>">

	<?php

	/**
	 * @deprecated
	 */
	do_action( 'learn_press_curriculum_section_summary', $section );

	/**
	 * @since  3.x.x
	 *
	 * @see learn_press_curriculum_section_title - 5
	 * @see learn_press_curriculum_section_content - 10
	 */
	do_action( 'learn-press/section-summary', $section );
	?>

</li>