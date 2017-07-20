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

<li class="section" id="section-<?php echo $section->get_id(); ?>" data-id="<?php echo $section->get_id(); ?>">

	<?php

	/**
	 * @deprecated
	 */
	do_action( 'learn_press_curriculum_section_summary', $section );

	/**
	 * @since  3.x.x
	 *
	 * @hooked learn_press_curriculum_section_title - 5
	 * @hooked learn_press_curriculum_section_content - 10
	 */
	do_action( 'learn-press/section-summary', $section );
	?>

</li>