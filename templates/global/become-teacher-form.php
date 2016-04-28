<?php
/**
 * Template for displaying the form let user fill out their information to become a teacher
 *
 * @author        ThimPress
 * @package       LearnPress/Templates
 * @version       1.0
 */

$method  = $atts['method'];
$request = $method == 'post' ? $_POST : $_REQUEST;
?>
<div class="become-teacher-form">
	<form id="<?php echo $form_id; ?>" name="become-teacher-form" method="<?php echo $method; ?>" enctype="multipart/form-data" action="<?php echo $atts['action']; ?>">
		<?php if ( $fields ): ?>
			<ul>
				<?php foreach ( $fields as $name => $option ): ?>
					<?php
					$option        = wp_parse_args(
						$option,
						array(
							'title'       => '',
							'type'        => '',
							'def'         => '',
							'placeholder' => ''
						)
					);
					$value         = !empty( $request[$name] ) ? $request[$name] : ( !empty( $option['def'] ) ? $option['def'] : '' );
					$requested     = strtolower( $_SERVER['REQUEST_METHOD'] ) == $method;
					$error_message = null;
					if ( $requested ) {
						$error_message = apply_filters( 'learn_press_become_teacher_form_validate_' . $name, $value );
					}

					?>
					<li>
						<label><?php echo $option['title']; ?></label>
						<?php
						switch ( $option['type'] ) {
							case 'text':
							case 'email':
								printf( '<input type="%s" name="%s" placeholder="%s" value="%s" />', $option['type'], $name, $option['placeholder'], esc_attr( $value ) );
								break;
						}
						if ( $error_message ) {
							printf( '<p class="error">%s</p>', $error );
						}
						?>
					</li>
				<?php endforeach; ?>
			</ul>
			<input type="hidden" name="lp-ajax" value="become-a-teacher" />
			<button type="submit" data-text="<?php echo esc_attr( $atts['submit_button_text'] ); ?>" data-text-process="<?php esc_attr_e( 'Processing', 'learnpress' ); ?>"><?php echo esc_html( $atts['submit_button_text'] ); ?></button>
		<?php endif; ?>
	</form>
</div>