<?php
if ( !$widget->options ) {
	return;
}
foreach ( $widget->options as $field ) {
	call_user_func( array( RW_Meta_Box::get_class_name( $field ), 'show' ), $field );
}
