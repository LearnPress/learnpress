<?php
$user = LP_Profile::instance()->get_user();

$lp_profile_data = array(
	'userID' => $user->get_id(),
);
?>
	<ul
		class="<?php echo esc_attr( apply_filters( 'learnpress/profile/course-list/course-container/class', 'learnpress-course-container' ) ); ?>"
	>
		<li class="lp-loading">
		</li>
		<input class="lp_profile_data" type="hidden" name="lp_profile_data"
			   value="<?php echo sanitize_text_field( htmlentities( wp_json_encode( $lp_profile_data ) ) ); ?>">
	</ul>
<?php
