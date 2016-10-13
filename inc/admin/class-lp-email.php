<?php

/**
 * Class LP_Email
 */
class LP_Email_bakup {

	/**
	 * @var array
	 */
	protected $to = array();

	/**
	 * @var string
	 */
	protected $subject = '';

	/**
	 * @var string
	 */
	protected $message = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
	}

	/**
	 * Filter headers.
	 * Replaces new lines.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public function filter( $value ) {
		return str_ireplace( array( "\r", "\n", '%0A', '%0D', '<CR>', '<LF>' ), '', $value );
	}

	/**
	 * Set email template.
	 *
	 * @param $action
	 */
	public function set_action( $action ) {
		$emails_settings = get_option( '_lpr_settings_emails' );

		$this->subject = isset( $emails_settings[$action]['subject'] ) ? $emails_settings[$action]['subject'] : '';
		$this->message = isset( $emails_settings[$action]['message'] ) ? $emails_settings[$action]['message'] : '';
	}

	/**
	 * Parse subject placeholders.
	 *
	 * @param array $vars
	 */
	public function parse_email( $vars ) {
		if ( empty( $vars ) ) {
			return;
		}

		foreach ( $vars as $key => $value ) {
			$this->subject = str_replace( '{' . $key . '}', $value, $this->subject );
			$this->message = str_replace( '{' . $key . '}', $value, $this->message );
		}
	}

	/**
	 * Add email recipient.
	 *
	 * @param string $email
	 */
	public function add_recipient( $email ) {
		$this->to[] = $email;
	}

	/**
	 * Send email.
	 *
	 * @return boolean
	 */
	public function send() {
		$email_settings  = get_option( '_lpr_settings_emails' );
		$headers[]       = 'Content-Type: text/html; charset=UTF-8';
		$headers['from'] = '';

		if ( is_array( $email_settings['general'] ) && !empty( $email_settings['general']['from_email'] ) ) {
			$headers['from'] .= 'From:';

			if ( !empty( $email_settings['general']['from_name'] ) ) {
				$headers['from'] .= ' ' . $this->filter( $email_settings['general']['from_name'] );
			}

			$headers['from'] .= ' <' . sanitize_email( $email_settings['general']['from_email'] ) . ">\r\n";
		}

		return wp_mail( $this->to, $this->filter( $this->subject ), stripslashes( $this->message ), $headers );
	}


}
