<?php
$attribute_taxonomy = get_term_by( 'slug', $attribute['name'], LP_COURSE_ATTRIBUTE );
?>
<li data-taxonomy="<?php echo $attribute_taxonomy->slug; ?>" class="learn-press-attribute <?php echo $attribute_taxonomy->slug; ?>">
	<h4><?php echo $attribute_taxonomy->name; ?></h4>
	<div class="learn-press-attribute-data">
		<table class="form-table">
			<tr>
				<th>
					<?php _e( 'Name', 'learnpress' ); ?>
				</th>
				<td>
					<?php echo $attribute_taxonomy->name; ?>
				</td>
			</tr>
			<tr>
				<th>
					<?php _e( 'Values', 'learnpress' ); ?>
				</th>
				<td>
					<?php $attribute_values = learn_press_get_attribute_terms( $attribute_taxonomy->term_id ); ?>
					<select class="course-attribute-values" name="course-attribute-values[<?php echo $attribute_taxonomy->term_id; ?>][]" multiple="multiple" style="width: 100%;">
						<?php foreach ( $attribute_values as $value ): ?>
							<option value="<?php echo $value->slug; ?>"<?php selected( has_term( absint( $value->term_id ), LP_COURSE_ATTRIBUTE . '-' . $attribute_taxonomy->slug, $postId ), true ); ?>>
								<?php echo $value->name; ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
		</table>
	</div>
	<?php
	?>
</li>
