<?php
class LP_Email{
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
	 *  @var array $search
	 *  @see $replace
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
	 *  @var array $replace
	 *  @see $search
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
		'£',                                            // Pound sign
		'EUR',                                          // Euro sign. € ?
		'$',                                            // Dollar sign
		'',                                             // Unknown/unhandled entities
		' '                                             // Runs of spaces, post-handling
	);


	function __construct(){
		$this->id = str_replace( '-', '_', $this->id );

	}

	function __get( $key ){
		if( !empty( $this->{$key} ) ){
			return $this->{$key};
		}else{
			return LP()->settings->get( 'emails_' . $this->id . '.' . $key );
		}
	}

	public function format_string( $string ) {
		return str_replace( apply_filters( 'learn_press_email_format_string_find', $this->find, $this ), apply_filters( 'learn_press_email_format_string_replace', $this->replace, $this ), $string );
	}

	public function get_recipient() {
		return apply_filters( 'learn_press_email_recipient_' . $this->id, $this->recipients, $this->object );
	}

	public function get_subject(){
		return apply_filters( 'learn_press_email_subject_' . $this->id, $this->format_string( $this->subject ), $this->object );
	}

	public function get_content(){

		if ( $this->get_email_format() == 'plain' ) {
			$email_content = preg_replace( $this->plain_search, $this->plain_replace, strip_tags( $this->get_content_plain() ) );
		} else {
			$email_content = $this->get_content_html();
		}

		return wordwrap( $email_content, 70 );
	}

	public function get_heading() {
		return apply_filters( 'learn_press_email_heading_' . $this->id, $this->format_string( $this->heading ), $this->object );
	}

	public function get_content_plain() {}

	public function get_content_html() {}

	public function get_headers(){
		return apply_filters( 'learn_press_email_headers', "Content-Type: " . $this->get_content_format() . "\r\n", $this->id, $this->object );
	}

	public function get_attachments(){
		return apply_filters( 'learn_press_email_attachments', array(), $this->id, $this->object );
	}

	function get_from_address(){
		return sanitize_email( LP()->settings->get( 'emails_general.from_email' ) );
	}

	function get_from_name(){
		return sanitize_email( LP()->settings->get( 'emails_general.from_name' ) );
	}

	public function get_blogname() {
		return wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}

	function get_content_format(){
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
		return $this->email_format && class_exists( 'DOMDocument' ) ? $this->email_format : 'plain';
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
				$content = $emogrifier->emogrify();

			} catch ( Exception $e ) {

			}
		}

		return $content;
	}

	public function send( $to, $subject, $message, $headers, $attachments ) {

		add_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		add_filter( 'wp_mail_content_type', array( $this, 'get_content_format' ) );

		$message = apply_filters( 'learn_press_mail_content', $this->apply_style_inline( $message ) );
		//$return  =  wp_mail( $to, $subject, $message, $headers, $attachments );
		$return  =  LP_Emails::instance()->send ( $this->get_from_address(), $to, $subject, $message, $headers, $attachments );

		remove_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		remove_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		remove_filter( 'wp_mail_content_type', array( $this, 'get_content_format' ) );

		return $return;
	}
}