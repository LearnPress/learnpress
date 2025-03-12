<?php
/**
 * Class ProfileQuizzesTemplate.
 *
 * @since 4.2.8
 * @version 1.0.0
 */
namespace LearnPress\TemplateHooks\Profile;

use LearnPress\Helpers\Template;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\TemplateAJAX;
use LearnPress\Models\UserItems\UserQuizModel;
use LP_Profile;
use LP_Datetime;
use LP_Page_Controller;
use Exception;
use stdClass;
use Throwable;

final class ProfileQuizzesTemplate {
	public static function instance() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	public static function tab_content() {
		do_action( 'learn-press/profile/layout/quizzes' );
	}

	protected function __construct() {
		add_filter( 'lp/rest/ajax/allow_callback', array( $this, 'allow_callback' ) );
		add_action( 'learn-press/profile/layout/quizzes', array( $this, 'quiz_profile_layout' ), 2 );
	}

	public static function init() {
		self::instance();
	}

	public function allow_callback( $callbacks ) {
		$callbacks[] = get_class( $this ) . ':renderContent';
		return $callbacks;
	}

	public function quiz_profile_layout( $data ) {
		$html_wrapper = array(
			'<div class="learn-press-subtab-content">' => '</div>',
		);
		$profile      = LP_Profile::instance();
		if ( ! $profile ) {
			throw new Exception( __( 'LP Profile is not exist', 'learnpress' ) );
		}
		if ( ! $profile->get_user() ) {
			throw new Exception( __( 'Invalid User Profile', 'learnpress' ) );
		}
		$user_id = $profile->get_user()->get_id();
		if ( ! $user_id ) {
			throw new Exception( __( 'User is not exist', 'learnpress' ) );
		}
		$callback = array(
			'class'  => get_class( $this ),
			'method' => 'renderContent',
		);
		$args     = array(
			'user_id' => $user_id,
			'paged'   => 1,
			'perpage' => 2,
			'type'    => 'all',
		);

		$content = TemplateAJAX::load_content_via_ajax( $args, $callback );
		$html    = Template::instance()->nest_elements( $html_wrapper, $content );
		echo $html;
	}
	public static function renderContent( $args ): stdClass {
		$content = new stdClass();
		ob_start();
		try {
			$user = UserModel::find( $args['user_id'] );
			if ( ! $user ) {
				throw new Exception( __( 'Invalid User', 'learnpress' ) );
			}
			$quiz_args = $user->get_quizzes_attend( $args );
			$sections  = apply_filters(
				'learnpress/profile/quiz-tab/sections',
				array(
					'tab-header' => self::quiz_tab_header( $args['user_id'], $args['type'] ),
					'tab-table'  => self::quiz_tab_table( $quiz_args, $args ),
				)
			);
			set_transient( 'renderContent_sections', $sections, 3600 );
			$content->content     = Template::combine_components( $sections );
			$content->total_pages = $quiz_args['total_page'];
			$content->paged       = $args['paged'];
			set_transient( 'quiz_tab_content', $content, $expiration = 3660 );
		} catch ( Throwable $e ) {
			ob_end_clean();
			error_log( __METHOD__ . '-' . $e->getMessage() );
		}
		return $content;
	}
	public static function quiz_tab_header( int $user_id = 0, string $type = 'all' ): string {
		$content = '';
		$profile = LP_Profile::instance( $user_id );
		ob_start();
		$quiz_filter = $profile->get_quizzes_filters( $type );
		if ( $quiz_filter ) {
			?>
			<div class="learn-press-tabs">
				<ul class="learn-press-filters">
					<?php foreach ( $quiz_filter as $class => $link ) : ?>
						<li class="<?php echo esc_attr( $class ); ?><?php echo esc_attr( $class === $type ? ' active' : '' ); ?>">
							<?php echo wp_kses_post( $link ); ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php
			$content = ob_get_clean();
		} else {
			ob_end_clean();
		}
		return $content;
	}
	public static function quiz_tab_table( $quiz_args, $args ): string {
		$sections = apply_filters(
			'learnpress/profile/quiz-table/sections',
			array(
				'start_wrap'   => '<table class="lp-list-table profile-list-quizzes profile-list-table">',
				'table_header' => self::table_header(),
				'table_body'   => self::table_body( $quiz_args, $args ),
				'table_footer' => self::table_footer( $quiz_args, $args ),
				'end_wrap'     => '</table>',
			)
		);
		return Template::combine_components( $sections );
	}
	public static function table_header(): string {
		$sections = apply_filters(
			'learnpress/profile/quiz-table/footer/sections',
			array(
				'<thead>',
				'<tr>',
				'<th class="column-quiz"> ' . __( 'Quiz', 'learnpress' ) . '</th>',
				'<th class="column-status"> ' . __( 'Result', 'learnpress' ) . '</th>',
				'<th class="column-time-interval"> ' . __( 'Time spent', 'learnpress' ) . '</th>',
				'<th class="column-date"> ' . __( 'Date', 'learnpress' ) . '</th>',
				'</tr>',
				'</thead>',
			)
		);
		return Template::combine_components( $sections );
	}
	public static function table_body( $quiz_args, $args ) {
		$content = '';
		foreach ( $quiz_args['items'] as $item ) {
			$content .= self::quiz_row( $item, $args );
		}
		return $content;
	}
	public static function quiz_row( $item, $args ) {
		$userQuizModel = UserQuizModel::find_user_item(
			$item->user_id,
			$item->item_id,
			$item->item_type,
			$item->ref_id,
			$item->ref_type,
			true
		);
		$start_time    = new LP_Datetime( $userQuizModel->get_start_time() );
		$sections      = apply_filters(
			'learnpress/profile/quiz-table/item/sections',
			array(
				'<tr>',
				'<td class="column-quiz column-quiz-' . $item->item_id . '">',
				$userQuizModel->get_quiz_post_model()->get_the_title(),
				'</td>',
				'<td class="column-status">',
				'<span class="result-percent">' . esc_html( $userQuizModel->get_result()['result'] ) . '% </span>',
				'<span class="lp-label label-' . esc_attr( $item->status ) . '">',
				$userQuizModel->get_status_label( $item->graduation ),
				'</span>',
				'</td>',
				'<td class="column-time-interval">',
				$userQuizModel->get_time_spend(),
				'</td>',
				'<td class="column-date">',
				$start_time->format( LP_Datetime::I18N_FORMAT ),
				'</td>',
				'</tr>',
			)
		);
		return Template::combine_components( $sections );
	}
	public static function table_footer( $quiz_args, $args ): string {
		$content  = '';
		$offset   = self::get_offset_text( $args['paged'], $args['perpage'], $quiz_args['total_page'] );
		$nav_link = $quiz_args['total_page'] < 2 ? '' : self::get_nav_numbers( $args['paged'], $quiz_args['total_page'] );
		$sections = apply_filters(
			'learnpress/profile/quiz-table/footer/sections',
			array(
				'<tfoot>',
				'<tr class="list-table-nav">',
				'<td colspan="2" class="nav-text">',
				$offset,
				'</td>',
				'<td colspan="2" class="nav-pages">',
				$nav_link,
				'</td>',
				'</tr>',
				'</tfoot>',
			),
			$quiz_args,
			$args
		);
		return Template::combine_components( $sections );
	}
	/**
	 * get offset array
	 *
	 * @param  integer $paged      number of page
	 * @param  integer $perpage    quiz foreach page
	 * @param  integer $total_page total page
	 * @return array offset array
	 */
	public static function get_offset( int $paged, int $perpage, int $total_page ): array {
		$from = ( $paged - 1 ) * $perpage + 1;
		$to   = $from + $perpage - 1;
		$to   = min( $to, $total_page );
		if ( $total_page < 1 ) {
			$from = 0;
		}

		return array( $from, $to );
	}
	/**
	 * Get offset text
	 *
	 * @param  int|integer $paged      number of page
	 * @param  int|integer $perpage    quizzes per page
	 * @param  int|integer $total_page total page
	 * @return string offset text
	 */
	public static function get_offset_text( int $paged = 1, int $perpage = 10, int $total_page = 1 ): string {
		$offset = self::get_offset( $paged, $perpage, $total_page );
		$output = '';
		$single = __( 'quiz', 'learnpress' );
		$plural = __( 'quizzes', 'learnpress' );
		$format = __( 'Displaying {{from}} to {{to}} of {{total}} {{item_name}}.', 'learnpress' );

		$output = str_replace(
			array( '{{from}}', '{{to}}', '{{total}}', '{{item_name}}' ),
			array(
				$offset[0],
				$offset[1],
				$total_page,
				$total_page < 2 ? $single : $plural,
			),
			$format
		);
		return wp_kses_post( $output );
	}
	/**
	 * get nav text
	 *
	 * @param  int|integer $paged      current page
	 * @param  int|integer $total_page total page
	 * @return string nav link html
	 */
	public static function get_nav_numbers( int $paged = 1, int $total_page = 1 ) {
		return learn_press_paging_nav(
			array(
				'num_pages' => $total_page,
				'paged'     => $paged,
				'echo'      => false,
				'format'    => '%#%/',
				'base'      => '#',
			)
		);
	}
}
