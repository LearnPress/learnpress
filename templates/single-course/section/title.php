<?php
/**
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$section_name = apply_filters( 'learn_press_curriculum_section_name', $section->name, $section );

if( $section_name === false ){
	return;
}
?>
<h4 class="section-header"><?php echo $section_name;?></h4>