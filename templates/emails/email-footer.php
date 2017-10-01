<?php
/**
 * Template for displaying email footer.
 *
 * @author ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

defined('ABSPATH') or exit();

$email = LP_Emails::get_email('new-order-guest');
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
