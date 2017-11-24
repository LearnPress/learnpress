<?php
$email_format = $settings->get( 'emails_' . $this->id . '.email_format', 'plain_text' );
?>
<tr>
	<th scope="row">
		<label for="learn-press-emails-<?php echo $this->id; ?>-email-content"><?php _e( 'Email content', 'learnpress' ); ?></label>
	</th>
	<td>
		<p><?php learn_press_email_formats_dropdown( array( 'name' => $settings_class->get_field_name( 'emails_' . $this->id . '[email_format]' ), 'id' => 'learn_press_email_formats', 'selected' => $email_format ) ); ?></p>
		<div id="templates">
			<?php
			$templates = array(
				'html'  => __( 'HTML template', 'learnpress' ),
				'plain' => __( 'Plain text template', 'learnpress' )
			);
			foreach ( $templates as $template_type => $title ) :
				$template = $this->get_template( 'template_' . $template_type );
				/*if ( empty( $template ) ) {
					continue;
				}*/
				$local_file    = $this->get_theme_template_file( $template, $this->template_path );
				$template_file = $this->template_base . $template;
				$template_dir  = $this->template_path;//learn_press_template_path();
				$classes       = array( 'learn-press-email-template' );
				if ( $template_type == 'html' ) {
					$classes[] = $template_type . ' multipart';
				} else {
					$classes[] = 'plain_text';
				}
				if ( $template_type == $email_format || ( ( $email_format == 'plain_text' || !$email_format ) && $template_type == 'plain' ) ) {
				} else {
					$classes[] = 'hide-if-js';
				}
				$content_html  = stripslashes( $settings->get( 'emails_' . $this->id . '.email_content_html', file_get_contents( $template_file ) ) );
				$content_plain = stripslashes( $settings->get( 'emails_' . $this->id . '.email_content_plain', file_get_contents( $template_file ) ) );

				$has_local_file = file_exists( $local_file );

				$theme_dir      = get_template_directory();
				$stylesheet_dir = get_stylesheet_directory();

				if ( $theme_dir != $stylesheet_dir ) {
					$theme_dir = $stylesheet_dir;
				}
				$theme_folder = basename( $theme_dir );
				?>
				<div class="<?php echo join( ' ', $classes ); ?>">

					<?php if ( $has_local_file ): ?>
						<textarea rows="10" style="width: 90%;" readonly="readonly"><?php echo stripslashes( file_get_contents( $local_file ) ); ?></textarea>
						<p class="description">
							<?php printf( __( 'This template has been overridden by your theme and can be found in: <code>%s</code>. <br />Please open the file in an editor program to edit', 'learnpress' ), $theme_folder . '/' . $template_dir . '/' . $template ); ?>
						</p>
					<?php endif; ?>
					<div class="<?php echo $has_local_file ? 'hide-if-js' : ''; ?>">
						<?php if ( $template_type == 'html' ): ?>
							<?php
							wp_editor(
								$content_html,
								'learn_press_emails_' . $this->id . '_' . $template_type,
								array(
									'textarea_rows' => 20,
									'wpautop'       => false,
									'textarea_name' => $settings_class->get_field_name( 'emails_' . $this->id . '[email_content_html]' )
								)
							); ?>
						<?php else: ?>
							<div class="editor">
								<textarea id="<?php echo esc_attr( 'learn_press_emails_' . $this->id . '_' . $template_type ); ?>" name="<?php echo $settings_class->get_field_name( 'emails_' . $this->id . '[email_content_plain]' ); ?>" class="code" cols="25" rows="20" style="width: 97%;"><?php echo $content_plain; ?></textarea>
							</div>
						<?php endif; ?>
					</div>

					<?php if ( !$has_local_file ): ?>
						<?php if ( $this->get_variables_support() ): ?>
							<p>
								<strong><?php esc_html_e( 'Click on variables to add it into email content.', 'learnpress' ); ?></strong>
							</p>
							<ol class="learn-press-email-variables<?php echo $template_type == 'html' ? ' has-editor' : ''; ?>" data-target="<?php echo esc_attr( 'learn_press_emails_' . $this->id . '_' . $template_type ); ?>">
								<?php foreach ( $this->support_variables as $variable ): ?>
									<li data-variable="<?php echo esc_attr( $variable ); ?>">
										<code><?php echo $variable; ?></code></li>
								<?php endforeach; ?>
							</ol>
						<?php endif; ?>
						<p class="description">
							<?php printf( __( 'To override and edit this email template copy <code>%s</code> to your theme folder: <code>%s</code>.', 'learnpress' ), plugin_basename( $template_file ), $theme_folder . '/' . $template_dir . '/' . $template ); ?>
						</p>
					<?php endif; ?>
				</div>
				<?php
			endforeach;
			?>
		</div>
	</td>
</tr>