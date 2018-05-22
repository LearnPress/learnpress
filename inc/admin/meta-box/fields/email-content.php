<?php
/**
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'RWMB_Email_Content_Field' ) ) {
	class RWMB_Email_Content_Field extends RWMB_Field {

		public static function admin_enqueue_scripts() {
			wp_enqueue_script( 'learn-press-email-content-field', LP()->plugin_url( 'inc/admin/meta-box/assets/email-content.js' ) );
			wp_enqueue_style( 'learn-press-email-content-field', LP()->plugin_url( 'inc/admin/meta-box/assets/email-content.css' ) );
		}

		/**
		 * Parse default value for $meta and field
		 *
		 * @param mixed $meta
		 * @param array $field
		 */
		public static function sanitize( &$meta, &$field ) {
			$meta  = wp_parse_args(
				$meta,
				array(
					'format' => 'plain_text',
					'html'   => '',
					'plain'  => ''
				)
			);
			$field = wp_parse_args(
				$field,
				array(
					'template_base'        => '',
					'template_path'        => '',//default learnpress
					'template_html'        => '',
					'template_plain'       => '',
					'template_html_local'  => '',
					'template_plain_local' => '',
					'support_variables'    => array()
				)
			);
		}

		/**
		 * Get field HTML
		 *
		 * @param mixed $meta
		 * @param mixed $field
		 *
		 * @return string
		 */
		static function html( $meta, $field = '' ) {
			self::sanitize( $meta, $field );

			$email_format = $meta['format'];
			ob_start();
			learn_press_email_formats_dropdown(
				array(
					'name'        => $field['field_name'] . '[format]',
					'class'       => 'lp-email-format',
					'selected'    => $email_format,
					'option_none' => array( '' => __( 'General setting', 'learnpress' ) )
				)
			);
			?>
            <p class="description"><?php printf( __( 'Choose <strong>General setting</strong> to apply the setting from Email <a href="%s">General Options</a> ', 'learnpress' ), admin_url( 'admin.php?page=learn-press-settings&tab=emails&section=general' ) ); ?></p>
            <div class="lp-email-templates">
				<?php
				$templates = learn_press_email_formats();

				foreach ( $templates as $template_type => $title ) :
					$template = ! empty( $field["template_{$template_type}"] ) ? $field["template_{$template_type}"] : null;
					//$template = $this->get_template( 'template_' . $template_type );
					if ( empty( $template ) ) {
						continue;
					}

					$local_file    = ! empty( $field["template_{$template_type}_local"] ) ? $field["template_{$template_type}_local"] : null;//$this->get_theme_template_file( $template, $this->template_path );
					$template_file = $field['template_base'] . $template;//$this->template_base . $template;
					$template_dir  = $field['template_path'];//$this->template_path;//learn_press_template_path();
					$classes       = array( 'learn-press-email-template' );
					//extract( $field['extra'] );
					if ( $template_type == 'html' ) {
						$classes[] = $template_type . ' multipart';
					} else {
						$classes[] = 'plain_text';
					}
					if ( $template_type == $email_format || ( ( $email_format == 'plain_text' || ! $email_format ) && $template_type == 'plain' ) ) {
					} else {
						$classes[] = 'hide-if-js';
					}
					$content_html  = $meta['html'] ? stripslashes( $meta['html'] ) : @file_get_contents( $template_file );
					$content_plain = $meta['plain'] ? stripslashes( $meta['plain'] ) : @file_get_contents( $template_file );

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
                            <textarea rows="10" style="width: 90%;"
                                      readonly="readonly"><?php echo stripslashes( file_get_contents( $local_file ) ); ?></textarea>
                            <p class="description">
								<?php printf( __( 'This template has been overridden by your theme and can be found in: <code>%s</code>. <br />Please open the file in an editor program to edit', 'learnpress' ), $theme_folder . '/' . $template_dir . '/' . $template ); ?>
                            </p>
						<?php endif; ?>
                        <div class="<?php echo $has_local_file ? 'hide-if-js' : ''; ?>">
							<?php if ( $template_type == 'html' ): ?>
								<?php
								wp_editor(
									$content_html,
									sanitize_key( $field['field_name'] . '-' . $template_type ),
									array(
										'textarea_rows' => 20,
										'wpautop'       => false,
										'textarea_name' => $field['field_name'] . '[html]'
									)
								); ?>
							<?php else: ?>
                                <div class="editor">
                                    <textarea
                                            id="<?php echo esc_attr( sanitize_key( $field['field_name'] . '-' . $template_type ) ); ?>"
                                            name="<?php echo $field['field_name'] . '[plain]'; ?>"
                                            class="code" cols="25" rows="20"
                                            style="width: 97%;"><?php echo $content_plain; ?></textarea>
                                </div>
							<?php endif; ?>
                        </div>

						<?php if ( ! $has_local_file ): ?>
							<?php if ( $field['support_variables'] /*$this->get_variables_support() */ ): ?>
                                <ol class="learn-press-email-variables<?php echo $template_type == 'html' ? ' has-editor' : ''; ?>"
                                    data-target="<?php echo esc_attr( sanitize_key( $field['field_name'] . '-' . $template_type ) ); ?>">
									<?php foreach ( $field['support_variables'] as $variable ): ?>
                                        <li data-variable="<?php echo esc_attr( $variable ); ?>">
                                            <code><?php echo $variable; ?></code></li>
									<?php endforeach; ?>
                                </ol>
                                <p class="description">
									<?php esc_html_e( 'Click on variables to add it into email content.', 'learnpress' ); ?>
                                </p>
							<?php endif; ?>
						<?php endif; ?>
                    </div>
					<?php
				endforeach;
				?>
            </div>
			<?php

			return ob_get_clean();
		}

		/**
		 * Get content of an email.
		 *
		 * @since 3.0.0
		 *
		 * @param string $format
		 * @param array  $meta
		 * @param array  $field
		 *
		 * @return bool|string
		 */
		public static function get_email_content( $format, $meta = array(), $field = array() ) {

			if ( $meta && isset( $meta[ $format ] ) ) {
				$content = stripslashes( $meta[ $format ] );
			} else {
				$template      = ! empty( $field["template_{$format}"] ) ? $field["template_{$format}"] : null;
				$template_file = $field['template_base'] . $template;
				$content       = @file_get_contents( $template_file );
			}

			return $content;
		}
	}
}