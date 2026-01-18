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
		
		$order_data =  array(
			'post_status' => sanitize_text_field($_GET['post_status'] ?? 'all'),
			'author_id' => sanitize_text_field($_GET['author'] ?? ''),
			'key_search' => sanitize_text_field($_GET['s'] ?? ''),
			'month' => sanitize_text_field($_GET['m'] ?? 0),
			'paged' => sanitize_text_field($_GET['paged'] ?? 1),
			'orderby' => sanitize_text_field($_GET['orderby'] ?? 'title'),
			'order' => sanitize_text_field($_GET['order'] ?? 'asc'),
		)
		?>
		<div class="alignleft actions">
			<button type="button" class="button export"><?php esc_html_e('Export', 'learnpres'); ?></button>
		</div>
		<?php
	}
}
