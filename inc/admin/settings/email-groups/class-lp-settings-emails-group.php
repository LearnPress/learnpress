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
				if ( ! array_key_exists( $email->id, $ids ) ) {
					continue;
				}
				$email->group      = $this;
				$ids[ $email->id ] = $email;
			}
		}

		$this->items = $ids;

		add_action( 'learn-press/admin/setting-payments/admin-options-' . $this->group_id, array(
			$this,
			'admin_page'
		) );
	}

	public function admin_page() {
		$current = $this->get_current_section();

		echo '<ul class="subsubsub">';
		foreach ( $this->items as $email ) {
			if ( $current == $email->id ) {
				echo '<li class="active"><span>' . $email . '</span></li>';
			} else {
				echo '<li><a href="' . add_query_arg( 'sub-section', $email->id ) . '">' . $email . '</a></li>';
			}
		}
		echo '</ul>';

		if ( ! empty( $this->items[ $current ] ) ) {
			$this->items[ $current ]->admin_options();
		}
	}

	public function get_current_section() {
		$ids = array_keys( $this->items );

		return ! empty( $_REQUEST['sub-section'] ) ? $_REQUEST['sub-section'] : reset( $ids );
	}

	public function __toString() {
		$name = str_replace( array( 'LP_Settings_', '_' ), array( '', ' ' ), get_class( $this ) );

		return (string) $name;
	}
}