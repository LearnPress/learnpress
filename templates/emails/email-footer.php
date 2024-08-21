<?php
/**
 * Template for displaying email footer.
 *
 * @author ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or exit();
?>
						</td>
					</tr>
					</tbody>
					<?php
					if ( ! empty( $footer_text ) ) {
					?>
					<tfoot id="email-footer">
					<tr>
						<td>
							<?php echo wp_kses_post( $footer_text ); ?>
						</td>
					</tr>
					</tfoot>
					<?php
					}
					?>
				</table>
			</td>
		</tr>
	</table>
</div>
