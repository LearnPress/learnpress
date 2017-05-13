<?php

global $post;
$post_id = $post->ID;
if ( $q = LP_Question_Factory::get_question( $post_id ) ) {
	$q->admin_interface();
}
