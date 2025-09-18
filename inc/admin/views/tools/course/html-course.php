<?php
/**
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 4.2.4
 */

defined( 'ABSPATH' ) or die();

$data_list_courses = array(
	'urlApi'      => get_rest_url( null, 'lp/v1/admin/tools/get-courses-has-user' ),
	'dataType'    => 'courses',
	'keyGetValue' => array(
		'value'      => 'ID',
		'text'       => '[#{{ID}}] {{post_title}}',
		'key_render' => array(
			'post_title' => 'post_title',
			'ID'         => 'ID',
		),
	),
	'setting'     => array(
		'placeholder' => esc_html__( 'Choose Course', 'learnpress' ),
	),
);

$list_courses     = new LP_Meta_Box_Select_Field(
	esc_html__( 'Choose Course', 'learnpress' ),
	array(),
	'',
	array(
		'options'           => array(),
		'tom_select'        => true,
		'multiple'          => true,
		'name_no_bracket'   => true,
		'custom_attributes' => array( 'data-struct' => htmlentities2( json_encode( $data_list_courses ) ) ),
	)
);
$list_courses->id = 'course_ids';

?>
<div id="learn-press-reset-course-progress" class="card">
	<h2><?php _e( 'Reset Course Progress', 'learnpress' ); ?></h2>
	<p><?php _e( 'This action will reset course progress of all users who have enrolled.', 'learnpress' ); ?></p>
	<p><?php _e( 'Search results only show if courses have user data.', 'learnpress' ); ?></p>
	<div class="content">
		<form id="lp-reset-course-progress-form" method="post" action="">
			<ul>
				<li>
					<?php $list_courses->output( 0 ); ?>
				</li>
			</ul>
			<div>
				<button class="button button-primary lp-button-reset-user-progress" type="submit">
					<?php _e( 'Reset Progress', 'learnpress' ); ?>
				</button>
				<span class="percent" style="margin-left: 10px"></span>
				<span class="message" style="margin-left: 10px"></span>
			</div>
		</form>
	</div>
</div>
