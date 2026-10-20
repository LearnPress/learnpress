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

	public function init(): void {}

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
			'wrap'      => '<div class="lp-table-wrap">',
			'table'     => sprintf(
				'<table class="lp-list-table %s">',
				esc_attr( $class_tb )
			),
			'header'    => $this->html_header( $data['header'] ?? [] ),
			'body'      => $this->html_body( $data['body'] ?? [] ),
			'footer'    => $this->html_footer( $data['footer'] ?? '' ),
			'table-end' => '</table>',
			'wrap-end'  => '</div>',
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
			$html_th .= $this->html_header_sort( $item );
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
	 * Render a single sortable/non-sortable column header.
	 *
	 * Mirrors WordPress WP_List_Table column headers, replacing the
	 * default .sorting-indicator pseudo-element icon with lp-icon carets.
	 *
	 * Expected item keys:
	 * - class       : CSS class(es) for the <th>.
	 * - title       : Column title.
	 * - sortable    : Whether the column supports sorting.
	 * - sort_url    : URL to use when the header is clicked (required when sortable).
	 * - sorted      : Whether this column is the currently sorted one.
	 * - sort_order  : Current sort order, 'asc' or 'desc' (default 'asc').
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function html_header_sort( array $item = [] ): string {
		$classes  = array_filter( [ (string) ( $item['class'] ?? '' ) ] );
		$sortable = ! empty( $item['sortable'] );
		$sorted   = $sortable && ! empty( $item['sorted'] );
		$order    = $sorted ? strtolower( (string) ( $item['sort_order'] ?? 'asc' ) ) : '';
		if ( ! in_array( $order, [ 'asc', 'desc' ], true ) ) {
			$order = 'asc';
		}

		if ( $sortable ) {
			$classes[] = $sorted ? 'sorted' : 'sortable';
			if ( $sorted ) {
				$classes[] = $order;
			}
		}

		$title = wp_kses_post( $item['title'] ?? '' );
		$class = implode( ' ', $classes );

		if ( ! $sortable ) {
			return sprintf(
				'<th scope="col" class="%s">%s</th>',
				esc_attr( $class ),
				$title
			);
		}

		$up_active   = $sorted && 'asc' === $order ? ' is-active' : '';
		$down_active = $sorted && 'desc' === $order ? ' is-active' : '';
		$sort_url    = $item['sort_url'] ?? '#';

		return sprintf(
			'<th scope="col" class="%s">
				<a href="%s">
				<span>%s</span>
				<span class="sorting-indicator">
					<i class="lp-icon-caret-up%4$s"></i>
					<i class="lp-icon-caret-down%5$s"></i>
				</span>
				</a>
			</th>',
			esc_attr( $class ),
			esc_url( $sort_url ),
			$title,
			esc_attr( $up_active ),
			esc_attr( $down_active )
		);
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

		// Allow callers to pass fully prepared rows HTML.
		if ( isset( $data['rows_html'] ) && is_string( $data['rows_html'] ) ) {
			$html_tr = $data['rows_html'];
		} else {
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
		}

		$section = [
			'wrapper'     => '<tbody>',
			'trs'         => $html_tr,
			'wrapper_end' => '</tbody>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * Render the HTML table footer.
	 *
	 * @param string $footer_html
	 *
	 * @return string
	 */
	public function html_footer( string $footer_html = '' ): string {
		if ( '' === trim( $footer_html ) ) {
			return '';
		}

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
