<?php
$attributes = learn_press_get_attributes();
$in         = !empty( $this->instance['filter_by'] ) ? $this->instance['filter_by'] : false;
if ( !$attributes ) {
	return;
}
global $lp_tax_query;
?>
<ul class="lp-course-attributes course-filters" data-ajax="<?php echo !empty( $this->instance['ajax_filter'] ) ? 'yes' : 'no'; ?>">
	<?php foreach ( $attributes as $attribute ) {
		if ( !( false === $in || in_array( LP_COURSE_ATTRIBUTE . '-' . $attribute->slug, $in ) ) ) {
			continue;
		}
		?>
		<li data-attribute="filter_<?php echo $attribute->slug; ?>">
			<h4><?php echo $attribute->name; ?></h4>
			<?php
			$values = learn_press_get_attribute_terms( $attribute->term_id );
			$tax    = false;
			if ( $lp_tax_query )
				foreach ( $lp_tax_query as $k => $_tax ) {
					$tax = false;
					if ( $k === 'relation' ) {
						continue;
					}
					if ( $_tax['taxonomy'] == LP_COURSE_ATTRIBUTE . '-' . $attribute->slug ) {
						$tax = $_tax;
						break;
					}
				}
			if ( $values ) {
				?>
				<ul class="lp-course-attribute-values">
					<?php foreach ( $values as $value ) {
						if ( !$value->count ) {
							continue;
						}
						$classes = array();
						if ( $tax && in_array( $value->slug, $tax['terms'] ) ) {
							$classes[] = "active";
						}
						?>
						<li class="<?php echo join( ' ', $classes ); ?>" data-value="<?php echo $value->slug; ?>">
							<a href=""><?php echo $value->name; ?></a>
							<span>(<?php echo $value->count; ?>)</span>
						</li>
					<?php } ?>
				</ul>
				<?php
			}
			?>
		</li>
	<?php } ?>
</ul>
<input type="hidden" name="attribute_operation" value="<?php echo empty( $this->instance['attribute_operation'] ) || strtolower( $this->instance['attribute_operation'] ) == 'and' ? 'and' : 'or'; ?>">
<input type="hidden" name="value_operation" value="<?php echo empty( $this->instance['value_operation'] ) || strtolower( $this->instance['value_operation'] ) == 'and' ? 'and' : 'or'; ?>">

<?php if ( !empty( $this->instance['button_filter'] ) ) { ?>
	<button class="lp-button-filter"><?php _e( 'Filter', 'learnpress' ); ?></button>
<?php } ?>
<script type="text/javascript">
	jQuery(document).ready(function ($) {
		$('.widget_lp-widget-course-filters').courseFilters(<?php echo wp_json_encode( $this->instance );?>);
	});
</script>
