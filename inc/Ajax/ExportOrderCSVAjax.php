<?php

namespace LearnPress\Ajax;

use Exception;
use LearnPress\Models\UserModel;
use LP_Helper;
use LP_Request;
use LP_REST_Response;
use Throwable;
use WP_Query;

class ExportOrderCSVAjax extends AbstractAjax
{
	public function export_order_csv()
	{
		$response = new LP_REST_Response();
		try {
			// Check permission
			if (!current_user_can(UserModel::ROLE_ADMINISTRATOR)
				&& !current_user_can(UserModel::ROLE_INSTRUCTOR)) {
				throw new Exception(__('You do not have permission to perform this action.', 'learnpress'));
			}
			$data_str = LP_Request::get_param('data');
			$params = LP_Helper::json_decode($data_str, true);
			$export_id = sanitize_key($params['export_id'] ?? '');
			if (!$export_id) {
				throw new Exception(__('Missing export id', 'learnpress'));
			}

			$paged = $params['paged'] ?? 1;
			$posts_per_page = $params['posts_per_page'] ?? 20;

			$args = [
				'post_type' => $params['post_type'] ?? LP_ORDER_CPT,
				'post_status' => $params['post_status'] ?? '',
				's' => $params['s'] ?? '',
				'm' => $params['m'] ?? 0,
				'order' => $params['order'] ?? '',
				'orderby' => $params['orderby'] ?? '',
				'posts_per_page' => $posts_per_page,
				'paged' => $paged,
			];

			if (!empty($params['author'])) {
				$args['author'] = $params['author'];
			}



			$query = new WP_Query($args);

			if ($query->have_posts()) {
				$file = $this->get_export_csv_path($export_id);
				var_dump($file);die;
				$handle = fopen($file, $paged === 1 ? 'w' : 'a');

				if ($paged === 1) {
					fprintf($handle, "\xEF\xBB\xBF");
					fputcsv($handle, [
						'Order',
						'Student',
						'Purchased',
						'Date',
						'Total',
						'Status',
					]);
				}

				while ($query->have_posts()) {
					$query->the_post();

					$order_id = get_the_ID();
					$order = learn_press_get_order($order_id);
					if (!$order) {
						continue;
					}
					$user = $order->get_user();
					$student = $user ? $user->display_name : __('Guest', 'learnpress');

					$items = $order->get_items();
					$courses = [];

					if (!empty($items)) {
						foreach ($items as $item) {
							if (!empty($item['course_id'])) {
								$courses[] = get_the_title($item['course_id']);
							}
						}
					}

					fputcsv($handle, [
						$order->get_order_key(),
						$student,
						implode(' | ', $courses),
						get_post_field('post_date', $order_id),
						$order->get_total(),
						$order->get_status(),
					]);
				}

				fclose($handle);
				wp_reset_postdata();

				if ($paged === 1) {
//					$this->update_migrate_option();
				}
			}

			$data = [];
			$data['max_page'] = $query->max_num_pages;
			$data['post_count'] = $query->post_count;

			if ($query->post_count === $posts_per_page) {
				$data['next_page'] = $paged + 1;
				$response->message = esc_html__('Orders exported!', 'learnpress');
			}

			if ($paged >= $query->max_num_pages) {
				$data['download_url'] = $this->get_export_csv_path($export_id);
				$data['done'] = true;
				$response->message = esc_html__('All orders exported!', 'learnpress');
			}

			$response->data = $data;
			$response->status = 'success';

		} catch (Throwable $e) {
			$response->message = $e->getMessage();
		}

		wp_send_json($response);
	}

	private function get_export_csv_path($export_id)
	{
		$upload = wp_upload_dir();
		$dir = $upload['basedir'] . '/lp-export';

		if (!file_exists($dir)) {
			wp_mkdir_p($dir);
		}

		return $dir . "/orders-{$export_id}.csv";
	}
}
