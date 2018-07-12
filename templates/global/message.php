<?php
/**
 * Template for displaying global message.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/global/message.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! isset( $messages ) ) {
	return;
}

foreach ( $messages as $type => $message ) {

	if ( ! $message ) {
		continue;
	}

	foreach ( $message as $content ) {

		$options = array();

		if ( is_array( $content ) ) {
			$options = $content['options'];
			$content = $content['content'];
			$options = wp_parse_args(
				$options,
				array(
					'position'  => '',
					'icon'      => '',
					'delay-in'  => 0,
					'delay-out' => 0
				)
			);
		}

		$classes = array( 'learn-press-message', esc_attr( $type ), $options['icon'] ? 'has-icon' : '' );
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
			<?php if ( in_array( 'has-icon', $classes ) ) {
				echo '<i class="icon"></i>';
			}
			echo $content; ?>
        </div>
		<?php
	}
}
