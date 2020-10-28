<?php
/**
 * Template for displaying categories of a course in loop.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

$categories = get_the_term_list( '', 'course_category' );
?>

<?php if ( ! empty( $categories ) ) : ?>
	<div class="course-categories">
		<?php echo $categories; ?>
	</div>
<?php endif; ?>
