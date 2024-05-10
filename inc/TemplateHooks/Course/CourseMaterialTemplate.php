<?php
/**
 * Template hooks Archive Package.
 *
 * @since 1.0.0
 * @version 1.0.0
 */
namespace LearnPress\TemplateHooks\Course;

use LearnPress\Helpers\Template;
use LP_WP_Filesystem;
use LP_Settings;
use Throwable;
class CourseMaterialTemplate {
	public static function instance() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	protected function __construct() {
		add_action( 'learn-press/course-material/layout', [ $this, 'sections' ] );
	}

	public function sections( array $data = [] ) {
		// $content = '';
		ob_start();
		try {
			$html_wrapper = apply_filters(
				'learn-press/course-material/sections/wrapper',
				[
					'<div class="lp-list-material">' => '</div>',
				],
				$data
			);
			?>
			<div class="lp-material-skeleton">
				<?php do_action( 'learn-press/course-material/before' ); ?>
				<?php lp_skeleton_animation_html( 5, 100 ); ?>
				<table class="course-material-table" >
					<?php echo $this->material_header(); ?>
					<tbody id="material-file-list">
					</tbody>
				</table>
				<button class="lp-button lp-loadmore-material" page="1"><?php esc_html_e( 'Load more.', 'learnpress' ); ?></button>
				<?php do_action( 'learn-press/course-material/after' ); ?>
			</div>
			<?php
			$html_content = ob_get_clean();
			$content      = Template::instance()->nest_elements( $html_wrapper, $html_content );
			echo $content;
		} catch ( Throwable $e ) {
			ob_end_clean();
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
	}
	public function material_header() {
		$content      = '';
		$html_wrapper = apply_filters(
			'learn-press/course-material/header/wrapper',
			[
				'<thead>' => '</thead>',
			]
		);
		ob_start();
		try {
			$sections = apply_filters(
				'learn-press/course-material/header/fields',
				[
					'file-name' => [ 'text_html' => sprintf( '<th class="lp-material-th-file-name">%s</th>', esc_html__( 'Name', 'learnpress' ) ) ],
					'file-type' => [ 'text_html' => sprintf( '<th class="lp-material-th-file-type">%s</th>', esc_html__( 'Type', 'learnpress' ) ) ],
					'file-size' => [ 'text_html' => sprintf( '<th class="lp-material-th-file-size">%s</th>', esc_html__( 'Size', 'learnpress' ) ) ],
					'file-link' => [ 'text_html' => sprintf( '<th class="lp-material-th-file-link">%s</th>', esc_html__( 'Download', 'learnpress' ) ) ],
				]
			);
			Template::instance()->print_sections( $sections );
			$content = ob_get_clean();
			$content = Template::instance()->nest_elements( $html_wrapper, $content );
		} catch ( Throwable $e ) {
			ob_end_clean();
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
		return $content;
	}
	public function material_item( $material ) {
		$content      = '';
		$html_wrapper = apply_filters(
			'learn-press/course-material/item/wrapper',
			[
				'<tr class="lp-material-item">' => '</tr>',
			],
			$material
		);

		ob_start();
		try {
			$sections = apply_filters(
				'learn-press/course-material/item/fields',
				[
					'file-name' => [ 'text_html' => $this->html_file_name( $material ) ],
					'file-type' => [ 'text_html' => $this->html_file_type( $material ) ],
					'file-size' => [ 'text_html' => $this->html_file_size( $material ) ],
					'file-link' => [ 'text_html' => $this->html_file_link( $material ) ],
				],
				$material
			);
			Template::instance()->print_sections( $sections, compact( 'material' ) );
			$content = ob_get_clean();
			$content = Template::instance()->nest_elements( $html_wrapper, $content );
		} catch ( Throwable $e ) {
			ob_end_clean();
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}
	public function html_file_name( $material ) {
		$content = '';
		try {
			$html_wrapper = [
				'<td class="lp-material-file-name">' => '</td>',
			];
			if ( get_post_type( $material->current_item_id ) == LP_COURSE_CPT && $material->item_type == LP_LESSON_CPT ) {
				$html_file_name = sprintf( esc_html__( '%1$s ( %2$s )' ), $material->file_name, get_the_title( $material->item_id ) );
			} else {
				$html_file_name = sprintf( esc_html__( '%s' ), $material->file_name );
			}
			$content = Template::instance()->nest_elements( $html_wrapper, $html_file_name );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
		return $content;
	}
	public function html_file_type( $material ) {
		$content = '';
		try {
			$html_wrapper   = [
				'<td class="lp-material-file-type">' => '</td>',
			];
			$html_file_type = sprintf( '%s', $material->file_type );
			$content        = Template::instance()->nest_elements( $html_wrapper, $html_file_type );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
		return $content;
	}
	public function html_file_size( $material ) {
		$content = '';
		try {
			$html_wrapper =
				[
					'<td class="lp-material-file-size">' => '</td>',
				];
			if ( $material->method == 'upload' ) {
				$file_size = filesize( wp_upload_dir()['basedir'] . $material->file_path );
				$file_size = ( $file_size / 1024 < 1024 ) ? round( $file_size / 1024, 2 ) . 'KB' : round( $file_size / 1024 / 1024, 2 ) . 'MB';
			} else {
				$file_size = LP_WP_Filesystem::instance()->get_file_size_from_url( $material->file_path );
			}
			$html_file_size = sprintf( '%s', $file_size );
			$content        = Template::instance()->nest_elements( $html_wrapper, $html_file_size );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
		return $content;
	}
	public function html_file_link( $material ) {
		$content = '';
		try {
			$html_wrapper    =
				[
					'<td class="lp-material-file-link">' => '</td>',
				];
			$file_url        = $material->method == 'upload' ? wp_upload_dir()['baseurl'] . $material->file_path : $material->file_path;
			$enable_nofollow = LP_Settings::instance()->get_option( 'material_url_nofollow', 'yes' );
			$rel             = '';
			if ( $enable_nofollow == 'yes' && $material->method == 'external' ) {
				$rel = 'nofollow';
			}
			$html_file_link = sprintf(
				'<a href="%s" target="_blank" rel="%s">
                    <i class="lp-icon-file-download btn-download-material"></i>
                </a>',
				esc_url_raw( $file_url ),
				$rel
			);
			$content        = Template::instance()->nest_elements( $html_wrapper, $html_file_link );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
		return $content;
	}
}
