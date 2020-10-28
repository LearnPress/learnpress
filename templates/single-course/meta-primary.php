<?php
/**
 * Template for displaying primary course meta data such as: Instructor, Categories, Reviews (addons)...
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) or die;

$has_meta_left  = LP()->template()->has_content( 'learn-press/course-meta-primary-left' );
$has_meta_right = LP()->template()->has_content( 'learn-press/course-meta-primary-right' );

// Do not echo anything if there is no content hooked
if ( ! $has_meta_left && ! $has_meta_right ) {
	return;
}
?>

<div class="course-meta course-meta-primary<?php echo $has_meta_right && $has_meta_left ? ' two-columns' : ''; ?>">

	<?php if ( $has_meta_left ) { ?>

		<div class="course-meta__pull-left">

			<?php
			/**
			 * LP Hook
			 */

			do_action( 'learn-press/course-meta-primary-left' );
			?>

		</div>

	<?php } ?>

	<?php if ( $has_meta_right ) { ?>

		<div class="course-meta__pull-right">

			<?php
			/**
			 * LP Hook
			 */

			do_action( 'learn-press/course-meta-primary-right' );
			?>

		</div>

	<?php } ?>

</div>
