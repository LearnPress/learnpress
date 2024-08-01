<?php
wp_enqueue_script( 'learn-press-email-content-field', LearnPress::instance()->plugin_url( 'inc/admin/meta-box/assets/email-content.js' ) );
wp_enqueue_style( 'learn-press-email-content-field', LearnPress::instance()->plugin_url( 'inc/admin/meta-box/assets/email-content.css' ) );

if ( ! isset( $value ) ) {
	return;
}

$meta = wp_parse_args(
	$value['value'],
	array(
		'format' => 'html',
		'html'   => '',
		'plain'  => '',
	)
);

$field = wp_parse_args(
	$value,
	array(
		'template_base'        => '',
		'template_path'        => '',
		'template_html'        => '',
		'template_plain'       => '',
		'template_html_local'  => '',
		'template_plain_local' => '',
		'support_variables'    => array(),
	)
);

$email_format = $meta['format'];
?>

<tr valign="top">
	<th scope="row" class="titledesc">
		<label><?php echo wp_kses_post( $value['title'] ); ?> <?php echo wp_kses_post( $tooltip_html ?? '' ); ?></label>
	</th>
	<td class="forminp lp-metabox-field__email-content forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">&lrm;
		<?php
		/*learn_press_email_formats_dropdown(
			array(
				'name'        => $value['id'] . '[format]',
				'class'       => 'lp-email-format',
				'selected'    => $email_format,
				'option_none' => array( '' => esc_html__( 'General Settings', 'learnpress' ) ),
			)
		);*/
		?>
		<div class="lp-email-templates">
			<?php
			$templates = learn_press_email_formats();

			foreach ( $templates as $template_type => $title ) :
				$template = ! empty( $field[ "template_{$template_type}" ] ) ? $field[ "template_{$template_type}" ] : null;

				if ( empty( $template ) ) {
					continue;
				}

				$local_file    = ! empty( $field[ "template_{$template_type}_local" ] ) ? $field[ "template_{$template_type}_local" ] : null;
				$template_file = $field['template_base'] . $template;
				$template_dir  = $field['template_path'];
				$classes       = array( 'learn-press-email-template' );

				if ( $template_type == 'html' ) {
					$classes[] = $template_type . ' multipart';
				} else {
					$classes[] = 'plain_text';
				}

				if ( $template_type == $email_format || ( ( $email_format == 'plain_text' || ! $email_format ) && $template_type == 'plain' ) ) {
				} else {
					$classes[] = 'hide-if-js';
				}

				$content_html  = $meta['html'] ? stripslashes( $meta['html'] ) : LP_WP_Filesystem::instance()->file_get_contents( $template_file );
				$content_plain = $meta['plain'] ? stripslashes( $meta['plain'] ) : LP_WP_Filesystem::instance()->file_get_contents( $template_file );

				$has_local_file = file_exists( $local_file );

				$theme_dir      = get_template_directory();
				$stylesheet_dir = get_stylesheet_directory();

				if ( $theme_dir != $stylesheet_dir ) {
					$theme_dir = $stylesheet_dir;
				}

				$theme_folder = basename( $theme_dir );
				?>

				<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
					<?php if ( $has_local_file ) : ?>
						<textarea rows="10" style="width: 90%;" readonly="readonly"><?php echo stripslashes( LP_WP_Filesystem::instance()->file_get_contents( $local_file ) ); ?></textarea>
						<p class="description">
							<?php printf( __( 'This template has been overridden by your theme and can be found in: <code>%s</code>. <br />Please open the file in an editor program to edit', 'learnpress' ), $theme_folder . '/' . $template_dir . '/' . $template ); ?>
						</p>
					<?php endif; ?>

					<div class="<?php echo esc_attr( $has_local_file ? 'hide-if-js' : '' ); ?>">
						<?php if ( $template_type == 'html' ) : ?>
							<?php
							wp_editor(
								$content_html,
								sanitize_key( $field['id'] . '-' . $template_type ),
								array(
									'textarea_rows' => 20,
									'wpautop'       => false,
									'textarea_name' => $field['id'] . '[html]',
								)
							);
							?>
						<?php else : ?>
							<div class="editor">
								<textarea
									id="<?php echo esc_attr( sanitize_key( $field['id'] . '-' . $template_type ) ); ?>"
									name="<?php echo esc_attr( $field['id'] . '[plain]' ); ?>"
									class="code" cols="25" rows="20"
									style="width: 97%;"><?php echo wp_kses_post( $content_plain ); ?>
								</textarea>
							</div>
						<?php endif; ?>
					</div>

					<?php if ( ! $has_local_file ) : ?>
						<?php if ( $field['support_variables'] ) : ?>
							<ol class="learn-press-email-variables<?php echo esc_html( $template_type == 'html' ? ' has-editor' : '' ); ?>"
								data-target="<?php echo esc_attr( sanitize_key( $field['id'] . '-' . $template_type ) ); ?>">
								<?php foreach ( $field['support_variables'] as $variable ) : ?>
									<li data-variable="<?php echo esc_attr( $variable ); ?>">
										<code><?php echo esc_html( $variable ); ?></code></li>
								<?php endforeach; ?>
							</ol>

							<p class="description">
								<?php esc_html_e( 'Click on any variables above to insert them into the email.', 'learnpress' ); ?>
							</p>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</td>
</tr>
