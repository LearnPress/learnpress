<?php

namespace LearnPress\TemplateHooks\Order;

use LearnPress\Helpers\Singleton;

/**
 * class AdminOrderListTemplate
 *
 * @since 4.3.2
 * @version 1.0.0
 */
class AdminOrderListTemplate
{
	use Singleton;

	public function init()
	{
		add_action('manage_posts_extra_tablenav', array($this, 'add_export_order_button'));
	}

	public function add_export_order_button($which)
	{
		global $pagenow;

		if ($pagenow !== 'edit.php' || !isset($_GET['post_type']) || $_GET['post_type'] !== LP_ORDER_CPT
		) {
			return;
		}
		?>
		<div class="alignleft actions">
			<button type="button" class="button export"><?php esc_html_e('Export', 'learnpres'); ?></button>
		</div>
		<?php
	}
}
