<?php
$data = wp_parse_args(
	$args,
	[
		'id' => get_the_ID() ?? '',
	]
);

$question_id = $data['id'];
if ( ! class_exists( 'LP_Meta_Box_Editor_Field' ) ) {
	include_once LP_PLUGIN_PATH . 'inc/admin/views/meta-boxes/fields/editor.php';
}

$description = new LP_Meta_Box_Editor_Field(
	esc_html__( 'Description', 'learnpress' )
);

?>

<div class="quiz-question-options js-question-options" data-question-id="<?php echo esc_attr( $question_id ); ?>">
	<div class="lp-place-holder">
		<div class="line-heading"></div>
	</div>
	<div class="postbox closed" style="display:none;">
		<h2 class="lp-box-data-head lp-row quiz-question-options__header">
			<span><?php esc_html_e( 'Question Settings', 'learnpress' ); ?></span>
			<div class="status success"></div>
		</h2>
		<a class="toggle"></a>
		<div class="inside">
			<div class="lp-quiz-editor__detail lp-meta-box__inner lp-meta-box lp-meta-box--question">
				<?php
				$description->id = '_lp_description_' . $question_id;
				$description->output( $question_id );

				$metabox = new LP_Meta_Box_Question();
				foreach ( $metabox->metabox( $question_id ) as $meta_key => $object ) {
					if ( is_a( $object, 'LP_Meta_Box_Field' ) ) {
						$object->id = $meta_key . '_' . $question_id;
						$object->output( $question_id );
					}
				}
				?>
			</div>
		</div>
	</div>
</div>