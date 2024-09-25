<?php
/**
 * Admin Question item: Editor template.
 *
 * @since 4.2.7
 * @author VuxMinhThanh
 */

$data = wp_parse_args(
	$args,
	[
		'id'    => '',
		'title' => 'Question',
		'order' => 1,
	]
);

$id        = $data['id'];
$title     = $data['title'];
$order     = $data['order'];
$edit_link = get_edit_post_link( $id );
?>

<div data-question-id="<?php echo esc_attr( $id ); ?>" data-question-order="<?php echo esc_attr( $index ); ?>" class="question-item multi_choice">
	<div class="question-actions table-row">
		<div class="sort lp-sortable-handle ui-sortable-handle">
			<?php learn_press_admin_view( 'svg-icon' ); ?>
		</div>
		<div class="order"><?php echo esc_attr( $order ); ?></div>
		<div class="name"><input type="text" class="question-title" value="<?php echo esc_attr( $title ); ?>"></div>
		<div class="actions">
			<div class="lp-box-data-actions lp-toolbar-buttons">
				<div data-content-tip="Duplicate" class="lp-toolbar-btn lp-title-attr-tip ready lp-btn-duplicate"><a href="" class="lp-btn-icon dashicons dashicons-admin-page"></a></div>
				<div data-content-tip="Edit an item" class="lp-toolbar-btn lp-title-attr-tip ready"><a href="<?php echo esc_attr( $edit_link ); ?>" target="_blank" class="lp-btn-icon dashicons dashicons-edit"></a></div>
				<div class="lp-toolbar-btn lp-btn-remove lp-toolbar-btn-dropdown"><a class="lp-btn-icon dashicons dashicons-trash"></a>
					<ul>
					
						<li><a class="remove"><?php esc_html_e( 'Removed from the quiz', 'learnpress' ); ?></a></li>
						<li><a class="delete" data-confirmed="<?php esc_html_e( 'Do you want to move the question to the trash?', 'learnpress' ); ?>"><?php esc_html_e( 'Move to trash', 'learnpress' ); ?></a></li>
					</ul>
				</div>
				<span class="lp-toolbar-btn lp-btn-toggle close"></span>
			</div>
		</div>
	</div>
	<div class="question-settings hide-if-js">
		<?php
		learn_press_admin_view(
			'question/editor-refactor',
			[
				'id' => $id,
			]
		);
		?>
	</div>
</div>