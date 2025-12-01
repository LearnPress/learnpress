<?php
/**
 * Template hooks Archive Package.
 *
 * @since 1.0.0
 * @version 1.0.0
 */
namespace LearnPress\TemplateHooks\Course;

use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserModel;
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

	/**
	 * Layout for course material.
	 *
	 * @throws Exception
	 */
	public function sections( array $data = array() ) {
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

		$args = array_merge(
			[
				'id_url'      => 'course-material',
				'item_id'     => $item_id,
				'paged'       => 1,
				'per_page'    => $per_page,
				'total_pages' => $total_pages,
			],
			$data
		);
		if ( get_post_type( $item_id ) !== LP_COURSE_CPT ) {
			$args['course_id'] = $item->get_course_id();
		}

		$callback = array(
			'class'  => self::class,
			'method' => 'render_material_items',
		);
		$content  = TemplateAJAX::load_content_via_ajax( $args, $callback );
		$section  = array(
			'wrap'     => '<div class="lp-material-skeleton">',
			'content'  => $content,
			'wrap_end' => '</div>',
		);
		echo Template::combine_components( $section );
	}

	public static function render_material_items( $args ): stdClass {
		$content = new stdClass();

		try {
			$userModel = UserModel::find( get_current_user_id(), true );
			$course_id = $args['course_id'] ?? 0;
			$item_id   = $args['item_id'] ?? 0;
			if ( get_post_type( $item_id ) === LP_COURSE_CPT ) {
				$courseModel = CourseModel::find( $item_id, true );
			} elseif ( $course_id ) {
				$courseModel = CourseModel::find( $course_id, true );
			} else {
				throw new Exception( esc_html__( 'Course not found!', 'learnpress' ) );
			}

			$can_show = false;
			if ( $userModel instanceof UserModel ) {
				if ( $courseModel->check_user_is_author( $userModel )
					|| user_can( $userModel->get_id(), ADMIN_ROLE ) ) {
					$can_show = true;
				} else {
					$userCourseModel = UserCourseModel::find( $userModel->get_id(), $courseModel->get_id(), true );
					if ( $userCourseModel &&
						( $userCourseModel->has_enrolled_or_finished()
							|| $userCourseModel->has_purchased() ) ) {
						$can_show = true;
					}
				}
			} elseif ( $courseModel->has_no_enroll_requirement() ) {
				$can_show = true;
			}

			$can_show = apply_filters( 'learn-press/course-material/can-show', $can_show, $courseModel, $userModel );

			$file_per_page = LP_Settings::get_option( 'material_file_per_page', - 1 );
			$count_files   = LP_Material_Files_DB::getInstance()->get_total( $courseModel->get_id() );
			if ( ! $can_show || $file_per_page == 0 || $count_files <= 0 ) {
				throw new Exception( '' );
			}

			$material_db = LP_Material_Files_DB::getInstance();
			$paged       = absint( $args['paged'] ?? 1 );
			$per_page    = intval( $args['per_page'] ?? 1 );
			$offset      = ( $per_page > 0 && $paged > 1 ) ? $per_page * ( $paged - 1 ) : 0;
			// $total_pages    = $per_page > 0 ? ceil( $total_rows / $per_page ) : 0;
			$material_files = $material_db->get_material_by_item_id( $args['item_id'], $per_page, $offset, false );
			if ( empty( $material_files ) ) {
				$content->content = '';
			} else {
				$material_html = '';
				foreach ( $material_files as $m ) {
					$material_html .= self::material_item( $m, $args['item_id'] );
				}
				if ( $args['paged'] === 1 ) {
					$sections         = array(
						'wrap'           => '<div class="lp-list-material">',
						'table_wrap'     => '<table class="course-material-table" >',
						'table_header'   => self::table_header(),
						'table_body'     => '<tbody>',
						'material_html'  => $material_html,
						'table_body_end' => '</tbody>',
						'table_wrap_end' => '</table>',
						'loadmore_btn'   => self::html_load_more_btn( $args ),
						'wrap_end'       => '</div>',
					);
					$content->content = Template::combine_components( $sections );
				} else {
					$content->content = $material_html;
				}
				$content->total_pages = $args['total_pages'];
				$content->paged       = $args['paged'];
			}
		} catch ( Throwable $e ) {
			if ( $e->getMessage() === '' ) {
				$content->content = '';
			} else {
				$content->content = Template::print_message( $e->getMessage(), 'error', false );
			}
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

	/**
	 * Generate HTML for file name.
	 *
	 * @param object $material Material object.
	 * @param int    $current_item_id Current item ID.
	 *
	 * @return string
	 */
	public static function html_file_name( $material, $current_item_id ): string {
		if ( get_post_type( $current_item_id ) == LP_COURSE_CPT && $material->item_type == LP_LESSON_CPT ) {
			$html_file_name = sprintf( esc_html( '%1$s ( %2$s )' ), $material->file_name, get_the_title( $material->item_id ) );
		} else {
			$html_file_name = sprintf( esc_html( '%s' ), $material->file_name );
		}

		return sprintf( '<td class="lp-material-file-name">%s</td>', $html_file_name );
	}

	/**
	 * Generate HTML for file type.
	 *
	 * @param object $material Material object.
	 *
	 * @return string
	 */
	public static function html_file_type( $material ): string {
		return sprintf( '<td class="lp-material-file-type">%s</td>', $material->file_type );
	}

	/**
	 * Get file size in human-readable format.
	 *
	 * @param object $material Material object.
	 *
	 * @return string
	 */
	public static function html_file_size( $material ): string {
		if ( $material->method == 'upload' ) {
			$file_size = filesize( wp_upload_dir()['basedir'] . $material->file_path );
			$file_size = ( $file_size / 1024 < 1024 ) ? round( $file_size / 1024, 2 ) . 'KB' : round( $file_size / 1024 / 1024, 2 ) . 'MB';
		} else {
			$args      = array(
				'timeout' => 0.1,
			);
			$file_head = wp_remote_head( $material->file_path, $args );

			if ( is_wp_error( $file_head )
				|| ! isset( $file_head['headers']['content-length'] )
				|| ! $file_head['headers']['content-length'] ) {
				$file_size = '';
			} else {
				$file_size = $file_head['headers']['content-length'];
				$file_size = self::convert_kb( intval( $file_size ) );
			}
		}

		return apply_filters(
			'learn-press/course-material/file-size',
			sprintf( '<td class="lp-material-file-size">%s</td>', $file_size ),
			$material
		);
	}

	/**
	 * Convert file size to human-readable format.
	 *
	 * @param int $file_size File size in bytes.
	 *
	 * @return string
	 */
	public static function convert_kb( int $file_size ): string {
		// Convert bytes to kilobytes
		$file_size = $file_size / 1024;

		if ( $file_size < 1024 ) {
			return round( $file_size, 2 ) . 'KB';
		} elseif ( $file_size < 1048576 ) {
			return round( $file_size / 1024, 2 ) . 'MB';
		} else {
			return round( $file_size / 1048576, 2 ) . 'GB';
		}
	}

	/**
	 * Generate HTML for file link.
	 *
	 * @param object $material Material object.
	 *
	 * @return string
	 */
	public static function html_file_link( $material ): string {
		$file_path = $material->file_path ?? '';
		if ( empty( $file_path ) ) {
			return '';
		}

		$file_url = $file_path;
		if ( $material->method === 'upload' ) {
			$file_url = wp_upload_dir()['baseurl'] . $file_path;
		}

		$enable_nofollow = LP_Settings::instance()->get_option( 'material_url_nofollow', 'yes' );
		$rel             = '';
		if ( $enable_nofollow == 'yes' && $material->method == 'external' ) {
			$rel = 'nofollow';
		}

		return apply_filters(
			'learn-press/course-material/file-link',
			sprintf(
				'<td class="lp-material-file-link">
					<a href="%s" target="_blank" rel="%s">
	                    <i class="lp-icon-file-download btn-download-material"></i>
	                </a>
	            </td>',
				esc_url_raw( $file_url ),
				$rel
			)
		);
	}

	/**
	 * Generate HTML for load more button.
	 *
	 * @param array $args Arguments containing pagination info.
	 *
	 * @return string
	 */
	public static function html_load_more_btn( array $args = [] ): string {
		$paged       = $args['paged'] ?? 1;
		$total_pages = $args['total_pages'] ?? 1;

		if ( $paged >= $total_pages ) {
			return '';
		}

		return apply_filters(
			'learn-press/course-material/btn-load-more',
			sprintf(
				'<div class="lp-loadmore-material">
					<button class="lp-btn lp-loadmore-material">
						%s
					</button>
				</div>',
				esc_html__( 'Load More', 'learnpress' )
			)
		);
	}
}
