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

	public function sections() {
		ob_start();
		try {
			$html_wrapper = apply_filters(
				'learn-press/course-material/sections/wrapper',
				[
					'<article class="lp-content-area">' => '</article>',
					'<div class="lp-list-instructors">' => '</div>',
				],
				$data
			);
			?>
			<div class="lp-material-skeleton">
				<?php do_action( 'learn-press/course-material/before' ); ?>
				<?php lp_skeleton_animation_html( 5, 100 ); ?>
				<table class="course-material-table" >
					<tbody id="material-file-list">
					</tbody>
				</table>
				<button class="lp-button lp-loadmore-material" page="1"><?php esc_html_e( 'Load more.', 'learnpress' ); ?></button>
				<?php do_action( 'learn-press/course-material/after' ); ?>
			</div>
			<?php
			$content = ob_get_clean();
			echo Template::instance()->nest_elements( $html_wrapper, $content );
		} catch (Throwable $e) {
			ob_end_clean();
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
	}
	public function material_header() {
		$content = '';
		$html_wrapper = apply_filters(
			'learn-press/course-material/sections/header',
			[
				'<thead>' => '</thead>',
			]
		);
		ob_start();
		try {
			$sections = apply_filters(
				'learn-press/course-material/sections/header/fields',
				[
					'file-name' => [ 'text_html' => sprintf( '<th >%s</th>', esc_html( 'Name', 'learnpress' ) ) ],
					'file-type' => [ 'text_html' => sprintf( '<th >%s</th>', esc_html( 'Type', 'learnpress' ) ) ],
					'file-size' => [ 'text_html' => sprintf( '<th >%s</th>', esc_html( 'Size', 'learnpress' ) ) ],
					'file-link' => [ 'text_html' => sprintf( '<th >%s</th>', esc_html( 'Download', 'learnpress' ) ) ],
				]
			);
			$content = ob_get_clean();
			$content = Template::instance()->nest_elements( $html_wrapper, $content );
		} catch (Throwable $e) {
			ob_end_clean();
			error_log( __METHOD__ . ': ' . $e->getMessage() );	
		}
	}
	public function material_item( $material, $item_id ) {
		$content      = '';
		$html_wrapper = apply_filters(
			'learn-press/course-material/item/wrapper',
			[
				'<tr class="item-material">' => '</tr>',
			],
			$material
		);

		ob_start();
		try {
			$sections = apply_filters(
				'learn-press/course-material/item/fields',
				[
					'file-name' => [ 'text_html' => $this->render_file_name( $material, $item_id ) ],
					'file-type' => [ 'text_html' => $this->render_file_type( $material ) ],
					'file-size' => [ 'text_html' => $this->render_file_type( $material ) ],
					'file-link' => [ 'text_html' => $this->render_file_link( $material ) ],
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
	public function render_file_name( $material, $item_id ) {
		$content      = '';
		$html_wrapper = apply_filters(
			'learn-press/course-material/item/file-name/wrapper',
			[
				'<td class="file-name">' => '</td>',
			],
			$material
		);
		ob_start();
		try {
			if ( get_post_type( $item_id ) == LP_COURSE_CPT && $material->item_type == LP_LESSON_CPT ) {
				$html_file_name = sprintf( esc_html__( '%1$s ( %2$s )' ), $material->file_name, get_the_title( $material->item_id ) );
			} else {
				$html_file_name = sprintf( esc_html__( '%s', $material->file_name ) );	
			}
			
			$sections = apply_filters( 
				'learn-press/course-material/item/file-name/value',
				'file_name'  => [ 'text_html' => $html_file_name ],
			);
			Template::instance()->print_sections( $sections, compact( 'material' ) );
			$content = ob_get_clean();
			$content = Template::instance()->nest_elements( $html_wrapper, $content );
		} catch (Throwable $e) {
			ob_end_clean();
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
	}
	public function render_file_type( $material ) {
		$content      = '';
		$html_wrapper = apply_filters(
			'learn-press/course-material/item/file-type/wrapper',
			[
				'<td class="file-type">' => '</td>',
			],
			$material
		);
		ob_start();
		try {
			$html_file_type = sprintf( '%s', $material->file_type );
			$sections = apply_filters( 
				'learn-press/course-material/item/file-type/value',
				'file_type'  => [ 'text_html' => $html_file_type ],
			);
			Template::instance()->print_sections( $sections, compact( 'material' ) );
			$content = ob_get_clean();
			$content = Template::instance()->nest_elements( $html_wrapper, $content );
		} catch (Throwable $e) {
			ob_end_clean();
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
	}
	public function render_file_size( $material ) {
		$content      = '';
		$html_wrapper = apply_filters(
			'learn-press/course-material/item/file-size/wrapper',
			[
				'<td class="file-size">' => '</td>',
			],
			$material
		);
		ob_start();
		try {
			if ( $material->method == 'upload' ) {
				$file_size = filesize( wp_upload_dir()['basedir'] . $material->file_path );
				$file_size = ( $file_size / 1024 < 1024 ) ? round( $file_size / 1024, 2 ) . 'KB' : round( $file_size / 1024 / 1024, 2 ) . 'MB';
			} else {
				$file_size = LP_WP_Filesystem::instance()->get_file_size_from_url( $material->file_path );
			}
			$html_file_size = sprintf( '%s', $file_size );
			$sections = apply_filters( 
				'learn-press/course-material/item/file-size/value',
				'file_size'  => [ 'text_html' => $html_file_size ],
			);
			Template::instance()->print_sections( $sections, compact( 'material' ) );
			$content = ob_get_clean();
			$content = Template::instance()->nest_elements( $html_wrapper, $content );
		} catch (Throwable $e) {
			ob_end_clean();
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
	}
	public function render_file_link( $material ) {
		$content      = '';
		$html_wrapper = apply_filters(
			'learn-press/course-material/item/file-link/wrapper',
			[
				'<td class="file-link">' => '</td>',
			],
			$material
		);
		ob_start();
		try {
			$file_url = $material->method == 'upload' ? wp_upload_dir()['baseurl'] . $material->file_path : $material->file_path;
			$html_file_link = sprintf( '
				<a href="%s" target="_blank">
                    <i class="fas fa-file-download btn-download-material"></i>
                </a>', $file_url );
			$sections = apply_filters( 
				'learn-press/course-material/item/file-link/value',
				'file_link'  => [ 'text_html' => $html_file_link ],
			);
			Template::instance()->print_sections( $sections, compact( 'material' ) );
			$content = ob_get_clean();
			$content = Template::instance()->nest_elements( $html_wrapper, $content );
		} catch (Exception $e) {
			ob_end_clean();
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
	}
 }
