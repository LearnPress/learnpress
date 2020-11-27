<?php
/**
 * Template for displaying extra info as toggle
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $checked ) ) {
	$checked = false;
}
?>

<input type="radio" name="course-extra-box-ratio" id="course-extra-box-ratio-<?php echo sanitize_key( $title ); ?>" <?php checked( $checked ); ?>/>

<div class="course-extra-box">
	<label class="course-extra-box__title" for="course-extra-box-ratio-<?php echo sanitize_key( $title ); ?>">
		<?php echo esc_html( $title ); ?>
	</label>

	<div class="course-extra-box__content">
		<div class="course-extra-box__content-inner">
			<ul>
				<?php foreach ( $items as $item ) : ?>
					<li><?php echo $item; ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</div>
