<?php

class LP_Meta_Box extends RW_Meta_Box {
	/**
	 * Get field class name
	 *
	 * @param array $field Field array
	 *
	 * @return bool|string Field class name OR false on failure
	 */
	static function get_class_name( $field ) {
		// Convert underscores to whitespace so ucwords works as expected. Otherwise: plupload_image -> Plupload_image instead of Plupload_Image
		$type = str_replace( '_', ' ', $field['type'] );

		// Uppercase first words
		echo $class = 'LP_Meta_Box_' . ucwords( $type ) . '_Field';
		echo "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX";
		// Relace whitespace with underscores
		$class = str_replace( ' ', '_', $class );
		//echo $class . '-' . ( class_exists( $class ) ? 1 : 0 ) . "<br />\n";
		return class_exists( $class ) ? $class : false;
	}
}