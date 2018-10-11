<?php
$attributes = learn_press_get_attributes();

$in = !empty( $this->instance['filter_by'] ) ? $this->instance['filter_by'] : false;
if ( !$attributes ) {
	return;
}
global $lp_tax_query;

$attribute_ids    = wp_list_pluck( $attributes, 'term_id' );
$attribute_values = learn_press_get_attribute_terms( $attribute_ids );
$taxonomies       = wp_list_pluck( $attributes, 'slug' );
foreach ( $taxonomies as $k => $v ) {
	$taxonomies[$k] = LP_COURSE_ATTRIBUTE . '-' . $v;
}
$term_counts  = $this->get_filtered_term_course_counts( wp_list_pluck( $attribute_values, 'term_id' ), $taxonomies, $this->instance['value_operator'] );
$has_filtered = false;
?>
<ul class="lp-course-attributes course-filters" data-ajax="<?php echo !empty( $this->instance['ajax_filter'] ) ? 'yes' : 'no'; ?>">
	<?php foreach ( $attributes as $attribute ) {
		if ( !( false === $in || in_array( LP_COURSE_ATTRIBUTE . '-' . $attribute->slug, $in ) ) ) {
			continue;
		}
		$values = learn_press_get_attribute_terms( $attribute->term_id );
		if ( !$values ) {
			continue;
		}
		$has_attribute_values = false;
		ob_start();
		?>
		<li data-attribute="filter_<?php echo $attribute->slug; ?>">
			<h4 class="lp-course-attribute-name"><?php echo $attribute->name; ?></h4>
			<?php
			//$term_counts = $this->get_filtered_term_course_counts( wp_list_pluck( $values, 'term_id' ), LP_COURSE_ATTRIBUTE . '-' . $attribute->slug, $this->instance['value_operator'] );
			$tax = false;
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
			?>
			<ul class="lp-course-attribute-values">
				<?php foreach ( $values as $value ) {
					if ( !$value->count ) {
						continue;
					}
					$count = isset( $term_counts[$value->term_id] ) ? $term_counts[$value->term_id] : 0;
					if ( !$count ) {
						continue;
					}
					$classes = array( 'lp-course-attribute-value' );
					if ( $tax && in_array( $value->slug, $tax['terms'] ) ) {
						$classes[]    = "active";
						$has_filtered = true;
					}
					$has_attribute_values = true;
					?>
					<li class="<?php echo join( ' ', $classes ); ?>" data-value="<?php echo $value->slug; ?>">
						<a href=""><?php echo $value->name; ?></a>
						<span>(<?php echo $count; ?>)</span>
					</li>
				<?php } ?>
			</ul>

		</li>
		<?php
		$output = ob_get_clean();
		if ( $has_attribute_values ) {
			echo $output;
		}
		?>
	<?php } ?>
</ul>
<input type="hidden" name="attribute_operation" value="<?php echo empty( $this->instance['attribute_operation'] ) || strtolower( $this->instance['attribute_operation'] ) == 'and' ? 'and' : 'or'; ?>">
<input type="hidden" name="value_operation" value="<?php echo empty( $this->instance['value_operation'] ) || strtolower( $this->instance['value_operation'] ) == 'and' ? 'and' : 'or'; ?>">
<?php if ( !empty( $this->instance['button_filter'] ) ) { ?>
	<button type="button" class="lp-button-filter" disabled="disabled"><?php _e( 'Filter', 'learnpress' ); ?></button>
<?php } ?>
<button type="button" class="lp-button-reset-filter" <?php echo $has_filtered ? '' : 'disabled="disabled"'; ?>"><?php _e( 'Reset', 'learnpress' ); ?></button>
<script type="text/javascript">
	jQuery(document).ready(function ($) {
		$('#<?php echo $this->args['widget_id'];?>').courseFilters(<?php echo wp_json_encode( $this->instance );?>);
	});
</script>
