<?php
$data = wp_parse_args(
	$args,
	[
		'item_id'    => 0,
		'title'      => 'New Item',
		'type'       => '0',
		'item_order' => 0,
		'item_link'  => '#',
		'preview'    => true,
	]
);

$item_id    = $data['item_id'];
$title      = $data['title'];
$type       = $data['type'];
$item_order = $data['item_order'];
$item_link  = $data['item_link'];
$preview    = $data['preview'];
?>

<li data-item-id="<?php echo esc_attr( $item_id ); ?>" data-item-order="<?php echo esc_attr( $item_order ); ?>" data-item-type="<?php echo esc_attr( $type ); ?>" class="section-item <?php echo esc_attr( $type ); ?>">
	<div class="drag lp-sortable-handle">
		<?php learn_press_admin_view( 'svg-icon' ); ?>
	</div>
	<div class="icon"></div>
	<div class="title"><input type="text" value="<?php echo esc_attr( $title ); ?>"></div>
	<div class="item-actions">
		<div class="actions">
			<div data-content-tip="Enable/Disable Preview" class="action preview-item lp-title-attr-tip ready">
				<a class="lp-btn-icon dashicons <?php $preview ? esc_attr_e( 'dashicons-visibility' ) : esc_attr_e( 'dashicons-hidden' ); ?> "></a>
			</div>
			<div data-content-tip="Edit an item" class="action edit-item lp-title-attr-tip ready">
				<a href="<?php echo esc_attr( $item_link ); ?>" target="_blank" class="lp-btn-icon dashicons dashicons-edit"></a>
			</div>
			<div class="action delete-item">
				<a class="lp-btn-icon dashicons dashicons-trash"></a>
				<ul class="ui-sortable">
					<li>
						<a class="delete-in-course"><?php esc_html_e( 'Remove from the course', 'learnpress' ); ?></a>
					</li>
					<li>
						<a class="delete-permanently"><?php esc_html_e( 'Move to trash', 'learnpress' ); ?></a>
					</li>
				</ul>
			</div>
		</div>
	</div>
</li>