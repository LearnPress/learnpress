<?php
/**
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @since 4.2.5.6
 * @version 1.0.1
 */

$data_struct_course = [
	'urlApi'      => get_rest_url( null, 'lp/v1/admin/tools/search-course' ),
	'dataType'    => 'courses',
	'keyGetValue' => [
		'value'      => 'ID',
		'text'       => '{{post_title}} (#{{ID}})',
		'key_render' => [
			'post_title' => 'post_title',
			'ID'         => 'ID',
		],
	],
	'setting'     => [
		'placeholder' => esc_html__( 'Choose Course', 'learnpress' ),
	],
];
$assign_course      = new LP_Meta_Box_Select_Field(
	esc_html__( 'Choose Course', 'learnpress' ),
	[],
	'',
	[
		'options'           => array(),
		'tom_select'        => true,
		'multiple'          => true,
		'name_no_bracket'   => true,
		'custom_attributes' => [ 'data-struct' => htmlentities2( json_encode( $data_struct_course ) ) ],
	]
);
$assign_course->id  = 'course_ids';

$data_struct_user = [
	'urlApi'      => get_rest_url( null, 'lp/v1/admin/tools/search-user' ),
	'dataType'    => 'users',
	'keyGetValue' => [
		'value'      => 'ID',
		'text'       => '{{display_name}}(#{{ID}}) - {{user_email}}',
		'key_render' => [
			'display_name' => 'display_name',
			'user_email'   => 'user_email',
			'ID'           => 'ID',
		],
	],
	'setting'     => [
		'placeholder' => esc_html__( 'Choose User', 'learnpress' ),
	],
];
$assign_user      = new LP_Meta_Box_Select_Field(
	esc_html__( 'Chose User', 'learnpress' ),
	[],
	'',
	[
		'options'           => array(),
		'tom_select'        => true,
		'multiple'          => true,
		'name_no_bracket'   => true,
		'custom_attributes' => [ 'data-struct' => htmlentities2( json_encode( $data_struct_user ) ) ],
	]
);
$assign_user->id  = 'user_ids';
?>
<div id="learn-press-assign-course" class="card">
	<h2><?php _e( 'Assign Course', 'learnpress' ); ?></h2>
	<div class="description">
		<div><?php _e( 'User can enroll in a specific course by manually assign to them.', 'learnpress' ); ?></div>
		<i style="color: #a20707">
			<?php _e( 'Noted: when assign user to course, the progress old of user with course assign will eraser, so be careful before do this.', 'learnpress' ); ?>
		</i>
	</div>
	<div class="content">
		<form id="lp-assign-user-course-form" name="" method="post">
			<fieldset class="lp-assign-course__options">
				<ul>
					<li>
						<?php $assign_course->output( 0 ); ?>
					</li>
					<li>
						<div class="assign-to-user">
							<?php $assign_user->output( 0 ); ?>
						</div>
					</li>
				</ul>
			</fieldset>
			<div>
				<button class="button button-primary lp-button-assign-course" type="submit">
					<?php _e( 'Assign', 'learnpress' ); ?>
				</button>
				<span class="percent" style="margin-left: 10px"></span>
				<span class="message" style="margin-left: 10px"></span>
			</div>
		</form>
	</div>
</div>
