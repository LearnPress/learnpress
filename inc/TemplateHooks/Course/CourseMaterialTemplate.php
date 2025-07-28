<?php
/**
 * Template hooks Archive Package.
 *
 * @since 1.0.0
 * @version 1.0.0
 */
namespace LearnPress\TemplateHooks\Course;

use LearnPress\Helpers\Template;
use LearnPress\TemplateHooks\TemplateAJAX;
use LP_Material_Files_DB;
use stdClass;
use LP_Global;
use LP_WP_Filesystem;
use LP_Settings;
use Throwable;
use Exception;
class CourseMaterialTemplate {
	public static function instance() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	protected function __construct() {
		add_action( 'learn-press/course-material/layout', array( $this, 'sections' ) );
		add_filter( 'lp/rest/ajax/allow_callback', array( $this, 'allow_callback' ) );
	}
	/**
	 * Allow callback for AJAX.
	 *
	 * @param array $callbacks
	 *
	 * @return array
	 */
	public function allow_callback( array $callbacks ): array {
		$callbacks[] = get_class( $this ) . ':render_material_items';

		return $callbacks;
	}

	public function sections( array $data = array() ) {
		$html_wrapper = apply_filters(
			'learn-press/course-material/sections/wrapper',
			array(
				'<div class="lp-list-material">' => '</div>',
			),
			$data
		);

		$item    = LP_Global::course_item();
		$item_id = 0;
		if ( ! $item && get_post_type( get_the_ID() ) === LP_COURSE_CPT ) {
			$item_id = get_the_ID();
		} elseif ( $item ) {
			$item_id = $item->get_id();
		} else {
			throw new Exception( __( 'Item not found', 'learnpress' ) );
		}
		// $item_id     = $item->get_id();
		$material_db = LP_Material_Files_DB::getInstance();
		$per_page    = (int) LP_Settings::get_option( 'material_file_per_page', - 1 );
		$total_rows  = $material_db->get_total( $item_id );
		$total_pages = $per_page > 0 ? ceil( $total_rows / $per_page ) : 0;

		$args     = array(
			'item_id'     => $item_id,
			'paged'       => 1,
			'per_page'    => $per_page,
			'total_pages' => $total_pages,
		);
		$callback = array(
			'class'  => self::class,
			'method' => 'render_material_items',
		);
		$content  = TemplateAJAX::load_content_via_ajax( $args, $callback );
		$section  = array(
			'wrap'     => '<div class="lp-list-material"><div class="lp-material-skeleton">',
			'content'  => $content,
			'wrap_end' => '</div></div>',
		);
		echo Template::combine_components( $section );
	}
	public static function render_material_items( $args ) {
		$content = new stdClass();
		try {
			$material_db = LP_Material_Files_DB::getInstance();
			$paged       = absint( $args['paged'] ?? 1 );
			$per_page    = absint( $args['per_page'] ?? 1 );
			$offset      = ( $per_page > 0 && $paged > 1 ) ? $per_page * ( $paged - 1 ) : 0;
			// $total_pages    = $per_page > 0 ? ceil( $total_rows / $per_page ) : 0;
			$material_files = $material_db->get_material_by_item_id( $args['item_id'], $per_page, $offset, false );
			if ( empty( $material_files ) ) {
				if ( $args['paged'] === 1 ) {
					$content->content = esc_html__( 'Empty material!', 'learnpress' );
				} else {
					$content->content = '';
				}
			} else {
				$material_html = '';
				foreach ( $material_files as $m ) {
					$material_html .= self::material_item( $m, $args['item_id'] );
				}
				if ( $args['paged'] === 1 ) {
					$sections         = array(
						'table_wrap'     => '<table class="course-material-table" >',
						'table_header'   => self::table_header(),
						'table_body'     => '<tbody>',
						'material_html'  => $material_html,
						'table_body_end' => '</tbody>',
						'table_wrap_end' => '</table>',
						'loadmore_btn'   => self::html_loadmore_btn( $args ),
					);
					$content->content = Template::combine_components( $sections );
				} else {
					$content->content = $material_html;
				}
				$content->total_pages = $args['total_pages'];
				$content->paged       = $args['paged'];
			}
		} catch ( Throwable $e ) {
			$content->content = Template::print_message( $e->getMessage(), 'error', false );
		}
		return $content;
	}
	public static function table_header() {
		$sections = array(
			'wrap'      => '<thead>',
			'file-name' => sprintf( '<th class="lp-material-th-file-name">%s</th>', esc_html__( 'Name', 'learnpress' ) ),
			'file-type' => sprintf( '<th class="lp-material-th-file-type">%s</th>', esc_html__( 'Type', 'learnpress' ) ),
			'file-size' => sprintf( '<th class="lp-material-th-file-size">%s</th>', esc_html__( 'Size', 'learnpress' ) ),
			'file-link' => sprintf( '<th class="lp-material-th-file-link">%s</th>', esc_html__( 'Download', 'learnpress' ) ),
			'wrap_end'  => '</thead>',
		);
		return Template::combine_components( $sections );
	}
	public static function material_item( $material, $current_item_id ) {
		$sections = array(
			'wrap'      => '<tr class="lp-material-item">',
			'file-name' => self::html_file_name( $material, $current_item_id ),
			'file-type' => self::html_file_type( $material ),
			'file-size' => self::html_file_size( $material ),
			'file-link' => self::html_file_link( $material ),
			'wrap_end'  => '</tr>',
		);
		return Template::combine_components( $sections );
	}
	public static function html_file_name( $material, $current_item_id ) {
		if ( get_post_type( $current_item_id ) == LP_COURSE_CPT && $material->item_type == LP_LESSON_CPT ) {
			$html_file_name = sprintf( esc_html__( '%1$s ( %2$s )' ), $material->file_name, get_the_title( $material->item_id ) );
		} else {
			$html_file_name = sprintf( esc_html__( '%s' ), $material->file_name );
		}
		$content = sprintf( '<td class="lp-material-file-name">%s</td>', $html_file_name );
		return $content;
	}
	public static function html_file_type( $material ) {
		return sprintf( '<td class="lp-material-file-type">%s</td>', $material->file_type );
	}
	public static function html_file_size( $material ) {
		if ( $material->method == 'upload' ) {
			$file_size = filesize( wp_upload_dir()['basedir'] . $material->file_path );
			$file_size = ( $file_size / 1024 < 1024 ) ? round( $file_size / 1024, 2 ) . 'KB' : round( $file_size / 1024 / 1024, 2 ) . 'MB';
		} else {
			$file_size = LP_WP_Filesystem::instance()->get_file_size_from_url( $material->file_path );
		}
		$content = sprintf( '<td class="lp-material-file-size">%s</td>', $file_size );
		return $content;
	}
	public static function html_file_link( $material ) {
		$file_url        = $material->method == 'upload' ? wp_upload_dir()['baseurl'] . $material->file_path : $material->file_path;
		$enable_nofollow = LP_Settings::instance()->get_option( 'material_url_nofollow', 'yes' );
		$rel             = '';
		if ( $enable_nofollow == 'yes' && $material->method == 'external' ) {
			$rel = 'nofollow';
		}
		$content = sprintf(
			'<td class="lp-material-file-link">
				<a href="%s" target="_blank" rel="%s">
                    <i class="lp-icon-file-download btn-download-material"></i>
                </a>
            </td>',
			esc_url_raw( $file_url ),
			$rel
		);
		return $content;
	}
	public static function html_loadmore_btn( $args ) {
		return $args['paged'] < $args['total_pages'] ? sprintf( '<button class="button lp-button lp-loadmore-material">%s</button>', esc_html__( 'Load more', 'learnpress' ) ) : '';
	}
}
