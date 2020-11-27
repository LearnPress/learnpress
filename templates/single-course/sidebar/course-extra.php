<?php
/**
 * Use for Course Extra widget.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

if ( ! isset( $type ) || ! isset( $content ) ) {
	return;
}

?>
<div class="course-extras style-checks <?php echo $type; ?>">
	<?php if ( isset( $title ) ) { ?>
		<h4 class="course-extras__title"><?php echo esc_html( $title ); ?></h4>
	<?php } ?>

	<div class="course-extras__content">
		<?php if ( is_array( $content ) ) { ?>
			<ul>
				<?php foreach ( $content as $line ) { ?>
					<li><?php echo $line; ?></li>
				<?php } ?>
			</ul>
		<?php } else { ?>
			<?php echo $content; ?>
		<?php } ?>
	</div>
</div>
