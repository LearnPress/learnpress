<?php
/**
 * Class ProfileQuizzesTemplate.
 *
 * @since 4.2.8.2
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\Table;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;


class TableListTemplate {
	use Singleton;

	public function init() {
	}

	/**
	 * Render the HTML table.
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public function html_table( array $data = [] ): string {
		$class_tb = $data['class_table'] ?? '';
		$section  = [
			'wrapper'     => sprintf(
				'<table class="lp-list-table %s">',
				esc_attr( $class_tb )
			),
			'header'      => $this->html_header( $data['header'] ?? [] ),
			'body'        => $this->html_body( $data['body'] ?? [] ),
			'footer'      => $this->html_footer( $data['footer'] ?? [] ),
			'wrapper_end' => '</table>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * Render the HTML table header.
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public function html_header( array $data = [] ): string {
		$html_th = '';

		foreach ( $data as $item ) {
			$html_th .= sprintf(
				'<th class="%s">%s</th>',
				esc_attr( $item['class'] ?? '' ),
				wp_kses_post( $item['title'] ?? '' )
			);
		}

		$section = [
			'wrapper'     => '<thead>',
			'tr'          => '<tr>',
			'ths'         => $html_th,
			'tr_end'      => '</tr>',
			'wrapper_end' => '</thead>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * Render the HTML table body.
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public function html_body( array $data = [] ): string {
		$html_tr = '';
		foreach ( $data as $item_tr ) {
			$html_tr .= '<tr>';
			foreach ( $item_tr as $item_td ) {
				$html_td  = sprintf(
					'<td>%s</td>',
					wp_kses_post( $item_td )
				);
				$html_tr .= $html_td;
			}
			$html_tr .= '</tr>';
		}

		$section = [
			'wrapper'     => '<tbody>',
			'trs'         => $html_tr,
			'wrapper_end' => '</tbody>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * Reder the HTML table footer.
	 *
	 * @param string $footer_html
	 *
	 * @return string
	 */
	public function html_footer( string $footer_html = '' ): string {
		$html_td = sprintf(
			'<td colspan="10">%s</td>',
			wp_kses_post( $footer_html )
		);

		$section = [
			'wrapper'     => '<tfoot>',
			'tr'          => '<tr>',
			'tds'         => $html_td,
			'tr_end'      => '</tr>',
			'wrapper_end' => '</tfoot>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * Render the HTML info page query.
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public function html_page_result( array $data = [] ): string {
		$total_rows = $data['total_rows'] ?? 0;
		$paged      = $data['paged'] ?? 1;
		$per_page   = $data['per_page'] ?? 1;
		$item_name  = $data['item_name'] ?? _n( 'item', 'items', $total_rows, 'learnpress' );

		$from = ( $paged - 1 ) * $per_page + 1;
		$to   = $from + $per_page - 1;
		$to   = min( $to, $total_rows );
		if ( $total_rows < 1 ) {
			$from = 0;
		}

		$format = __( 'Displaying {{from}} to {{to}} of {{total}} {{item_name}}.', 'learnpress' );
		$format = apply_filters(
			'learnpress/table/list-page-result',
			$format,
			$data
		);

		$output = str_replace(
			array( '{{from}}', '{{to}}', '{{total}}', '{{item_name}}' ),
			array(
				$from,
				$to,
				$total_rows,
				$item_name,
			),
			$format
		);

		return wp_kses_post( $output );
	}
}
