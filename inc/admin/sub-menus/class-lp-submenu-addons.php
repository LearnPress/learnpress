<?php

use LearnPress\Helpers\Template;

/**
 * Class LP_Submenu_Addons
 *
 * @since 3.0.1
 * @version 1.0.1
 */
class LP_Submenu_Addons extends LP_Abstract_Submenu {

	/**
	 * LP_Submenu_Addons constructor.
	 */
	public function __construct() {
		$this->id         = 'learn-press-addons';
		$this->menu_title = __( 'Add-ons', 'learnpress' ) . '<span class="lp-notify has-addon-update"></span>';
		$this->page_title = __( 'LearnPress Add-ons', 'learnpress' );
		$this->priority   = 20;
		$this->callback   = [ $this, 'display' ];

		parent::__construct();
	}

	public function display() {
		ob_start();
		lp_skeleton_animation_html( 20 );
		$html_loading = ob_get_clean();

		$section = apply_filters(
			'learn-press/admin/manager-addons/section',
			[
				'label'      => sprintf(
					'<h1>%s</h1>',
					__( 'LearnPress Add-ons', 'learnpress' )
				),
				'note-theme' => sprintf(
					'<p style="color: rgba(255,0,0,0.76)"><strong><i>%s</i></strong></p>',
					__( '* If you use a Premium Theme that includes LearnPress add-ons, you can go to the <strong>Plugins</strong> tab on Dashboard of theme to download or update them.', 'learnpress' )
				),
				'note-addon' => sprintf(
					'<p>%s</p>',
					sprintf(
						__( 'If you have purchased a premium add-on separately, you can enter your purchase code (%s) to download or update the add-ons here.', 'learnpress' ),
						sprintf(
							'<a href="%s" target="_blank">%s</a>',
							'https://thimpress.com/my-account/',
							__( 'get from your account', 'learnpress' )
						)
					)
				),
				'list'       => sprintf( '<div class="lp-addons-page">%s</div>', $html_loading ),
			]
		);

		echo Template::combine_components( $section );
	}
}

return new LP_Submenu_Addons();
