<?php
/**
 * Template for displaying extra info as toggle
 *
 * @version 4.0.0
 * @author  ThimPress
 * @package LearnPress/Templates
 */

defined( 'ABSPATH' ) or die;

/**
 * @var string $title
 * @var array  $items
 * @var bool   $checked
 */

if ( !isset( $checked ) ) {
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
			<?php if ( sanitize_key( $title ) == 'targetaudiences' ): ?>
				<ul>
					<?php foreach ( $items as $item ) { ?>
						<li><?php echo $item; ?></li>
					<?php } ?>
				</ul>
			<?php else: ?>
				<ul>
					<?php foreach ( $items as $item ) { ?>
						<li><?php echo $item; ?></li>
					<?php } ?>
				</ul>
			<?php endif; ?>

		</div>
	</div>
</div>
