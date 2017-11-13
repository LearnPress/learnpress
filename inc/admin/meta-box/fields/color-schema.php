<?php

/**
 * Color Schema field class.
 *
 * @since 3.0
 */
class RWMB_Color_Schema_Field extends RWMB_Field {
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
		$colors  = array(
			array(
				'title'    => __( 'Section title', 'learnpress' ),
				'selector' => '.section-title',
				'props'    => array(
					'background' => '#FF0000'
				)
			),
			array(
				'title'    => __( 'Final quiz label', 'learnpress' ),
				'selector' => '.course-curriculum, .course-meta',
				'props'    => array(
					'background' => '#000'
				)
			)
		);
		$schemas = get_option( 'learn_press_color_schemas' );
		if ( ! $schemas ) {
			$schemas = array( $colors );
		} else {
			foreach ( $schemas as $k => $schema ) {
				$schemas[ $k ] = $colors;
				foreach ( $colors as $m => $options ) {
					$key = $options['selector'];
					if ( ! empty( $schema[ $key ] ) ) {
						$schemas[ $k ][ $m ]['props'] = $schema[ $key ];
					}
				}
			}
		}
		?>
        <div id="color-schemas">
			<?php foreach ( $schemas as $k => $schema ) { ?>
                <table class="color-schemas<?php echo $k == 0 ? ' current' : ''; ?>">
                    <tbody>
					<?php foreach ( $schema as $option ) {
						$name = 'color_schema[' . $k . '][' . $option['selector'] . ']';
						?>
                        <tr>
                            <th><?php echo $option['title']; ?></th>
							<?php foreach ( $option['props'] as $prop => $value ) {
								if ( false === strpos( $name, '[' . $prop . ']' ) ) {
									$name .= '[' . $prop . ']';
									?>
                                    <td class="color-selector"><input name="<?php echo $name; ?>"
                                                                      value="<?php echo $value; ?>"></td>
								<?php } ?>
							<?php } ?>
                        </tr>
					<?php } ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="2">
							<?php if ( $k == 0 ) { ?>
                                <button class="button clone-schema"
                                        type="button"><?php _e( 'Save as new', 'learnpress' ); ?></button>
							<?php } ?>
                            <a class="apply-schema" href=""><?php _e( 'Use this colors', 'learnpress' ); ?></a>
                            <a class="remove-schema" href=""><?php _e( 'Delete', 'learnpress' ); ?></a>
                        </td>
                    </tr>
                    </tfoot>
                </table>
			<?php } ?>
        </div>
        <script type="text/javascript">
            jQuery(function ($) {
                var $btn = $('.clone-schema').on('click', function () {
                    var $src = $(this).closest('table'),
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
                    $(this).closest('table').remove();
                }).on('click', '.apply-schema', function (e) {
                    e.preventDefault();
                    var $current = $('.color-schemas.current:first').find('tbody'),
                        $btn = $(this);

                    $btn.closest('table').find('tbody').insertAfter($current);

                    $current.appendTo($btn.closest('table'));
                });

                $('#color-schemas').find('.color-selector input').wpColorPicker();
            })
        </script>
		<?php
		return ob_get_clean();
	}
}