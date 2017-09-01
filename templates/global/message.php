<?php
/**
 * @author        ThimPress
 * @package       LearnPress/Templates
 * @version       1.0
 */

defined( 'ABSPATH' ) || exit();
if ( ! $messages ) {
	return;
}
?>
<?php foreach ( $messages as $type => $message ) { ?>
	<?php if ( $message ): foreach ( $message as $content ) { ?>
		<?php
		$options = array();
		if ( is_array( $content ) ) {
			$options = $content['options'];
			$content = $content['content'];
			$options = wp_parse_args(
				$options,
				array(
					'position'  => '',
					'delay-in'  => 0,
					'delay-out' => 0
				)
			);
		}
		$classes = array( 'learn-press-message', esc_attr( $type ) );
		$data    = array();
		if ( ! empty( $options['position'] ) ) {
			$classes[] = $options['position'];
			if ( ! empty( $options['delay-in'] ) ) {
				$data[] = sprintf( 'data-delay-in="%s"', $options['delay-in'] );
			}

			if ( ! empty( $options['delay-in'] ) ) {
				$data[] = sprintf( 'data-delay-out="%s"', $options['delay-out'] );
			}
		}
		?>
        <div class="<?php echo join( ' ', $classes ); ?>" <?php echo $data ? join( ' ', $data ) : ''; ?>>
			<?php
			//			if ( !preg_match( '!<p>!', $content ) && !preg_match( '!<div>!', $content ) ) {
			//				$content = sprintf( '<p>%s</p>', $content );
			//			}
			?>
			<?php echo $content; ?>
        </div>
	<?php } ?>
	<?php endif; ?>
<?php } ?>
