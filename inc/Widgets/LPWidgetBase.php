<?php

namespace LearnPress\Widgets;

use LearnPress\MetaBox\LPMetaBoxField;
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
	protected $lp_widget_setting     = [];

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

	public function form( $instance ) {
		if ( empty( $this->lp_widget_setting ) ) {
			echo '<p>' . esc_html_e( 'There are no options for this widget.', 'learnpress' ) . '</p>';
			return;
		}

		foreach ( $this->lp_widget_setting as $key => $setting ) {
			$extra            = $setting;
			$extra['value']   = $instance[ $key ] ?? '';
			$extra['default'] = $setting['std'] ?? '';
			$extra['id']      = $this->get_field_id( $key );

			if ( isset( $setting['type'] ) && LPMetaBoxField::CHECKBOX === $setting['type'] ) {
				$html_wrapper = [
					'<p style="display:flex;flex-direction:row-reverse;justify-content:left;align-items:center">' => '</p>',
					'<label for="' . $extra['id'] . '">' . ( $setting['label'] ?? '' ) . '</label>' => '',
				];
			} else {
				$html_wrapper = [
					'<p style="display:flex;flex-direction:column">' => '</p>',
					'<label for="' . $extra['id'] . '">' . ( $setting['label'] ?? '' ) . '</label>' => '',
				];
			}

			LPMetaBoxField::render( $setting['type'], $this->get_field_name( $key ), $extra, $html_wrapper );
		}
	}
}

