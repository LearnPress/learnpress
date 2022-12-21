<?php

/**
 * Check permissions of posts on REST API.
 *
 * @param [type]  $post_type Post type.
 * @param string  $context Request context.
 * @param integer $object_id Post ID.
 * @return void
 */
function lp_rest_check_post_permissions( $post_type, $context = 'read', $object_id = 0 ) {
	$contexts = array(
		'read'   => 'read_private_posts',
		'create' => 'publish_posts',
		'edit'   => 'edit_post',
		'delete' => 'delete_post',
		'batch'  => 'edit_others_posts',
	);

	if ( 'revision' === $post_type ) {
		$permission = false;
	} else {
		$cap              = $contexts[ $context ];
		$post_type_object = get_post_type_object( $post_type );
		$permission       = current_user_can( $post_type_object->cap->$cap, $object_id );
	}

	return apply_filters( 'lp_rest_check_permissions', $permission, $context, $object_id, $post_type );
}

function lp_jwt_prepare_date_response( $date_gmt, $date = null ) {
	// Use the date if passed.
	if ( isset( $date ) ) {
		return mysql_to_rfc3339( $date );
	}

	// Return null if $date_gmt is empty/zeros.
	if ( '0000-00-00 00:00:00' === $date_gmt ) {
		return null;
	}

	// Return the formatted datetime.
	return mysql_to_rfc3339( $date_gmt );
}
