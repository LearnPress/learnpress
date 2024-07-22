<?php
/**
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 4.2.4
 */

defined( 'ABSPATH' ) or die();

$data_struct_user = [
	'urlApi'      => get_rest_url( null, 'lp/v1/admin/tools/search-user' ),
	'dataType'    => 'users',
	'keyGetValue' => [
		'value'      => 'ID',
		'text'       => '{{display_name}} ({{ID}} - {{user_email}})',
		'key_render' => [
			'display_name' => 'display_name',
			'user_email'   => 'user_email',
			'ID'           => 'ID',
		],
	],
	'setting'     => [
		'placeholder' => esc_html__( 'Choose User', 'learnpress' ),
		'maxItems'    => null,
	],
];

$data_struct_course = [
	'urlApi'      => get_rest_url( null, 'lp/v1/admin/tools/search-user' ),
	'dataType'    => 'users',
	'keyGetValue' => [
		'value'      => 'ID',
		'text'       => '{{display_name}} ({{ID}} - {{user_email}})',
		'key_render' => [
			'display_name' => 'display_name',
			'user_email'   => 'user_email',
			'ID'           => 'ID',
		],
	],
	'setting'     => [
		'placeholder' => esc_html__( 'Choose Course', 'learnpress' ),
		'maxItems'    => null,
	],
];

$unassgin_course     = new LP_Meta_Box_Select_Field(
	esc_html__( 'Choose Course', 'learnpress' ),
	[],
	'',
	[
		'options'           => array(),
		'tom_select'        => true,
		'custom_attributes' => [ 'data-struct' => htmlentities2( json_encode( $data_struct_course ) ) ],
	],
);
$unassgin_course->id = 'course_ids';


$unassgin_user     = new LP_Meta_Box_Select_Field(
	esc_html__( 'Choose User', 'learnpress' ),
	[],
	'',
	[
		'options'           => array(),
		'tom_select'        => true,
		'custom_attributes' => [ 'data-struct' => htmlentities2( json_encode( $data_struct_user ) ) ],
	]
);
$unassgin_user->id = 'user_ids';

?>
<div id="learn-press-unassigned-course" class="card">
	<h2><?php _e( 'Unassign Course', 'learnpress' ); ?></h2>
	<div class="description">
		<div><?php _e( 'Remove user from a course', 'learnpress' ); ?></div>
		<i style="color: #a20707">
			<?php
			_e(
				'Noted: when remove user from course, the progress of user with course assign will eraser, so be careful before do this.',
				'learnpress'
			);
			?>
		</i>
	</div>
	<div class="content">
		<form id="lp-unassign-user-course-form">
			<ul>
				<li>
					<?php $unassgin_course->output( '' ); ?>
				</li>
				<li>
					<div class="assign-to-user">
						<?php $unassgin_user->output( '' ); ?>
					</div>
				</li>
			</ul>
			<div>
				<button class="button button-primary lp-button-unassign-course" type="submit">
					<?php _e( 'Remove', 'learnpress' ); ?>
				</button>
				<span class="percent" style="margin-left: 10px"></span>
				<span class="message" style="margin-left: 10px"></span>
			</div>
		</form>
	</div>
</div>
