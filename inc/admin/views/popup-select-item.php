<?php
/**
 * Admin Select Item: Editor template.
 *
 * @since 4.2.7
 * @author VuxMinhThanh
 */

$data = wp_parse_args(
	$args,
	[
		'types' => [],
	]
);

$types = $data['types'];

?>
<div id="lp-modal-choose-items-refactor" class="">
	<div class="lp-choose-items">
		<div class="header">
			<div class="preview-title">
				<span class="show">
					<?php esc_html_e( 'Selected items', 'learnpress' ); ?>
				</span>
				<div class="total-selected"></div>
			</div>
			<ul class="tabs">
				<?php $key = 0; ?>
				<?php foreach ( $types as $value => $title ) : ?>
					<li data-type="<?php echo esc_attr( $value ); ?>" class="tab <?php $key === 0 ? esc_attr_e( 'active' ) : esc_attr_e( 'inactive' ); ?>"><a href="#"><?php echo esc_html( $title ); ?></a></li>
					<?php ++$key; ?>
				<?php endforeach; ?>
			</ul> <div class="close"><span title="Close" class="dashicons dashicons-no-alt"></span></div>
		</div>
		<div class="main">
			<form class="search"><input type="text" placeholder="Type here to search for an item" title="search" class="modal-search-input"></form>
			<ul class="list-items">
			</ul>
			<div class="pagination" style="display: none;">
				<form>
					<button class="button first">«</button>
					<button class="button previous"><?php esc_html_e( 'Previous', 'learnpress' ); ?></button>
					<button class="button next"><?php esc_html_e( 'Next', 'learnpress' ); ?></button>
					<button class="button last">»</button>
					<span class="index"></span>
				</form>
			</div>
			<div class="lp-added-items-preview">
				<ul class="list-added-items">
				</ul>
			</div>
		</div>
		<div class="footer">
			<div class="cart">
				<button class="button button-primary checkout" disabled>
					<span><?php esc_html_e( 'Add' ); ?></span>
				</button>
				<button class="button button-secondary edit-selected" disabled>
					<span class="show">
						<?php esc_html_e( 'Selected items', 'learnpress' ); ?>
					</span>
					<span class="back" style="display: none;">
						<?php esc_html_e( 'Back', 'learnpress' ); ?>
					</span>
					<span class="total-selected"></span>
				</button>
			</div>
		</div>
	</div>
</div>