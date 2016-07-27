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
?>
<li class="section" id="section-<?php echo $section->section_id; ?>" data-id="<?php echo $section->section_id; ?>">

	<?php do_action( 'learn_press_curriculum_section_summary', $section ); ?>

</li>