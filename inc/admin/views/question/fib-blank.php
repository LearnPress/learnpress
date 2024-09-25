<?php
$comparisons = [
	[
		'value'       => 'equal',
		'label'       => esc_html( 'Equal', 'learnpress' ),
		'description' => __( 'Match two words are equality.', 'learnpress' ),
	],
	[
		'value'       => 'range',
		'label'       => esc_html( 'Range', 'learnpress' ),
		'description' => __( 'Match any number in a range. Use <code>100, 200</code> to match any value from 100 to 200.', 'learnpress' ),
	],
	[
		'value'       => 'any',
		'label'       => esc_html( 'Any', 'learnpress' ),
		'description' => __( 'Match any value in a set of words. Use <code>fill, blank, or question</code> to match any value in the set.', 'learnpress' ),
	],
];
?>

<tbody class="fib-blank" style="display:none;">
	<tr>
		<td class="blank-position" width="50"></td>
		<td class="blank-fill">
			<input type="text">
		</td>
		<td class="blank-actions">
			<span class="blank-status"></span>
			<a class="option button"><?php esc_html_e( 'Options', 'learnpress' ); ?></a>
			<a class="delete button"><?php esc_html_e( 'Delete', 'learnpress' ); ?></a>
		</td>
	</tr>
	<tr class="blank-options">
		<td width="50"></td>
		<td colspan="2">
			<ul>
				<li>
					<label>
						<input type="checkbox">
						<?php esc_html_e( 'Match case', 'learnpress' ); ?></label>
					<p class="description"><?php esc_html_e( 'Match two words in case sensitive.', 'learnpress' ); ?></p>
				</li>
				<li>
					<h4><?php esc_html_e( 'Comparison', 'learnpress' ); ?></h4>
				</li>
				<?php foreach ( $comparisons as $comparison ) : ?>
					<li>
						<label>
							<input type="radio" value="<?php echo esc_attr( $comparison['value'] ); ?>">
							<?php echo $comparison['label']; ?>
						</label>
						<p class="description">
							<?php echo $comparison['description']; ?>
						</p>
					</li>
				<?php endforeach; ?>
			</ul>
		</td>
	</tr>
</tbody>