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
<li class="section">

	<?php do_action( 'learn_press_curriculum_section_summary', $section ); ?>

</li>