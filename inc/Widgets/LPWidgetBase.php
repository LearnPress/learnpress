<?php

namespace LearnPress\Widgets;

use WP_Widget;

/**
 * Class AbstractWidget
 *
 * @package LearnPress\Widgets
 * @since 4.2.3.2
 * @version 1.0.0
 */
class LPWidgetBase extends WP_Widget {
	protected $prefix                = 'learnpress_';
	protected $lp_widget_id          = '';
	protected $lp_widget_name        = '';
	protected $lp_widget_description = '';
	protected $lp_widget_class       = '';
	protected $lp_widget_options     = [];

	public function __construct() {
		$id_base         = $this->prefix . $this->lp_widget_id;
		$name            = $this->lp_widget_name;
		$widget_options  = array_merge(
			[
				'description'                 => $this->lp_widget_description,
				'classname'                   => $this->lp_widget_class,
				'customize_selective_refresh' => true,
			],
			$this->lp_widget_options
		);
		$control_options = $this->control_options;
		parent::__construct( $id_base, $name, $widget_options, $control_options );
	}
}

