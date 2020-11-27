<?php
/**
 * Template for displaying email footer.
 *
 * @author ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 */

defined('ABSPATH') or exit();

if ( ! $email = LP_Emails::instance()->get_current() ) {
	return;
}
?>
						</td>
					</tr>
                    </tbody>
                    <tfoot id="email-footer">
					<tr>
						<td>
                            <?php echo $email->get_footer_text(); ?>
						</td>
					</tr>
                    </tfoot>
				</table>
			</td>
		</tr>
	</table>
</div>
