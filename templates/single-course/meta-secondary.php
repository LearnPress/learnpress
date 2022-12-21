<?php
/**
 * Template for displaying secondary course meta data such as: duration, level, lessons, quizzes, students, etc...
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) or die;

$has_meta_left  = LearnPress::instance()->template()->has_content( 'learn-press/course-meta-secondary-left' );
$has_meta_right = LearnPress::instance()->template()->has_content( 'learn-press/course-meta-secondary-right' );

// Do not echo anything if there is no content hooked
if ( ! $has_meta_left && ! $has_meta_right ) {
	return;
}
?>

<div class="course-meta course-meta-secondary<?php echo esc_attr( $has_meta_right && $has_meta_left ? ' two-columns' : '' ); ?>">

	<?php if ( $has_meta_left ) { ?>

		<div class="course-meta__pull-left">

			<?php
			/**
			 * LP Hook
			 */

			do_action( 'learn-press/course-meta-secondary-left' );
			?>

		</div>

	<?php } ?>

	<?php if ( $has_meta_right ) { ?>

		<div class="course-meta__pull-right">

			<?php
			/**
			 * LP Hook
			 */

			do_action( 'learn-press/course-meta-secondary-right' );
			?>

		</div>

	<?php } ?>

</div>
