<?php
$attributes = learn_press_get_attributes();
$in         = !empty( $this->instance['filter_by'] ) ? $this->instance['filter_by'] : false;
if ( !$attributes ) {
	return;
}
global $lp_tax_query;
print_r( $lp_tax_query );
?>
<ul class="lp-course-attributes course-filters">
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
			if ( $lp_tax_query ) foreach ( $lp_tax_query as $tax ) {
				if ( $tax['taxonomy'] == LP_COURSE_ATTRIBUTE . '-' . $attribute->slug ) {
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
							<a href=""><?php echo $value->name; ?> <span>(<?php echo $value->count; ?>)</span>
							</a>
						</li>
					<?php } ?>
				</ul>
				<?php
			}
			?>
		</li>
	<?php } ?>
</ul>