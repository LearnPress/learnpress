<?php

/**
 * Color Schema field class.
 *
 * @since 3.0
 */
class RWMB_Color_Schema_Field extends RWMB_Field {

	public static function init() {
		add_action( 'learn-press/update-settings/updated', array( __CLASS__, 'update' ) );
	}

	public static function update() {
		if ( ! empty( $_REQUEST['color_schema'] ) ) {
			update_option( 'learn_press_color_schemas', $_REQUEST['color_schema'] );
		}
		//learn_press_debug( $_REQUEST['color_schema'] );
		//die();
	}

	protected static function get_colors() {
		$colors = array(
			array(
				'title' => __( 'Popup heading background', 'learnpress' ),
				'id'    => 'popup-heading-bg'
			),
			array(
				'title' => __( 'Section heading background', 'learnpress' ),
				'id'    => 'section-heading-bg'
			),
			array(
				'title' => __( 'Lines color', 'learnpress' ),
				'id'    => 'lines-color'
			),
			array(
				'title' => __( 'Section heading color', 'learnpress' ),
				'id'    => 'section-heading-color'
			)
		);

		return $colors;
	}

	/**
	 * HTML
	 *
	 * @param mixed $meta
	 * @param array $field
	 *
	 * @return string
	 */
	public static function html( $meta, $field = array() ) {
		ob_start();
		$colors = self::get_colors();

		$schemas = get_option( 'learn_press_color_schemas' );

		if ( ! $schemas ) {
			$schemas = array( $colors );
		} else {
			foreach ( $schemas as $k => $schema ) {
				$schemas[ $k ] = $colors;
				foreach ( $colors as $m => $options ) {
					if ( array_key_exists( $options['id'], $schema ) ) {
						$schemas[ $k ][ $m ]['std'] = $schema[ $options['id'] ];
					}
				}
			}
		}

		$schemas = array_values($schemas);

		?>
        <div id="color-schemas">
			<?php foreach ( $schemas as $k => $schema ) { ?>
                <div class="color-schemas<?php echo $k == 0 ? ' current' : ''; ?>">
                    <table>
                        <tbody>
						<?php foreach ( $schema as $option ) {
							$name = 'color_schema[' . $k . '][' . $option['id'] . ']';
							$std  = ! empty( $option['std'] ) ? $option['std'] : '';
							?>
                            <tr>
                                <th><?php echo $option['title']; ?></th>

                                <td class="color-selector">
                                    <input name="<?php echo $name; ?>" value="<?php echo $std; ?>">
                                </td>
                            </tr>
						<?php } ?>
                        </tbody>
                    </table>
                    <p>
                        <button class="button clone-schema"
                                type="button"><?php _e( 'Save as new', 'learnpress' ); ?></button>
                        <a class="apply-schema" href=""><?php _e( 'Use this colors', 'learnpress' ); ?></a>
                        <a class="remove-schema" href=""><?php _e( 'Delete', 'learnpress' ); ?></a>
                    </p>
                </div>
			<?php } ?>
        </div>
        <script type="text/javascript">
            jQuery(function ($) {

                var $btn = $('.color-schemas').on('click', '.clone-schema', function () {
                    var $src = $(this).closest('.color-schemas'),
                        $dst = $src.clone().find('.clone-schema').remove().end().insertAfter($src).removeClass('current'),
                        $colorPickers = $dst.find('.wp-picker-container');
                    $colorPickers.each(function () {
                        var $colorPicker = $(this),
                            $input = $colorPicker.find('.wp-color-picker');

                        $input.insertAfter($colorPicker);
                        $colorPicker.remove();
                        $input.wpColorPicker();
                    })

                });

                $($btn[0].form).on('submit', function () {
                    $('.color-schemas').each(function (i, el) {
                        $(el).find('input').each(function () {
                            var $input = $(this),
                                name = $input.attr('name') || '';
                            name = name.replace(new RegExp('color_schema\[[0-9]+\]'), 'color_schema[' + i + ']');
                            $input.attr('name', name);
                        })
                    });
                });

                $(document).on('click', '.remove-schema', function (e) {
                    e.preventDefault();
                    $(this).closest('.color-schemas').remove();
                }).on('click', '.apply-schema', function (e) {
                    e.preventDefault();
                    var $current = $('.color-schemas.current:first'),
                        $btn = $(this),
                        $new = $btn.closest('.color-schemas');

                    //$btn.closest('.color-schemas').insertAfter($current);

                    $current.insertAfter($new).removeClass('current');
                    $current.parent().prepend($new.addClass('current'));
                });

                $('#color-schemas').find('.color-selector input').wpColorPicker();
            })
        </script>
		<?php
		return ob_get_clean();
	}
}

RWMB_Color_Schema_Field::init();