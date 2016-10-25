<?php
/**
 * @author        ThimPress
 * @package       LearnPress/Templates
 * @version       1.0
 */

defined( 'ABSPATH' ) || exit();
if ( !$messages ) {
	return;
}
?>
<?php foreach ( $messages as $type => $message ) { ?>
	<?php if ( $message ): foreach ( $message as $content ) { ?>
		<?php
		if ( is_array( $content ) ) {
			$content   = $content['content'];
			$autoclose = !empty( $content['autoclose'] ) ? $content['autoclose'] : false;
			if ( $autoclose === true ) {
				$autoclose = 3000;
			}
		} else {
			$autoclose = false;
		}
		?>
		<div class="learn-press-message <?php echo esc_attr( $type ); ?>" <?php echo $autoclose ? 'data-autoclose="' . esc_attr( $autoclose ) . '"' : ''; ?>>
			<?php
			if ( !preg_match( '!<p>!', $content ) && !preg_match( '!<div>!', $content ) ) {
				$content = sprintf( '<p>%s</p>', $content );
			}
			?>
			<?php echo $content; ?>
		</div>
	<?php } ?>
	<?php endif; ?>
<?php } ?>
