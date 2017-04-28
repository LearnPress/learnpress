<?php
$attribute_taxonomy = get_term_by( 'slug', $attribute['name'], LP_COURSE_ATTRIBUTE );
?>
<li data-taxonomy="<?php echo $attribute_taxonomy->slug; ?>" class="learn-press-attribute learn-press-toggle-box <?php echo $attribute_taxonomy->slug; ?>">
	<h4 class="learn-press-toggle-box-header">
		<span><?php echo $attribute_taxonomy->name; ?></span>
		<div class="learn-press-toggle-box-tools lp-section-actions lp-button-actions">
            <a href="" data-action="remove" class="dashicons dashicons-trash learn-press-remove-attribute" data-confirm-remove="<?php _e( 'Are you sure you want to remove this section?', 'learnpress' ); ?>"></a>
            <a href="" class="move ui-sortable-handle dashicons dashicons-menu"></a>
        </div>
	</h4>
	<div class="learn-press-toggle-box-content learn-press-attribute-data">
		<label><?php _e( 'Values', 'learnpress' ); ?></label>

		<?php $attribute_values = learn_press_get_attribute_terms( $attribute_taxonomy->term_id ); ?>
		<select class="course-attribute-values" name="course-attribute-values[<?php echo $attribute_taxonomy->term_id; ?>][]" multiple="multiple" style="width: 100%;">
			<?php foreach ( $attribute_values as $value ): ?>
				<option value="<?php echo $value->slug; ?>"<?php selected( has_term( absint( $value->term_id ), LP_COURSE_ATTRIBUTE . '-' . $attribute_taxonomy->slug, $postId ), true ); ?>>
					<?php echo $value->name; ?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>
	<?php
	?>
</li>
