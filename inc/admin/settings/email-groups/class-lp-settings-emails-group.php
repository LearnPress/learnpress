<?php

/**
 * Class LP_Settings_Emails_Group
 *
 * @since 3.0.0
 */
class LP_Settings_Emails_Group extends LP_Settings {

	/**
	 * Items
	 *
	 * @var array
	 */
	public $items = array();

	/**
	 * @var string
	 */
	public $group_id = '';

	/**
	 * LP_Settings_Emails_Group constructor.
	 */
	public function __construct() {
		parent::__construct();

		$emails = LP_Emails::instance()->emails;

		$ids = array_fill_keys( $this->items, '' );

		foreach ( $this->items as $id ) {
			foreach ( $emails as $email ) {
				if ( ! is_object( $email ) ) {
					continue;
				}

				if ( ! array_key_exists( $email->id, $ids ) ) {
					continue;
				}
				$email->group      = $this;
				$ids[ $email->id ] = $email;
			}
		}

		$this->items = $ids;

		add_action( 'learn-press/admin/setting-payments/admin-options-' . $this->group_id, array( $this, 'output_settings' ) );
		add_filter( 'learn-press/admin/get-settings/admin-options-' . $this->group_id, array( $this, 'filter_get_settings' ) );
	}

	public function output_settings() {
		$current = $this->get_current_section();

		echo '<ul class="subsubsub">';

		foreach ( $this->items as $email ) {
			if ( ! is_object( $email ) ) {
				continue;
			}
			if ( $current == $email->id ) {
				echo '<li class="active"><span>' . wp_kses_post( $email->title ) . '</span></li>';
			} else {
				echo '<li><a href="' . esc_url_raw( add_query_arg( 'sub-section', $email->id ) ) . '">' . wp_kses_post( $email->title ) . '</a></li>';
			}
		}

		echo '</ul>';

		if ( ! empty( $this->items[ $current ] ) ) {
			$this->items[ $current ]->admin_option_settings(); // Email content show in here <nhamdv> LP_Abstract_Settings.
		}
	}

	/**
	 * Filter save setting.
	 *
	 * @return array list setting Email.
	 * @version 4.0.0
	 * @author Nhamdv
	 */
	public function filter_get_settings() {
		$current = $this->get_current_section();

		if ( ! empty( $this->items[ $current ] ) ) {
			return $this->items[ $current ]->get_settings();
		}
	}

	public function get_current_section() {
		$ids = array_keys( $this->items );

		return ! empty( $_REQUEST['sub-section'] ) ? sanitize_text_field( $_REQUEST['sub-section'] ) : reset( $ids );
	}

	public function __toString() {
		$name = str_replace( array( 'LP_Settings_', '_' ), array( '', ' ' ), get_class( $this ) );

		return (string) $name;
	}
}
