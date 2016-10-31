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

	/**
	 * List of pattern search corresponding to patterns replace.
	 *
	 * @var array text shortcode
	 */
	public $text_search = array();

	/**
	 * List of pattern to replace
	 *
	 * @var array text replace
	 */
	public $text_replace = array();

	/**
	 * Text message description
	 *
	 * @var string
	 */
	public $email_text_message_description = '';

	/**
	 * @var string
	 */
	public $template_path = '';

	/**
	 * @var null
	 */
	public $variables = null;

	/**
	 * @var null
	 */
	public $support_variables = null;

	/**
	 * LP_Email constructor.
	 */
	public function __construct() {
		$this->id = str_replace( '-', '_', $this->id );
		if ( is_null( $this->template_base ) ) {
			$this->template_base = LP()->plugin_path( 'templates/' );
		}
		if ( $this->is_current() ) {
			///add_filter( 'learn_press_update_option_value', array( $this, '_remove_email_content_from_option' ), 99, 2 );
			$this->template_actions();
		}

		if ( empty( $this->template_path ) ) {
			$this->template_path = learn_press_template_path();
		}

		if ( !$this->object ) {
			$this->object = array();
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
		$search = $replace = array();
		if ( is_array( $this->variables ) ) {
			$search  = array_keys( $this->variables );
			$replace = array_values( $this->variables );
		}
		return str_replace( apply_filters( 'learn_press_email_format_string_find', $search, $this ), apply_filters( 'learn_press_email_format_string_replace', $replace, $this ), $string );
	}

	public function get_recipient() {
		return apply_filters( 'learn_press_email_recipient_' . $this->id, $this->recipient, $this->object );
	}

	public function get_subject() {
		return apply_filters( 'learn_press_email_subject_' . $this->id, $this->format_string( $this->subject ), $this->object );
	}

	public function get_content() {
		$email_format = $this->get_email_format();
		if ( $email_format == 'plain_text' ) {
			$email_content = preg_replace( $this->plain_search, $this->plain_replace, strip_tags( $this->get_content_plain() ) );
		} else if ( in_array( $email_format, array( 'html', 'multipart' ) ) ) {
			$email_content = $this->get_content_html();
		} else {
			$this->_prepare_content_text_message();
			$email_content = preg_replace( $this->text_search, $this->text_replace, $this->get_content_text_message() );
		}

		if ( is_array( $this->variables ) ) {
			$search        = array_keys( $this->variables );
			$replace       = array_values( $this->variables );
			$email_content = str_replace( $search, $replace, $email_content );
		}


		return wordwrap( $email_content, 70 );
	}

	public function get_heading() {
		return apply_filters( 'learn_press_email_heading_' . $this->id, $this->format_string( $this->heading ), $this->object );
	}

	public function get_footer_text() {
		return apply_filters( 'learn_press_email_footer_text_' . $this->id, LP()->settings->get( 'emails_general.footer_text' ) );
	}

	public function get_content_html() {
		$template   = $this->get_template( 'template_html' );
		$local_file = $this->get_theme_template_file( $template, $this->template_path );
		if ( file_exists( $local_file ) ) {
			$args = $this->get_template_data( 'html' );
			is_array( $args ) && extract( $args );
			ob_start();
			include $local_file;
			$content = ob_get_clean();
		} else {
			$template_file = $this->template_base . $template;
			$content       = LP()->settings->get( 'emails_' . $this->id . '.email_content_html', file_get_contents( $template_file ) );
			$content       = stripslashes( $content );
		}
		return $content;
	}

	public function get_content_plain() {
		$template   = $this->get_template( 'template_plain' );
		$local_file = $this->get_theme_template_file( $template, $this->template_path );
		if ( file_exists( $local_file ) ) {
			$args = $this->get_template_data( 'plain' );
			is_array( $args ) && extract( $args );
			ob_start();
			include $local_file;
			$content = ob_get_clean();
		} else {
			$template_file = $this->template_base . $template;
			$content       = LP()->settings->get( 'emails_' . $this->id . '.email_content_plain', file_get_contents( $template_file ) );
			$content       = stripslashes( $content );
		}
		return $content;
	}

	public function _prepare_content_text_message() {
	}

	public function get_content_text_message() {
		return apply_filters( 'learn_press_email_text_message_' . $this->id, LP()->settings->get( 'emails_' . $this->id . '.content_text_message' ) );
	}

	public function get_headers() {
		return apply_filters( 'learn_press_email_headers', "Content-Type: " . $this->get_content_format() . "\r\n", $this->id, $this->object );
	}

	public function get_attachments() {
		return apply_filters( 'learn_press_email_attachments', array(), $this->id, $this->object );
	}

	public function get_from_address() {
		$email = sanitize_email( LP()->settings->get( 'emails_general.from_email' ) );
		if ( !is_email( $email ) ) {
			$email = get_option( 'admin_email' );
		}
		return $email;
	}

	public function get_from_name() {
		$name = sanitize_email( LP()->settings->get( 'emails_general.from_name' ) );
		if ( empty( $name ) ) {
			$name = get_option( 'blogname' );
		}
		return $name;
	}

	public function get_blogname() {
		return wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}

	public function get_content_format() {
		switch ( $this->get_email_format() ) {
			case 'text_message' :
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

	/**
	 * @param $content
	 *
	 * @return string
	 */
	public function apply_style_inline( $content ) {
		if ( in_array( $this->get_content_format(), array( 'text/html', 'multipart/alternative' ) ) && class_exists( 'DOMDocument' ) ) {

			// get CSS styles
			ob_start();
			learn_press_get_template( 'emails/email-styles.php' );
			$css = apply_filters( 'learn_press_email_styles', ob_get_clean(), $this->id, $this );

			try {
				if ( !class_exists( 'Emogrifier' ) ) {
					LP()->_include( 'libraries/class-emogrifier.php' );
				}
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
		return;
		if ( current_user_can( 'edit_themes' ) && !empty( $code ) && !empty( $path ) ) {
			$saved = false;
			$file  = trailingslashit( get_stylesheet_directory() ) . trailingslashit( learn_press_template_path() ) . $path;
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

	public function get_theme_template_file( $template, $template_path = null ) {
		return trailingslashit( get_stylesheet_directory() ) . trailingslashit( apply_filters( 'learn_press_template_directory', $template_path ? $template_path : learn_press_template_path(), $template ) ) . $template;
	}

	public function admin_options( $obj ) {

	}

	public function send( $to, $subject, $message, $headers, $attachments ) {

		add_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		add_filter( 'wp_mail_content_type', array( $this, 'get_content_format' ) );

		$message = apply_filters( 'learn_press_mail_content', $this->apply_style_inline( $message ), $this );
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

	public function get_common_template_data( $format = 'plain' ) {
		$heading     = strip_tags( $this->get_heading() );
		$footer_text = strip_tags( $this->get_footer_text() );
		if ( $format != 'plain' ) {
			$header = LP_Emails::instance()->email_header( $heading, true );
			$footer = LP_Emails::instance()->email_footer( $footer_text, true );
		} else {
			$header = $heading;
			$footer = $footer_text;
		}
		$admin_user = get_user_by( 'email', get_option( 'admin_email' ) );
		$common     = array(
			'header'           => $header,
			'footer'           => $footer,
			'email_heading'    => $heading,
			'footer_text'      => $footer_text,
			'site_url'         => get_site_url(),
			'site_title'       => $this->get_blogname(),
			'site_admin_email' => get_option( 'admin_email' ),
			'site_admin_name'  => learn_press_get_profile_display_name( $admin_user ),
			'login_url'        => learn_press_get_login_url(),
			'plain_text'       => $format == 'plain'
		);
		if ( ( $num = func_num_args() ) > 1 ) {
			for ( $i = 1; $i < $num; $i ++ ) {
				$a = func_get_arg( $i );
				if ( is_array( $a ) ) {
					$common = array_merge( $common, $a );
				}
			}
		}
		return $common;
	}

	public function data_to_variables( $data = null ) {
		if ( !$data ) {
			$data = $this->get_common_template_data();
		}
		$variables = array();
		if ( is_array( $data ) ) {
			foreach ( $data as $k => $v ) {
				$variables["{{" . $k . "}}"] = $v;
			}
		}
		return $variables;
	}
}