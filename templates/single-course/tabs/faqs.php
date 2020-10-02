<?php
/**
 * Template for displaying FAQs tab of single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/tabs/faqs.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

defined( 'ABSPATH' ) || exit();

$unique_key = uniqid();
?>

<input type="checkbox" name="course-faqs-box-ratio" id="course-faqs-box-ratio-<?php echo sanitize_key( $unique_key ); ?>"/>

<?php if ( $question && $answer ) : ?>
	<div class="course-faqs-box">
		<label class="course-faqs-box__title" for="course-faqs-box-ratio-<?php echo sanitize_key( $unique_key ); ?>">
			<?php echo esc_html( $question ); ?>
		</label>

		<div class="course-faqs-box__content">
			<div class="course-faqs-box__content-inner">
				<?php echo $answer; ?>
			</div>
		</div>
	</div>
<?php endif ?>
