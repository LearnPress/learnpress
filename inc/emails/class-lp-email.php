<?php

/**
 * Class LP_Email
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

class LP_Email extends LP_Abstract_Settings {
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
	 * For display the field to setting specific emails.
	 * .
	 * @var string
	 */
	public $recipients = '';

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

	public $debug = false;

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
	public $variables = array();

	/**
	 * @var array|null
	 */
	public $basic_variables = array();

	/**
	 * @var null
	 */
	public $general_variables = array();

	/**
	 * @var null
	 */
	public $support_variables = array();

	/**
	 * @var LP_Settings
	 */
	public $settings = null;

	/**
	 * @var string
	 */
	public $email_format = '';

	/**
	 * @var bool
	 */
	public $enable = false;

	/**
	 * @var string
	 */
	public $group = '';

	/**
	 * @var string
	 */
	protected $_option_id = '';

	/**
	 * LP_Email constructor.
	 */
	public function __construct() {
		// Set template base path to LP templates path if it is not set.
		if ( is_null( $this->template_base ) ) {
			$this->template_base = LP()->plugin_path( 'templates/' );
		}

		/**
		 * Set template folder if it is not set. Default is 'learnpress'
		 */
		if ( empty( $this->template_path ) ) {
			$this->template_path = learn_press_template_path();
		}

		if ( empty( $this->template_html ) ) {
			$this->template_html = "emails/{$this->id}.php";
		}

		if ( empty( $this->template_plain ) ) {
			$this->template_plain = "emails/plain/{$this->id}.php";
		}

		if ( ! $this->object ) {
			$this->object = array();
		}

		$this->_option_id = 'emails_' . $this->id;

		$this->settings = LP()->settings()->get_group( $this->_option_id, '' );

		/**
		 * Init general options
		 */
		$this->heading      = $this->settings->get( 'heading', $this->default_heading );
		$this->subject      = $this->settings->get( 'subject', $this->default_subject );
		$this->email_format = $this->settings->get( 'email_format' ) == 'plain_text' ? 'plain' : 'html';
		$this->enable       = $this->settings->get( 'enable' ) === 'yes';

		$this->basic_variables = array(
			'{{site_url}}',
			'{{site_title}}',
			'{{login_url}}',
			'{{email_heading}}',
		);

		$this->general_variables = array_merge(
			$this->basic_variables,
			array(
				'{{site_admin_email}}',
				'{{site_admin_name}}',
				'{{header}}',
				'{{footer}}',
				'{{footer_text}}'
			)
		);
	}

	public function enable( $value = null ) {
		if ( is_bool( $value ) ) {
			$this->enable = $value;
			$this->settings->set( 'enable', $value ? 'yes' : 'no' );
			$this->settings->update( 'learn_press_' . $this->_option_id, $this->settings->get() );
		}

		return $this->enable;
	}

	public function is_configured() {
		return LP()->settings->get( $this->_option_id );
	}

	/**
	 * Get variables support in mail.
	 *
	 * @return mixed
	 */
	public function get_variables_support() {
		return apply_filters( 'learn-press/email-variables-support', $this->support_variables, $this );
	}

	/**
	 * Magic function
	 *
	 * @param $key
	 *
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( ! empty( $this->{$key} ) ) {
			return $this->{$key};
		} else {
			return $this->settings->get( $key );
		}
	}

	private function is_current() {

		return ! empty( $_REQUEST['section'] ) && $_REQUEST['section'] == $this->id;
	}

	public function _remove_email_content_from_option( $options, $key ) {

		if ( ! $this->is_current() ) {
			return false;
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

	/**
	 * Apply the variables to string
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public function format_string( $string ) {
		$search = $replace = array();
		if ( is_array( $this->variables ) ) {
			$search  = array_keys( $this->variables );
			$replace = array_values( $this->variables );
		}
		$search  = apply_filters( 'learn-press/email-format-string-find', $search, $this );
		$replace = apply_filters( 'learn-press/email-format-string-replace', $replace, $this );

		return str_replace( $search, $replace, $string );
	}

	/**
	 * Get email recipient.
	 *
	 * @return string
	 */
	public function get_recipient() {
		return apply_filters( 'learn-press/email-recipient-' . $this->id, $this->recipient, $this->object );
	}

	/**
	 * Get email subject.
	 *
	 * @return string
	 */
	public function get_subject() {
		return apply_filters( 'learn-press/email-subject-' . $this->id, $this->format_string( $this->subject ), $this->object );
	}

	/**
	 * Get email content.
	 *
	 * @return string
	 */
	public function get_content() {
		$email_format = $this->get_email_format();

		if ( $email_format == 'plain_text' ) {
			$email_content = preg_replace( $this->plain_search, $this->plain_replace, strip_tags( $this->get_content_plain() ) );
		} else if ( in_array( $email_format, array( 'html', 'multipart' ) ) ) {
			$email_content = $this->get_content_html();
		} else {
			$email_content = preg_replace( $this->text_search, $this->text_replace, $this->get_content_text_message() );
		}

		$email_content = $this->format_string( $email_content );

		return wordwrap( $email_content, 70 );
	}

	/**
	 * Get email heading.
	 *
	 * @return string
	 */
	public function get_heading() {
		return apply_filters( 'learn-press/email-heading-' . $this->id, $this->format_string( $this->heading ), $this->object );
	}

	/**
	 * Get email footer text.
	 *
	 * @return string
	 */
	public function get_footer_text() {
		$text = wpautop( wp_kses_post( wptexturize( LP()->settings->get( 'emails_general.footer_text' ) ) ) );

		return apply_filters( 'learn-press/email-footer-text-' . $this->id, $text );
	}

	/**
	 * Get email content HTML.
	 *
	 * @return string
	 */
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
			$content       = $this->settings->get( 'email_content_html', file_get_contents( $template_file ) );
			$content       = stripslashes( $content );
		}

		return $content;
	}

	/**
	 * Get email content plain.
	 *
	 * @return string
	 */
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
			$content       = $this->settings->get( 'email_content_plain', file_get_contents( $template_file ) );
			$content       = stripslashes( $content );
		}

		return $content;
	}

	/**
	 * Get content context message.
	 *
	 * @return string
	 */
	public function get_content_text_message() {
		return apply_filters( 'learn-press/email-text-message-' . $this->id, $this->settings->get( 'content_text_message' ) );
	}

	/**
	 * Get email headers.
	 *
	 * @return string|array
	 */
	public function get_headers() {
		return apply_filters( 'learn-press/email-headers', "Content-Type: " . $this->get_content_format() . "\r\n", $this->id, $this->object );
	}

	/**
	 * Get email attachments.
	 *
	 * @return array
	 */
	public function get_attachments() {
		return apply_filters( 'learn-press/email-attachments', array(), $this->id, $this->object );
	}

	/**
	 * Get FROM address. Default is admin email.
	 *
	 * @return string
	 */
	public function get_from_address() {
		$email = sanitize_email( LP()->settings->get( 'emails_general.from_email' ) );

		if ( ! is_email( $email ) ) {
			$email = get_option( 'admin_email' );
		}

		return $email;
	}

	/**
	 * Get FROM name. Default is Blog name.
	 * @return string
	 */
	public function get_from_name() {
		$name = sanitize_email( LP()->settings->get( 'emails_general.from_name' ) );

		if ( empty( $name ) ) {
			$name = get_option( 'blogname' );
		}

		return $name;
	}

	/**
	 * Get image header in general settings.
	 *
	 * @return string
	 */
	public function get_image_header() {
		return LP()->settings->get( 'emails_general.header_image' );
	}

	/**
	 * Get WP Blog name.
	 *
	 * @return string
	 */
	public function get_blogname() {
		return wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}

	/**
	 * Get email content format.
	 *
	 * @return string
	 */
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
	 * Apply css from external to inline.
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function apply_style_inline( $content ) {
		if ( in_array( $this->get_content_format(), array(
				'text/html',
				'multipart/alternative'
			) ) && class_exists( 'DOMDocument' )
		) {

			// get CSS styles
			ob_start();
			learn_press_get_template( 'emails/email-styles.php' );
			$css = apply_filters( 'learn_press_email_styles', ob_get_clean(), $this->id, $this );

			try {
				if ( ! class_exists( 'Emogrifier' ) ) {
					include_once LP_PLUGIN_PATH . 'inc/libraries/class-emogrifier.php';
				}
				// apply CSS styles inline for picky email clients
				$emogrifier = new Emogrifier( $content, $css );
				$content    = $emogrifier->emogrify();

			}
			catch ( Exception $e ) {

			}
		}

		return apply_filters( 'learn-press/email-content-inline-style', $content, $this->id );
	}

	/**
	 * Get template file from type.
	 *
	 * @param string $type
	 *
	 * @return string
	 */
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
	}

	/**
	 * Get template file.
	 *
	 * @param string $template
	 * @param string $template_path
	 *
	 * @return string
	 */
	public function get_theme_template_file( $template, $template_path = null ) {
		$template_dir = apply_filters( 'learn-press/template-directory', $template_path ? $template_path : learn_press_template_path(), $template );

		return join(
			'',
			array(
				trailingslashit( get_stylesheet_directory() ),
				trailingslashit( $template_dir ),
				$template
			)
		);
	}

	/**
	 * Send email.
	 *
	 * @param string $to
	 * @param string $subject
	 * @param string $message
	 * @param array  $headers
	 * @param array  $attachments
	 *
	 * @return bool
	 */
	public function send( $to, $subject, $message, $headers, $attachments ) {

		if($this->debug){
			return false;
		}

		add_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		add_filter( 'wp_mail_content_type', array( $this, 'get_content_format' ) );

		$return  = false;
		$message = apply_filters( 'learn-press/email-content', $this->apply_style_inline( $message ), $this );

		if ( ! is_array( $to ) ) {
			$to = preg_split( '~\s?,\s?~', $to );
		}

		$separated = apply_filters( 'learn_press_email_to_separated', false, $to, $this );

		if ( ! $separated ) {
			$return = wp_mail( $to, $subject, $message, $headers, $attachments );
		} else {
			if ( is_array( $to ) ) {
				foreach ( $to as $t ) {
					$return = wp_mail( $t, $subject, $message, $headers, $attachments );
				}
			}
		}

		//
		remove_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		remove_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		remove_filter( 'wp_mail_content_type', array( $this, 'get_content_format' ) );

		return $return;
	}

	/**
	 * Get format of email template content.
	 *
	 * @param string $format
	 *
	 * @return array
	 */
	public function get_template_data( $format = 'plain' ) {

		return $this->object;
		///return array( 'plain_text' => $format == 'plain' );
	}

	/**
	 * Get common template data variables.
	 *
	 * @param string $format
	 *
	 * @return array
	 */
	public function get_common_template_data( $format = 'plain' ) {
		$heading     = strip_tags( $this->get_heading() );
		$footer_text = strip_tags( $this->get_footer_text() );
		if ( $format != 'plain' ) {
			$header = LP_Emails::instance()->email_header( $heading, false );
			$footer = LP_Emails::instance()->email_footer( $footer_text, false );
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

	/**
	 * Build list of template variables from an array.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function data_to_variables( $data = null ) {
		if ( ! $data ) {
			$data = $this->get_common_template_data();
		}
		$variables = array();
		if ( is_array( $data ) ) {
			foreach ( $data as $k => $v ) {
				$variables[ "{{" . $k . "}}" ] = $v;
			}
		}

		return $variables;
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	public function get_field_name( $name ) {
		return $this->_option_id . "[$name]";
	}

	/**
	 * Default options for all emails.
	 * Almost the emails are the same with options.
	 *
	 * @return array
	 */
	protected function _default_settings() {
		$default = array(
			array(
				'type'  => 'heading',
				'title' => $this->title,
				'desc'  => $this->description
			),
			array(
				'title'   => __( 'Enable', 'learnpress' ),
				'type'    => 'yes-no',
				'default' => 'no',
				'id'      => $this->get_field_name( 'enable' )
			),
			array(
				'title'      => __( 'Recipient(s)', 'learnpress' ),
				'type'       => 'text',
				'default'    => get_option( 'admin_email' ),
				'id'         => $this->get_field_name( 'recipients' ),
				'desc'       => sprintf( __( 'Email recipient(s) (separated by comma), default: <code>%s</code>', 'learnpress' ), get_option( 'admin_email' ) ),
				'visibility' => array(
					'state'       => 'show',
					'conditional' => array(
						array(
							'field'   => $this->get_field_name( 'enable' ),
							'compare' => '=',
							'value'   => 'yes'
						)
					)
				)
			),
			array(
				'title'      => __( 'Subject', 'learnpress' ),
				'type'       => 'text',
				'default'    => $this->default_subject,
				'id'         => $this->get_field_name( 'subject' ),
				'desc'       => sprintf( __( 'Email subject, default: <code>%s</code>', 'learnpress' ), $this->default_subject ),
				'visibility' => array(
					'state'       => 'show',
					'conditional' => array(
						array(
							'field'   => $this->get_field_name( 'enable' ),
							'compare' => '=',
							'value'   => 'yes'
						)
					)
				)
			),
			array(
				'title'      => __( 'Heading', 'learnpress' ),
				'type'       => 'text',
				'default'    => $this->default_heading,
				'id'         => $this->get_field_name( 'heading' ),
				'desc'       => sprintf( __( 'Email heading, default: <code>%s</code>', 'learnpress' ), $this->default_heading ),
				'visibility' => array(
					'state'       => 'show',
					'conditional' => array(
						array(
							'field'   => $this->get_field_name( 'enable' ),
							'compare' => '=',
							'value'   => 'yes'
						)
					)
				)
			),
			array(
				'title'                => __( 'Email content', 'learnpress' ),
				'type'                 => 'email-content',
				'default'              => '',
				'id'                   => $this->get_field_name( 'email_content' ),
				'template_base'        => $this->template_base,
				'template_path'        => $this->template_path,//default learnpress
				'template_html'        => $this->template_html,
				'template_plain'       => $this->template_plain,
				'template_html_local'  => $this->get_theme_template_file( 'html', $this->template_path ),
				'template_plain_local' => $this->get_theme_template_file( 'plain', $this->template_path ),
				'support_variables'    => $this->get_variables_support(),
				'visibility'           => array(
					'state'       => 'show',
					'conditional' => array(
						array(
							'field'   => $this->get_field_name( 'enable' ),
							'compare' => '=',
							'value'   => 'yes'
						)
					)
				)
			)
		);

		/**
		 * In case the email is not for sending to specific admin (like user who has bought course or author of course, etc..)
		 * So, we do not need this field.
		 */
		if ( empty( $this->recipients ) ) {
			unset( $default[2] );
		}

		return $default;
	}

	public function get_settings() {
		return apply_filters(
			'learn-press/email-settings/' . $this->id . '/settings',
			$this->_default_settings()
		);
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->title;
	}
}
