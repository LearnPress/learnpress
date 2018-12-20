<?php
/**
 * Template for displaying loop course of section.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/loop-section.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
/**
 * @var LP_Course_Section $section
 */
if ( ! isset( $section ) ) {
	return;
}

?>

<li<?php $section->main_class(); ?> id="section-<?php echo $section->get_slug(); ?>"
                                    data-id="<?php echo $section->get_slug(); ?>"
                                    data-section-id="<?php echo $section->get_id(); ?>">

	<?php
	/**
	 * @deprecated
	 */
	do_action( 'learn_press_curriculum_section_summary', $section );

	/**
	 * @since  3.0.0
	 *
	 * @see    learn_press_curriculum_section_title - 5
	 * @see    learn_press_curriculum_section_content - 10
	 */
	do_action( 'learn-press/section-summary', $section );
	?>

</li>