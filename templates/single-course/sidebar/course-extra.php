<?php
if ( ! isset( $type ) || ! isset( $content ) ) {
	return;
}

?>
<div class="course-extra-<?php echo $type; ?>">
	<?php if ( isset( $title ) ) { ?>
        <h4 class="course-extra-<?php echo $type; ?>__title"><?php echo esc_html( $title ); ?></h4>
	<?php } ?>

    <div class="course-extra-<?php echo $type; ?>__content">
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