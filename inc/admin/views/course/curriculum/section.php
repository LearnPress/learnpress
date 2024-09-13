<?php
$data = wp_parse_args(
	$args,
	[
		'section_id'    => 0,
		'title'         => 'New Section',
		'count_item'    => '0',
		'section_order' => 0,
		'desc'          => '',
		'items'         => [],
	]
);

$section_id    = $data['section_id'];
$title         = $data['title'];
$count_item    = $data['count_item'];
$section_order = $data['section_order'];
$desc          = $data['desc'];
$items         = $data['items'];
$types         = learn_press_course_get_support_item_types();
?>

<div data-section-order="<?php echo esc_attr( $section_order ); ?>" data-section-id="<?php echo esc_attr( $section_id ); ?>" class="section close">
	<div class="section-head">
		<span class="movable lp-sortable-handle ui-sortable-handle"></span>
		<input type="text" title="title" value="<?php echo esc_attr( $title ); ?>" placeholder="Create a new section" class="title-input">
		<div class="section-item-counts">
			<span>
				<?php echo sprintf( '%s %s', $count_item, esc_html__( 'Items', 'learnpress' ) ); ?>
			</span>
		</div>
		<div class="actions"><span class="collapse close"></span></div>
	</div>
	<div class="section-collapse close">
		<div class="section-content">
			<div class="details">
				<input type="text" title="description" value="<?php echo esc_attr( $desc ); ?>" placeholder="Section description..." class="description-input no-submit">
			</div>
			<div class="section-list-items">
				<ul class="ui-sortable" data-section-id="<?php echo esc_attr( $section_id ); ?>">
					<?php
					foreach ( $items as $key => $item ) {
						$item_id    = $item->id ?? 0;
						$item_order = $item->order ?? 0;
						$type       = $item->type ?? 'lp_lesson';
						$title      = $item->title ?? 'New item';
						$preview    = $item->preview ?? true;
						$item_link  = get_edit_post_link( $item_id ) ?? '#';
						learn_press_admin_view(
							'course/curriculum/section-item',
							[
								'item_id'    => $item_id,
								'title'      => $title,
								'type'       => $type,
								'item_order' => $item_order,
								'item_link'  => $item_link,
								'preview'    => $preview,
							]
						);
					}
					?>
				</ul>
				<div class="new-section-item section-item">
					<div class="drag lp-sortable-handle"></div>
					<div class="types">
						<?php $key = 0; ?>
						<?php foreach ( $types as $value => $title ) : ?>
							<label title="<?php echo esc_attr( $title ); ?>" class="type <?php echo esc_attr( $value ); ?><?php $key === 0 ? esc_attr_e( ' current' ) : ''; ?>">	
								<input type="radio" name="lp-section-item-type" value="<?php echo esc_attr( $value ); ?>">
							</label>
							<?php ++$key; ?>
						<?php endforeach; ?>
					</div>
					<div class="title"><input type="text" placeholder="Create a new lesson"></div>
				</div>
			</div>
		</div>
		<div class="section-actions">
			<button type="button" class="button button-secondary select-items">
				<?php esc_html_e( 'Select items', 'learnpress' ); ?>
			</button>
			<div class="remove">
				<span class="icon"><?php esc_html_e( 'Delete', 'learnpress' ); ?></span>
				<div class="confirm"><?php esc_html_e( 'Are you sure?', 'learnpress' ); ?></div>
			</div>
		</div>
	</div>
</div>