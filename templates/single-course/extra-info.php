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

<div class="course-extra-box">
	<h3 class="course-extra-box__title">
		<?php echo esc_html( $title ); ?>
	</h3>

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
