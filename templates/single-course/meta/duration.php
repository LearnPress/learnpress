<?php
/**
 * Template for displaying course duration in secondary section.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.1
 */

use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

defined( 'ABSPATH' ) or die;

$course = learn_press_get_course();
$duration_str = SingleCourseTemplate::instance()->html_duration( $course );
?>

<div class="meta-item meta-item-duration"><?php echo $duration_str; ?></div>

