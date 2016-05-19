<?php

/**
 * Class LP_Email
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

class LP_Email {
	/**
	 * Email method ID.
	 *
	 * @var String
	 */
	public $id;

	/**
	 * Email method title.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * 'yes' if the method is enabled.
	 *
	 * @var string
	 */
	public $enabled;

	/**
	 * Description for the email.
	 *
	 * @var string
	 */
	public $description;

	/**
	 * Plain text template path.
	 *
	 * @var string
	 */
	public $template_plain;

	/**
	 * HTML template path.
	 *
	 * @var string
	 */
	public $template_html;

	/**
	 * Template path.
	 *
	 * @var string
	 */
	public $template_base;

	/**
	 * Recipients for the email.
	 *
	 * @var string
	 */
	public $recipient;

	/**
	 * Heading for the email content.
	 *
	 * @var string
	 */
	public $heading;

	/**
	 * Subject for the email.
	 *
	 * @var string
	 */
	public $subject;

	/**
	 * Default heading for the email content.
	 *
	 * @var string
	 */
	public $default_heading;

	/**
	 * Default subject for the email.
	 *
	 * @var string
	 */
	public $default_subject;

	/**
	 * Object this email is for, for example a customer, product, or email.
	 *
	 * @var object
	 */
	public $object;

	/**
	 * Strings to find in subjects/headings.
	 *
	 * @var array
	 */
	public $find;

	/**
	 * Strings to replace in subjects/headings.
	 *
	 * @var array
	 */
	public $replace;

	/**
	 * Mime boundary (for multipart emails).
	 *
	 * @var string
	 */
	public $mime_boundary;

	/**
	 * Mime boundary header (for multipart emails).
	 *
	 * @var string
	 */
	public $mime_boundary_header;

	/**
	 * True when email is being sent.
	 *
	 * @var bool
	 */
	public $sending;

	/**
	 *  List of preg* regular expression patterns to search for,
	 *  used in conjunction with $replace.
	 *  https://raw.github.com/ushahidi/wp-silcc/master/class.html2text.inc
	 *
	 * @var array $search
	 * @see $replace
	 */
	public $plain_search = array(
		"/\r/",                                          // Non-legal carriage return
		'/&(nbsp|#160);/i',                              // Non-breaking space
		'/&(quot|rdquo|ldquo|#8220|#8221|#147|#148);/i', // Double quotes
		'/&(apos|rsquo|lsquo|#8216|#8217);/i',           // Single quotes
		'/&gt;/i',                                       // Greater-than
		'/&lt;/i',                                       // Less-than
		'/&#38;/i',                                      // Ampersand
		'/&#038;/i',                                     // Ampersand
		'/&amp;/i',                                      // Ampersand
		'/&(copy|#169);/i',                              // Copyright
		'/&(trade|#8482|#153);/i',                       // Trademark
		'/&(reg|#174);/i',                               // Registered
		'/&(mdash|#151|#8212);/i',                       // mdash
		'/&(ndash|minus|#8211|#8722);/i',                // ndash
		'/&(bull|#149|#8226);/i',                        // Bullet
		'/&(pound|#163);/i',                             // Pound sign
		'/&(euro|#8364);/i',                             // Euro sign
		'/&#36;/',                                       // Dollar sign
		'/&[^&\s;]+;/i',                                 // Unknown/unhandled entities
		'/[ ]{2,}/'                                      // Runs of spaces, post-handling
	);

	/**
	 *  List of pattern replacements corresponding to patterns searched.
	 *
	 * @var array $replace
	 * @see $search
	 */
	public $plain_replace = array(
		'',                                             // Non-legal carriage return
		' ',                                            // Non-breaking space
		'"',                                            // Double quotes
		"'",                                            // Single quotes
		'>',                                            // Greater-than
		'<',                                            // Less-than
		'&',                                            // Ampersand
		'&',                                            // Ampersand
		'&',                                            // Ampersand
		'(c)',                                          // Copyright
		'(tm)',                                         // Trademark
		'(R)',                                          // Registered
		'--',                                           // mdash
		'-',                                            // ndash
		'*',                                            // Bullet
		'�',                                            // Pound sign
		'EUR',                                          // Euro sign. � ?
		'$',                                            // Dollar sign
		'',                                             // Unknown/unhandled entities
		' '                                             // Runs of spaces, post-handling
	);


	public function __construct() {
		$this->id = str_replace( '-', '_', $this->id );
		if ( is_null( $this->template_base ) ) {
			$this->template_base = LP()->plugin_path( 'templates/' );
		}
		if ( $this->is_current() ) {
			add_filter( 'learn_press_update_option_value', array( $this, '_remove_email_content_from_option' ), 99, 2 );
			$this->template_actions();
		}

		$this->heading      = LP()->settings->get( 'emails_' . $this->id . '.heading', $this->default_heading );
		$this->subject      = LP()->settings->get( 'emails_' . $this->id . '.subject', $this->default_subject );
		$this->email_format = LP()->settings->get( 'emails_' . $this->id . '.email_format' );
		$this->enable       = LP()->settings->get( 'emails_' . $this->id . '.enable' ) == 'yes';
	}

	public function __get( $key ) {
		if ( !empty( $this->{$key} ) ) {
			return $this->{$key};
		} else {
			return LP()->settings->get( 'emails_' . $this->id . '.' . $key );
		}
	}

	private function is_current() {

		return !empty( $_REQUEST['section'] ) && $_REQUEST['section'] == $this->id;
	}

	public function _remove_email_content_from_option( $options, $key ) {

		if ( !$this->is_current() ) {
			return;
		}

		if ( is_array( $options ) && ( array_key_exists( 'email_content_html', $options ) || array_key_exists( 'email_content_plain', $options ) ) ) {

			if ( array_key_exists( 'email_content_html', $options ) ) {
				$this->save_template( $options['email_content_html'], $this->template_html );
				unset( $options['email_content_html'] );
			}

			if ( array_key_exists( 'email_content_plain', $options ) ) {
				$this->save_template( $options['email_content_plain'], $this->template_plain );
				unset( $options['email_content_plain'] );
			}
		}
		return $options;
	}

	protected function template_actions() {
		if (
			( !empty( $this->template_html ) || !empty( $this->template_plain ) )
			&& ( !empty( $_GET['move_template'] ) || !empty( $_GET['delete_template'] ) )
			&& 'GET' == $_SERVER['REQUEST_METHOD']
		) {
			if ( empty( $_GET['_learn_press_email_nonce'] ) || !wp_verify_nonce( $_GET['_learn_press_email_nonce'], 'learn_press_email_template_nonce' ) ) {
				return;
			}

			if ( !current_user_can( 'edit_themes' ) ) {
				return;
			}

			if ( !empty( $_GET['move_template'] ) ) {
				$this->move_template( $_GET['move_template'] );
			}

			if ( !empty( $_GET['delete_template'] ) ) {
				$this->delete_template( $_GET['delete_template'] );
			}
		}
	}

	protected function move_template( $type ) {
		if ( $template = $this->get_template( 'template_' . $type ) ) {
			if ( !empty( $template ) ) {
				$theme_file = $this->get_theme_template_file( $template );
				if ( wp_mkdir_p( dirname( $theme_file ) ) && !file_exists( $theme_file ) ) {
					$template_file = $this->template_base . $template;
					// Copy template file
					copy( $template_file, $theme_file );
					echo '<div class="updated"><p>' . __( 'Template file copied to theme.', 'learnpress' ) . '</p></div>';
				}
			}
		}
	}

	protected function delete_template( $type ) {
		if ( $template = $this->get_template( 'template_' . $type ) ) {

			if ( !empty( $template ) ) {

				$theme_file = $this->get_theme_template_file( $template );

				if ( file_exists( $theme_file ) ) {
					unlink( $theme_file );
					echo '<div class="updated"><p>' . __( 'Template file deleted from theme.', 'learnpress' ) . '</p></div>';
				}
			}
		}
	}

	public function format_string( $string ) {
		return str_replace( apply_filters( 'learn_press_email_format_string_find', $this->find, $this ), apply_filters( 'learn_press_email_format_string_replace', $this->replace, $this ), $string );
	}

	public function get_recipient() {
		return apply_filters( 'learn_press_email_recipient_' . $this->id, $this->recipient, $this->object );
	}

	public function get_subject() {
		return apply_filters( 'learn_press_email_subject_' . $this->id, $this->format_string( $this->subject ), $this->object );
	}

	public function get_content() {
		if ( $this->get_email_format() == 'plain_text' ) {
			$email_content = preg_replace( $this->plain_search, $this->plain_replace, strip_tags( $this->get_content_plain() ) );
		} else {
			$email_content = $this->get_content_html();
		}

		return wordwrap( $email_content, 70 );
	}

	public function get_heading() {
		return apply_filters( 'learn_press_email_heading_' . $this->id, $this->format_string( $this->heading ), $this->object );
	}

	public function get_footer_text() {
		return apply_filters( 'learn_press_email_footer_text_' . $this->id, LP()->settings->get( 'emails_general.footer_text' ) );
	}

	public function get_content_plain() {
	}

	public function get_content_html() {
	}

	public function get_headers() {
		return apply_filters( 'learn_press_email_headers', "Content-Type: " . $this->get_content_format() . "\r\n", $this->id, $this->object );
	}

	public function get_attachments() {
		return apply_filters( 'learn_press_email_attachments', array(), $this->id, $this->object );
	}

	public function get_from_address() {
		return sanitize_email( LP()->settings->get( 'emails_general.from_email' ) );
	}

	public function get_from_name() {
		return sanitize_email( LP()->settings->get( 'emails_general.from_name' ) );
	}

	public function get_blogname() {
		return wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}

	public function get_content_format() {
		switch ( $this->get_email_format() ) {
			case 'html' :
				return 'text/html';
			case 'multipart' :
				return 'multipart/alternative';
			default :
				return 'text/plain';
		}
	}

	/**
	 * @return string
	 */
	public function get_email_format() {
		return $this->email_format && class_exists( 'DOMDocument' ) ? $this->email_format : 'plain_text';
	}

	public function apply_style_inline( $content ) {
		if ( in_array( $this->get_content_format(), array( 'text/html', 'multipart/alternative' ) ) && class_exists( 'DOMDocument' ) ) {

			// get CSS styles
			ob_start();
			learn_press_get_template( 'emails/email-styles.php' );
			$css = apply_filters( 'learn_press_email_styles', ob_get_clean() );

			try {
				LP()->_include( 'libraries/class-emogrifier.php' );
				// apply CSS styles inline for picky email clients
				$emogrifier = new Emogrifier( $content, $css );
				$content    = $emogrifier->emogrify();

			} catch ( Exception $e ) {

			}
		}

		return $content;
	}

	public function get_template( $type ) {
		$type = esc_attr( basename( $type ) );

		if ( 'template_html' == $type ) {
			return $this->template_html;
		} else if ( 'template_plain' == $type ) {
			return $this->template_plain;
		}

		return '';
	}

	protected function save_template( $code, $path ) {

		if ( current_user_can( 'edit_themes' ) && !empty( $code ) && !empty( $path ) ) {
			$saved = false;
			$file  = get_stylesheet_directory() . '/' . learn_press_template_path() . '/' . $path;
			$code  = stripslashes( $code );
			if ( is_writeable( $file ) ) {

				$f = fopen( $file, 'w+' );

				if ( $f !== false ) {
					fwrite( $f, $code );
					fclose( $f );
					$saved = true;
				}
			}

			if ( !$saved ) {
				$redirect = add_query_arg( 'learn_press_error', urlencode( __( 'Could not write to template file.', 'learnpress' ) ) );
				wp_redirect( $redirect );
				exit;
			}
		}
	}

	public function get_theme_template_file( $template ) {
		return get_stylesheet_directory() . '/' . apply_filters( 'learn_press_template_directory', 'learnpress', $template ) . '/' . $template;
	}

	public function admin_options( $obj ) {

	}

	public function send( $to, $subject, $message, $headers, $attachments ) {

		add_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		add_filter( 'wp_mail_content_type', array( $this, 'get_content_format' ) );

		$message = apply_filters( 'learn_press_mail_content', $this->apply_style_inline( $message ) );
		$return  = wp_mail( $to, $subject, $message, $headers, $attachments );

		if ( LP()->settings->get( 'debug' ) == 'yes' ) {
			ob_start();
			echo get_class( $this ) . '::' . __FUNCTION__ . "\n";
			print_r( func_get_args() );
			$log = ob_get_clean();
			LP_Debug::instance()->add( $log );
		}
		remove_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		remove_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		remove_filter( 'wp_mail_content_type', array( $this, 'get_content_format' ) );

		return $return;
	}

	public function _send( $from, $to, $subject, $message ) {

	}

	/**
	 * @param string $format
	 *
	 * @return array
	 */

	public function get_template_data( $format = 'plain' ) {
		return array( 'plain_text' => $format == 'plain' );
	}
}