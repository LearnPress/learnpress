<?php
/**
 * Template hooks Tab Dashboard in Course Builder.
 *
 * @since 4.3.0
 * @version 2.0.0
 */

namespace LearnPress\TemplateHooks\CourseBuilder;

use LearnPress\CourseBuilder\CourseBuilder;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\UserModel;
use LP_Course_DB;
use LP_Course_Filter;
use LP_Statistics_DB;
use LP_User_Items_DB;
use LP_User_Items_Filter;
use Throwable;

class BuilderTabDashboardTemplate {
	use Singleton;

	public function init() {
		add_action( 'learn-press/course-builder/dashboard/layout', [ $this, 'html_tab_dashboard' ] );
	}

	/**
	 * Render dashboard tab HTML.
	 *
	 * @return void
	 */
	public function html_tab_dashboard() {
		$html = '';

		try {
			$user_id = get_current_user_id();
			if ( ! $user_id ) {
				return;
			}

			$user = UserModel::find( $user_id );
			if ( ! $user ) {
				return;
			}

			$html = $this->render_dashboard_content( $user );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		echo Template::combine_components( [
			'wrapper'     => '<div class="lp-course-builder-dashboard">',
			'content'     => $html,
			'wrapper_end' => '</div>',
		] );
	}

	/**
	 * Render dashboard content.
	 *
	 * @param UserModel $user
	 * @return string
	 */
	private function render_dashboard_content( UserModel $user ): string {
		$is_admin = user_can( $user->get_id(), 'administrator' );

		if ( $is_admin ) {
			$statistic = $this->get_admin_statistic();
		} else {
			$statistic = $user->get_instructor_statistic();
		}

		$stats_html          = $this->render_statistics_cards( $statistic, $is_admin );
		$charts_html         = $this->render_charts_section( $is_admin, $user->get_id() );
		$top_instructors_html = $is_admin ? $this->render_top_instructors_section() : '';
		$top_courses_html    = $this->render_top_courses_section( $is_admin ? 0 : $user->get_id() );
		$recent_courses_html = $this->render_recent_courses_section( $user );
		$quick_actions_html  = $this->render_quick_actions();

		return Template::combine_components( [
			'stats'           => $stats_html,
			'charts_row'      => '<div class="lp-cb-dashboard__charts-row">' . $charts_html . $top_instructors_html . '</div>',
			'top_courses'     => $top_courses_html,
			'quick_actions'   => $quick_actions_html,
			'recent_courses'  => $recent_courses_html,
		] );
	}

	/**
	 * Get global statistics for admin.
	 *
	 * @return array
	 */
	private function get_admin_statistic(): array {
		$statistic = array(
			'total_course'     => 0,
			'published_course' => 0,
			'pending_course'   => 0,
			'total_student'    => 0,
			'total_instructor' => 0,
		);

		try {
			global $wpdb;

			// Course counts via wp_count_posts
			$course_counts = wp_count_posts( LP_COURSE_CPT );

			$statistic['total_course']     = intval( $course_counts->publish ?? 0 )
				+ intval( $course_counts->pending ?? 0 )
				+ intval( $course_counts->draft ?? 0 )
				+ intval( $course_counts->private ?? 0 );
			$statistic['published_course'] = intval( $course_counts->publish ?? 0 );
			$statistic['pending_course']   = intval( $course_counts->pending ?? 0 );

			// Total unique students enrolled in any course
			$tb_user_items = $wpdb->prefix . 'learnpress_user_items';
			$total_students = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT user_id) FROM {$tb_user_items} WHERE item_type = %s",
					LP_COURSE_CPT
				)
			);
			$statistic['total_student'] = intval( $total_students );

			// Total instructors
			$statistic['total_instructor'] = $this->count_total_instructors();
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $statistic;
	}

	/**
	 * Count total instructors (admin + lp_teacher).
	 *
	 * @return int
	 */
	private function count_total_instructors(): int {
		global $wpdb;

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT u.ID)
				FROM {$wpdb->users} AS u
				INNER JOIN {$wpdb->usermeta} AS um ON um.user_id = u.ID
				WHERE um.meta_key = %s
				AND (um.meta_value LIKE %s OR um.meta_value LIKE %s)",
				$wpdb->prefix . 'capabilities',
				'%administrator%',
				'%' . LP_TEACHER_ROLE . '%'
			)
		);

		return intval( $result );
	}

	/**
	 * Render statistics cards.
	 *
	 * @param array $statistic
	 * @param bool  $is_admin
	 * @return string
	 */
	private function render_statistics_cards( array $statistic, bool $is_admin ): string {
		$cards = [
			[
				'key'   => 'total_course',
				'label' => __( 'Total Courses', 'learnpress' ),
				'color' => '#ef4444',
				'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><line x1="12" y1="6" x2="12" y2="12"/><line x1="9" y1="9" x2="15" y2="9"/></svg>',
			],
			[
				'key'   => 'published_course',
				'label' => __( 'Published Courses', 'learnpress' ),
				'color' => '#2E91FA',
				'svg'   => '<svg width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_5770_727)"><path d="M24 4.5C24.3978 4.5 24.7794 4.65804 25.0607 4.93934C25.342 5.22064 25.5 5.60218 25.5 6V7.5H28.5C29.2956 7.5 30.0587 7.81607 30.6213 8.37868C31.1839 8.94129 31.5 9.70435 31.5 10.5V28.5C31.5 29.2956 31.1839 30.0587 30.6213 30.6213C30.0587 31.1839 29.2956 31.5 28.5 31.5H7.5C6.70435 31.5 5.94129 31.1839 5.37868 30.6213C4.81607 30.0587 4.5 29.2956 4.5 28.5V10.5C4.5 9.70435 4.81607 8.94129 5.37868 8.37868C5.94129 7.81607 6.70435 7.5 7.5 7.5H10.5V6C10.5 5.60218 10.658 5.22064 10.9393 4.93934C11.2206 4.65804 11.6022 4.5 12 4.5C12.3978 4.5 12.7794 4.65804 13.0607 4.93934C13.342 5.22064 13.5 5.60218 13.5 6V7.5H22.5V6C22.5 5.60218 22.658 5.22064 22.9393 4.93934C23.2206 4.65804 23.6022 4.5 24 4.5ZM28.5 10.5H7.5V28.5H28.5V10.5ZM22.236 14.0685C22.5189 13.7953 22.8978 13.6441 23.2911 13.6475C23.6844 13.6509 24.0606 13.8087 24.3387 14.0868C24.6168 14.3649 24.7746 14.7411 24.778 15.1344C24.7814 15.5277 24.6302 15.9066 24.357 16.1895L16.944 23.604C16.8033 23.7448 16.6362 23.8565 16.4523 23.9328C16.2684 24.009 16.0713 24.0482 15.8723 24.0482C15.6732 24.0482 15.4761 24.009 15.2922 23.9328C15.1083 23.8565 14.9412 23.7448 14.8005 23.604L11.6295 20.43C11.3563 20.1471 11.2051 19.7682 11.2085 19.3749C11.2119 18.9816 11.3697 18.6054 11.6478 18.3273C11.9259 18.0492 12.3021 17.8914 12.6954 17.888C13.0887 17.8846 13.4676 18.0358 13.7505 18.309L15.873 20.43L22.236 14.0685Z" fill="#2E91FA"/></g><defs><clipPath id="clip0_5770_727"><rect width="36" height="36" fill="white"/></clipPath></defs></svg>',
			],
			[
				'key'   => 'pending_course',
				'label' => __( 'Pending Courses', 'learnpress' ),
				'color' => '#F8A100',
				'svg'   => '<svg width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_5770_8096)"><path d="M27 3C27.7956 3 28.5587 3.31607 29.1213 3.87868C29.6839 4.44129 30 5.20435 30 6V25.485C30 25.737 29.943 25.968 29.8305 26.193L29.013 27.828C28.9087 28.0364 28.8544 28.2662 28.8544 28.4993C28.8544 28.7323 28.9087 28.9621 29.013 29.1705L29.8275 30.8025C29.9476 31.0311 30.0066 31.2868 29.9989 31.5448C29.9911 31.8029 29.9169 32.0546 29.7834 32.2756C29.6499 32.4966 29.4617 32.6794 29.2368 32.8063C29.012 32.9332 28.7582 32.9999 28.5 33H10.5C9.30653 33 8.16193 32.5259 7.31802 31.682C6.47411 30.8381 6 29.6935 6 28.5V7.5C6 6.30653 6.47411 5.16193 7.31802 4.31802C8.16193 3.47411 9.30653 3 10.5 3H27ZM26.112 27H10.5C10.1177 27.0004 9.74995 27.1468 9.47195 27.4093C9.19394 27.6717 9.02665 28.0304 9.00424 28.4121C8.98184 28.7938 9.10601 29.1696 9.3514 29.4627C9.59678 29.7559 9.94486 29.9443 10.3245 29.9895L10.5 30H26.112C25.8079 29.1399 25.7724 28.2078 26.01 27.327L26.112 27ZM27 6H10.5C10.1326 6.00005 9.77799 6.13493 9.50344 6.37907C9.22889 6.62321 9.05349 6.95962 9.0105 7.3245L9 7.5V24.255C9.375 24.123 9.774 24.039 10.1865 24.0105L10.5 24H27V6ZM21 10.5C21.3823 10.5004 21.75 10.6468 22.0281 10.9093C22.3061 11.1717 22.4734 11.5304 22.4958 11.9121C22.5182 12.2938 22.394 12.6696 22.1486 12.9627C21.9032 13.2559 21.5551 13.4443 21.1755 13.4895L21 13.5H15C14.6177 13.4996 14.25 13.3532 13.9719 13.0907C13.6939 12.8283 13.5266 12.4696 13.5042 12.0879C13.4818 11.7062 13.606 11.3304 13.8514 11.0373C14.0968 10.7441 14.4449 10.5557 14.8245 10.5105L15 10.5H21Z" fill="#F8A100"/></g><defs><clipPath id="clip0_5770_8096"><rect width="36" height="36" fill="white"/></clipPath></defs></svg>',
			],
			[
				'key'   => 'total_student',
				'label' => __( 'Total Students', 'learnpress' ),
				'color' => '#28A746',
				'svg'   => '<svg width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M34.275 9.13516C34.1163 8.54109 33.7339 8.15936 33.1623 7.96557C32.0779 7.59814 30.9902 7.23949 29.9039 6.87791C26.1353 5.62346 22.3664 4.3703 18.5985 3.11389C18.1514 2.96464 17.7069 2.95489 17.2598 3.09796C16.7555 3.25956 16.2525 3.42474 15.7498 3.59187C12.1838 4.77902 8.61745 5.96487 5.05275 7.1556C4.1719 7.44987 3.293 7.75064 2.41963 8.06571C1.92214 8.24553 1.62559 8.61881 1.54105 9.14199C1.50561 9.35952 1.49813 9.58387 1.49781 9.80531C1.49553 13.7742 1.49618 17.7431 1.49618 21.7119C1.49618 21.7877 1.49618 21.8638 1.49976 21.9392C1.5352 22.5772 1.96603 23.1296 2.58838 23.3367C3.19383 23.538 3.87276 23.3452 4.28083 22.861C4.58062 22.5053 4.67069 22.0859 4.67004 21.6362C4.66842 20.6074 4.66451 19.5789 4.66419 18.5501C4.66321 16.3247 4.66386 14.0993 4.66419 11.874C4.66419 11.818 4.67037 11.7618 4.67492 11.6867C6.1209 12.1686 7.54313 12.643 8.98228 13.1226C7.73173 15.4022 7.35064 17.7957 7.91024 20.3229C8.47016 22.851 9.802 24.8809 11.9136 26.4235C11.8557 26.4504 11.8316 26.4622 11.8069 26.4729C9.31492 27.5693 7.25667 29.2071 5.65104 31.4091C5.39027 31.7671 5.10803 32.1147 4.99585 32.5569C4.8401 33.1711 4.97406 33.7106 5.45887 34.1303C5.91767 34.5277 6.44703 34.6587 7.03004 34.4392C7.43616 34.2861 7.71644 33.9769 7.95641 33.629C8.55567 32.7608 9.23168 31.9573 10.0455 31.2849C13.0123 28.8319 16.3877 27.8805 20.1921 28.5952C23.0492 29.132 25.3842 30.5601 27.2178 32.8102C27.5387 33.2043 27.8128 33.6381 28.1484 34.0182C28.852 34.8151 30.1523 34.6682 30.6635 33.7437C31.0059 33.1243 30.9096 32.5215 30.5275 31.9531C28.9548 29.612 26.8988 27.8317 24.3411 26.6361C24.1971 26.5688 24.0527 26.5021 23.8849 26.4244C25.9906 24.8812 27.3169 22.8565 27.8814 20.3339C28.4482 17.8016 28.0602 15.4078 26.8263 13.133C26.8728 13.1157 26.8972 13.1057 26.9222 13.0975C28.0905 12.706 29.2594 12.3155 30.4274 11.9227C31.3495 11.6129 32.272 11.3027 33.1922 10.9866C33.6595 10.8263 34.0149 10.5307 34.1989 10.0632C34.2542 9.92269 34.2873 9.79328 34.4961 9.85993C34.4161 9.59948 34.3374 9.3696 34.275 9.13516ZM22.8815 23.1377C21.6748 24.3613 20.2035 25.0311 18.4857 25.1579C16.4368 25.3095 14.6381 24.7011 13.1079 23.3445C12.2283 22.5651 11.6652 21.5572 11.249 20.4682C10.9589 19.7086 10.8071 18.9204 10.8119 18.0747C10.8074 16.8362 11.1973 15.6806 11.8199 14.5923C11.9051 14.444 11.9692 14.2281 12.097 14.1757C12.216 14.127 12.4081 14.2525 12.5665 14.3058C14.0824 14.815 15.5976 15.3249 17.1132 15.8347C17.6383 16.0113 18.1634 16.0142 18.6899 15.838C20.3085 15.2956 21.9278 14.7558 23.5451 14.2089C23.6856 14.1614 23.7581 14.1774 23.8449 14.3081C24.5108 15.3096 24.889 16.4102 24.941 17.6078C25.0337 19.7359 24.3912 21.6062 22.8815 23.1377ZM26.9453 9.74613C24.0289 10.7154 21.1116 11.6831 18.1959 12.6547C18.0018 12.7194 17.8214 12.7184 17.6269 12.6537C14.5155 11.6177 11.4031 10.5854 8.29067 9.55201C8.2406 9.53543 8.19215 9.51494 8.11248 9.48438C8.19312 9.45511 8.2445 9.43495 8.29652 9.41739C11.4278 8.37494 14.5591 7.33086 17.6929 6.29523C17.8207 6.25296 17.9872 6.25296 18.115 6.29556C21.2589 7.33574 24.4 8.38437 27.5413 9.4317C27.58 9.44471 27.6177 9.45999 27.6935 9.4886C27.421 9.58257 27.1839 9.66711 26.9453 9.74613Z" fill="#28A746"/></svg>',
			],
		];

		// Admin-only: Total Instructors card
		if ( $is_admin ) {
			$cards[] = [
				'key'   => 'total_instructor',
				'label' => __( 'Total Instructors', 'learnpress' ),
				'color' => '#06AED4',
				'svg'   => '<svg width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_5770_8016)"><path fill-rule="evenodd" clip-rule="evenodd" d="M16.5 6C15.9091 6 15.3239 6.1164 14.7779 6.34254C14.232 6.56869 13.7359 6.90016 13.318 7.31802C12.9002 7.73588 12.5687 8.23196 12.3425 8.77792C12.1164 9.32389 12 9.90905 12 10.5C12 11.0909 12.1164 11.6761 12.3425 12.2221C12.5687 12.768 12.9002 13.2641 13.318 13.682C13.7359 14.0998 14.232 14.4313 14.7779 14.6575C15.3239 14.8836 15.9091 15 16.5 15C17.6935 15 18.8381 14.5259 19.682 13.682C20.5259 12.8381 21 11.6935 21 10.5C21 9.30653 20.5259 8.16193 19.682 7.31802C18.8381 6.47411 17.6935 6 16.5 6ZM9 10.5C9 8.51088 9.79018 6.60322 11.1967 5.1967C12.6032 3.79018 14.5109 3 16.5 3C18.4891 3 20.3968 3.79018 21.8033 5.1967C23.2098 6.60322 24 8.51088 24 10.5C24 12.4891 23.2098 14.3968 21.8033 15.8033C20.3968 17.2098 18.4891 18 16.5 18C14.5109 18 12.6032 17.2098 11.1967 15.8033C9.79018 14.3968 9 12.4891 9 10.5ZM6.6195 26.4015C6.135 27.0165 6 27.4815 6 27.75C6 27.933 6.0555 28.1265 6.3825 28.389C6.756 28.689 7.4055 28.9995 8.3985 29.262C10.3755 29.787 13.2165 30 16.5 30C16.833 30 17.1615 29.997 17.487 29.9925C17.8848 29.9871 18.2685 30.14 18.5536 30.4175C18.8387 30.695 19.0019 31.0744 19.0073 31.4723C19.0126 31.8701 18.8597 32.2537 18.5822 32.5388C18.3047 32.8239 17.9253 32.9871 17.5275 32.9925C17.1885 32.997 16.845 33 16.5 33C13.1565 33 9.9975 32.79 7.6305 32.163C6.453 31.851 5.3445 31.404 4.5045 30.729C3.615 30.015 3 29.0175 3 27.75C3 26.5695 3.537 25.4655 4.266 24.5415C5.007 23.604 6.0315 22.7415 7.233 22.0065C9.6375 20.5425 12.9075 19.5 16.5 19.5C17.1705 19.5 17.8305 19.536 18.474 19.605C18.8696 19.647 19.2324 19.8444 19.4825 20.1538C19.7326 20.4633 19.8495 20.8594 19.8075 21.255C19.7655 21.6506 19.5681 22.0134 19.2587 22.2635C18.9492 22.5136 18.5531 22.6305 18.1575 22.5885C17.6175 22.53 17.0625 22.5 16.5 22.5C13.4655 22.5 10.7355 23.385 8.796 24.5685C7.8255 25.1595 7.092 25.8015 6.6195 26.4015ZM32.562 24.102C32.8352 23.8191 32.9864 23.4402 32.983 23.0469C32.9796 22.6536 32.8218 22.2774 32.5437 21.9993C32.2656 21.7212 31.8894 21.5634 31.4961 21.56C31.1028 21.5566 30.7239 21.7078 30.441 21.981L25.668 26.754L23.547 24.633C23.2641 24.3598 22.8852 24.2086 22.4919 24.212C22.0986 24.2154 21.7224 24.3732 21.4443 24.6513C21.1662 24.9294 21.0084 25.3056 21.005 25.6989C21.0016 26.0922 21.1528 26.4711 21.426 26.754L24.501 29.829C24.6542 29.9823 24.8362 30.1039 25.0364 30.1869C25.2366 30.2698 25.4513 30.3125 25.668 30.3125C25.8847 30.3125 26.0994 30.2698 26.2996 30.1869C26.4998 30.1039 26.6818 29.9823 26.835 29.829L32.562 24.102Z" fill="#06AED4"/></g><defs><clipPath id="clip0_5770_8016"><rect width="36" height="36" fill="white"/></clipPath></defs></svg>',
			];
		}

		$cards_html = '';
		foreach ( $cards as $card ) {
			$value = $statistic[ $card['key'] ] ?? 0;
			$cards_html .= sprintf(
				'<div class="lp-cb-dashboard__stat-card" style="--card-color: %s; --card-bg: %s15">
					<div class="stat-card__icon">%s</div>
					<span class="stat-card__label">%s</span>
					<span class="stat-card__value">%s</span>
				</div>',
				esc_attr( $card['color'] ),
				esc_attr( $card['color'] ),
				$card['svg'],
				esc_html( $card['label'] ),
				esc_html( number_format_i18n( $value ) )
			);
		}

		return Template::combine_components( [
			'wrapper'     => '<div class="lp-cb-dashboard__stats">',
			'content'     => $cards_html,
			'wrapper_end' => '</div>',
		] );
	}

	/**
	 * Render charts section with Net Sales and Students charts.
	 *
	 * @param bool $is_admin
	 * @param int  $user_id
	 * @return string
	 */
	private function render_charts_section( bool $is_admin, int $user_id ): string {
		$instructor_id = $is_admin ? 0 : $user_id;
		$nonce         = wp_create_nonce( 'lp_cb_dashboard_nonce' );

		// Get initial data - use 'year' for sales and 'previous_days' for students so data appears immediately
		try {
			$lp_statistic_db = LP_Statistics_DB::getInstance();
			$current_date    = current_time( 'Y-m-d' );

			$sales_data    = $lp_statistic_db->get_net_sales_data_scoped( 'year', $current_date, $instructor_id );
			$students_data = $lp_statistic_db->get_enrollment_chart_data( 'previous_days', 6, $instructor_id );

			// Process chart data using the REST controller's logic
			require_once LP_PLUGIN_PATH . 'inc/rest-api/v1/admin/class-lp-admin-rest-statistics-controller.php';
			$stats_ctrl = new \LP_REST_Admin_Statistics_Controller();

			$sales_chart    = $stats_ctrl->process_chart_data(
				[ 'filter_type' => 'year', 'time' => $current_date ],
				$sales_data
			);
			$students_chart = $stats_ctrl->process_chart_data(
				[ 'filter_type' => 'previous_days', 'time' => 6 ],
				$students_data
			);
		} catch ( Throwable $e ) {
			$sales_chart    = [ 'labels' => [], 'data' => [] ];
			$students_chart = [ 'labels' => [], 'data' => [] ];
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		$filter_options = '
			<option value="this_month">' . esc_html__( 'This month', 'learnpress' ) . '</option>
			<option value="this_week">' . esc_html__( 'This week', 'learnpress' ) . '</option>
			<option value="this_year" selected>' . esc_html__( 'This year', 'learnpress' ) . '</option>';

		$html = sprintf(
			'<div class="lp-cb-dashboard__chart-card">
				<div class="chart-card__header">
					<h3 class="chart-card__title">%s</h3>
					<select class="chart-card__filter" data-chart="sales" data-nonce="%s">%s</select>
				</div>
				<div class="chart-card__body">
					<canvas id="lp-cb-chart-sales"></canvas>
				</div>
			</div>
			<div class="lp-cb-dashboard__chart-card">
				<div class="chart-card__header">
					<h3 class="chart-card__title">%s</h3>
					<select class="chart-card__filter" data-chart="students" data-nonce="%s">
						<option value="this_week" selected>%s</option>
						<option value="this_month">%s</option>
						<option value="this_year">%s</option>
					</select>
				</div>
				<div class="chart-card__body">
					<canvas id="lp-cb-chart-students"></canvas>
				</div>
			</div>
			<script id="lp-cb-dashboard-chart-data" type="application/json">%s</script>',
			esc_html__( 'Net sales', 'learnpress' ),
			esc_attr( $nonce ),
			$filter_options,
			esc_html__( 'Students', 'learnpress' ),
			esc_attr( $nonce ),
			esc_html__( 'This week', 'learnpress' ),
			esc_html__( 'This month', 'learnpress' ),
			esc_html__( 'This year', 'learnpress' ),
			wp_json_encode( [
				'sales'    => [ 'labels' => $sales_chart['labels'] ?? [], 'data' => $sales_chart['data'] ?? [] ],
				'students' => [ 'labels' => $students_chart['labels'] ?? [], 'data' => $students_chart['data'] ?? [] ],
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => $nonce,
			] )
		);

		return $html;
	}

	/**
	 * Render top instructors section (admin only).
	 *
	 * @return string
	 */
	private function render_top_instructors_section(): string {
		try {
			$lp_statistic_db = LP_Statistics_DB::getInstance();
			$instructors     = $lp_statistic_db->get_top_instructors( 4 );
		} catch ( Throwable $e ) {
			$instructors = [];
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		$items_html = '';
		if ( empty( $instructors ) ) {
			$items_html = '<div class="no-data">' . esc_html__( 'No instructors found', 'learnpress' ) . '</div>';
		} else {
			foreach ( $instructors as $instructor ) {
				$avatar = get_avatar( $instructor->instructor_id, 40 );
				$items_html .= sprintf(
					'<div class="instructor-item">
						<div class="instructor-item__avatar">%s</div>
						<div class="instructor-item__info">
							<span class="instructor-item__name">%s</span>
							<span class="instructor-item__meta">%s &middot; %s</span>
						</div>
					</div>',
					$avatar,
					esc_html( $instructor->instructor_name ),
					sprintf(
						/* translators: %s: number of courses */
						esc_html( _n( '%s course', '%s courses', $instructor->course_count, 'learnpress' ) ),
						number_format_i18n( $instructor->course_count )
					),
					sprintf(
						/* translators: %s: number of students */
						esc_html( _n( '%s student', '%s students', $instructor->student_count, 'learnpress' ) ),
						number_format_i18n( $instructor->student_count )
					)
				);
			}
		}

		return sprintf(
			'<div class="lp-cb-dashboard__top-instructors">
				<div class="top-instructors__header">
					<h3 class="top-instructors__title">
						%s
					</h3>
				</div>
				<div class="top-instructors__list">%s</div>
			</div>',
			esc_html__( 'Top Instructors', 'learnpress' ),
			$items_html
		);
	}

	/**
	 * Render top courses section.
	 *
	 * @param int $user_id
	 * @return string
	 */
	private function render_top_courses_section( int $user_id ): string {
		$top_enrolled_html = $this->render_top_enrolled_courses( $user_id );
		$top_selling_html  = $this->render_top_selling_courses( $user_id );

		return Template::combine_components( [
			'wrapper'      => '<div class="lp-cb-dashboard__top-courses-wrapper">',
			'top_enrolled' => $top_enrolled_html,
			'top_selling'  => $top_selling_html,
			'wrapper_end'  => '</div>',
		] );
	}

	/**
	 * Render top enrolled courses with rich card layout.
	 *
	 * @param int $user_id
	 * @return string
	 */
	private function render_top_enrolled_courses( int $user_id ): string {
		try {
			$lp_statistic_db = LP_Statistics_DB::getInstance();
			$top_courses     = $lp_statistic_db->get_top_enrolled_courses_by_instructor( $user_id, 3 );
		} catch ( Throwable $e ) {
			$top_courses = [];
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		$total_enrolled = 0;
		$items_html     = '';

		if ( empty( $top_courses ) ) {
			$items_html = '<div class="no-data">' . esc_html__( 'No enrollment data available', 'learnpress' ) . '</div>';
		} else {
			foreach ( $top_courses as $course ) {
				$total_enrolled += intval( $course->enrollment_count );
				$thumbnail = get_the_post_thumbnail( $course->course_id, 'thumbnail' );
				if ( empty( $thumbnail ) ) {
					$thumbnail = '<div class="course-item__thumb-placeholder"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="24" height="24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg></div>';
				}

				$categories = wp_get_post_terms( $course->course_id, 'course_category', array( 'fields' => 'names' ) );
				$category_text = '';
				if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
					$category_text = sprintf( ' %s <span class="category">%s</span>', esc_html__( 'in', 'learnpress' ), esc_html( implode( ', ', $categories ) ) );
				}

				$items_html .= sprintf(
					'<div class="course-item">
						<div class="course-item__thumb">%s</div>
						<div class="course-item__info">
							<a href="%s" class="course-item__title">%s</a>
							<span class="course-item__meta">%s <span class="author">%s</span>%s</span>
						</div>
						<div class="course-item__badge-wrapper">
							<span class="course-item__badge">
								%s
							</span>
						</div>
					</div>',
					$thumbnail,
					esc_url( get_permalink( $course->course_id ) ),
					esc_html( $course->course_name ),
					esc_html__( 'by', 'learnpress' ),
					esc_html( $course->instructor_name ?? '' ),
					$category_text,
					sprintf(
						/* translators: %s: number of students */
						esc_html( _n( '%s student', '%s students', $course->enrollment_count, 'learnpress' ) ),
						number_format_i18n( $course->enrollment_count )
					)
				);
			}
		}

		return sprintf(
			'<div class="lp-cb-dashboard__top-courses lp-cb-dashboard__top-enrolled">
				<div class="top-courses__header">
					<h3 class="top-courses__title">%s</h3>
					<span class="top-courses__total">%s <strong class="enrolled-students-total">%s</strong> %s</span>
				</div>
				<div class="top-courses__list">%s</div>
			</div>',
			esc_html__( 'Top Enrolled Courses', 'learnpress' ),
			esc_html__( 'Total:', 'learnpress' ),
			esc_html( number_format_i18n( $total_enrolled ) ),
			esc_html__( 'enrolled students', 'learnpress' ),
			$items_html
		);
	}

	/**
	 * Render top selling courses with rich card layout.
	 *
	 * @param int $user_id
	 * @return string
	 */
	private function render_top_selling_courses( int $user_id ): string {
		try {
			$lp_statistic_db = LP_Statistics_DB::getInstance();
			$top_courses     = $lp_statistic_db->get_top_sold_courses_by_instructor( $user_id, 3 );
		} catch ( Throwable $e ) {
			$top_courses = [];
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		$total_revenue = 0;
		$items_html    = '';

		if ( empty( $top_courses ) ) {
			$items_html = '<div class="no-data">' . esc_html__( 'No sales data available', 'learnpress' ) . '</div>';
		} else {
			foreach ( $top_courses as $course ) {
				$total_revenue += floatval( $course->total_revenue ?? 0 );
				$thumbnail = get_the_post_thumbnail( $course->course_id, 'thumbnail' );
				if ( empty( $thumbnail ) ) {
					$thumbnail = '<div class="course-item__thumb-placeholder"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="24" height="24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg></div>';
				}

				$currency_symbol = function_exists( 'learn_press_get_currency_symbol' ) ? learn_press_get_currency_symbol() : '$';
				
				$lp_course = function_exists( 'learn_press_get_course' ) ? learn_press_get_course( $course->course_id ) : false;
				$price_html = '';
				if ( $lp_course ) {
					$price = $lp_course->get_price();
					if ( $price > 0 ) {
						$price_html = learn_press_format_price( $price, true );
					} else {
						$price_html = esc_html__( 'Free', 'learnpress' );
					}
				}

				$items_html .= sprintf(
					'<div class="course-item">
						<div class="course-item__thumb">%s</div>
						<div class="course-item__info">
							<a href="%s" class="course-item__title">%s</a>
							<span class="course-item__meta">%s <span class="author">%s</span> &bull; %s</span>
							<span class="course-item__price">%s</span>
						</div>
						<div class="course-item__stats">
							<div class="course-item__revenue">%s: <strong class="revenue-amount">%s%s</strong></div>
							<div class="course-item__sold">%s %s</div>
						</div>
					</div>',
					$thumbnail,
					esc_url( get_permalink( $course->course_id ) ),
					esc_html( $course->course_name ),
					esc_html__( 'Instructor:', 'learnpress' ),
					esc_html( $course->instructor_name ?? '' ),
					sprintf(
						/* translators: %s: number of students */
						esc_html( _n( '%s student', '%s students', $course->course_count, 'learnpress' ) ),
						number_format_i18n( $course->course_count )
					),
					$price_html,
					esc_html__( 'Revenue', 'learnpress' ),
					esc_html( $currency_symbol ),
					esc_html( number_format_i18n( $course->total_revenue ?? 0, 2 ) ),
					esc_html( number_format_i18n( $course->course_count ) ),
					esc_html__( 'sold', 'learnpress' )
				);
			}
		}

		$currency_symbol = function_exists( 'learn_press_get_currency_symbol' ) ? learn_press_get_currency_symbol() : '$';

		return sprintf(
			'<div class="lp-cb-dashboard__top-courses lp-cb-dashboard__top-selling">
				<div class="top-courses__header">
					<h3 class="top-courses__title">%s</h3>
					<span class="top-courses__total">%s <strong class="revenue-total">%s%s</strong> %s</span>
				</div>
				<div class="top-courses__list">%s</div>
			</div>',
			esc_html__( 'Top Selling Courses', 'learnpress' ),
			esc_html__( 'Total:', 'learnpress' ),
			esc_html( $currency_symbol ),
			esc_html( number_format_i18n( $total_revenue, 2 ) ),
			esc_html__( 'revenue', 'learnpress' ),
			$items_html
		);
	}

	/**
	 * Render quick action buttons.
	 *
	 * @return string
	 */
	private function render_quick_actions(): string {
		$actions = [
			[
				'label' => __( 'Create Course', 'learnpress' ),
				'url'   => CourseBuilder::get_tab_link( 'courses', CourseBuilder::POST_NEW, 'overview' ),
				'color' => '#ef4444',
				'svg'   => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_5791_4587)"><path fill-rule="evenodd" clip-rule="evenodd" d="M3 4C3 3.46957 3.21071 2.96086 3.58579 2.58579C3.96086 2.21072 4.46957 2 5 2H7C7.364 2 7.706 2.097 8 2.268C8.30385 2.09201 8.64886 1.99954 9 2H11C11.727 2 12.364 2.388 12.714 2.969C12.924 2.801 13.17 2.673 13.446 2.599L15.378 2.082C15.6317 2.01394 15.8963 1.99653 16.1568 2.03077C16.4172 2.065 16.6684 2.1502 16.8959 2.28151C17.1234 2.41281 17.3228 2.58765 17.4828 2.79604C17.6427 3.00442 17.76 3.24227 17.828 3.496L21.968 18.951C22.0361 19.2047 22.0535 19.4694 22.0192 19.7298C21.985 19.9902 21.8998 20.2414 21.7685 20.4689C21.6372 20.6964 21.4624 20.8958 21.254 21.0558C21.0456 21.2157 20.8077 21.333 20.554 21.401L18.622 21.918C18.3683 21.9861 18.1037 22.0035 17.8432 21.9692C17.5828 21.935 17.3316 21.8498 17.1041 21.7185C16.8766 21.5872 16.6772 21.4124 16.5172 21.204C16.3573 20.9956 16.24 20.7577 16.172 20.504L13 8.663V20C13 20.5304 12.7893 21.0391 12.4142 21.4142C12.0391 21.7893 11.5304 22 11 22H9C8.64886 22.0005 8.30385 21.908 8 21.732C7.69615 21.908 7.35114 22.0005 7 22H5C4.46957 22 3.96086 21.7893 3.58579 21.4142C3.21071 21.0391 3 20.5304 3 20V4ZM5 4H7V20H5V4ZM11 20H9V4H11V20ZM13.964 4.531L15.895 4.014L20.037 19.469L18.105 19.986L13.964 4.531Z" fill="currentColor"/></g><defs><clipPath id="clip0_5791_4587"><rect width="24" height="24" fill="white"/></clipPath></defs></svg>',
			],
			[
				'label' => __( 'Create Lesson', 'learnpress' ),
				'attr'  => 'data-add-new-lesson',
				'color' => '#7067ED',
				'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
			],
			[
				'label' => __( 'Create Quiz', 'learnpress' ),
				'url'   => CourseBuilder::get_tab_link( 'quizzes', CourseBuilder::POST_NEW, 'overview' ),
				'color' => '#f59e0b',
				'svg'  => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7 3.5C7 1.56772 8.56772 0 10.5 0C12.4323 0 14 1.56772 14 3.5V4H17C18.6523 4 20 5.34772 20 7V10H20.5C22.4323 10 24 11.5677 24 13.5C24 15.4323 22.4323 17 20.5 17H20V20C20 21.6523 18.6523 23 17 23H12.2V20.5C12.2 19.5623 11.4377 18.8 10.5 18.8C9.56229 18.8 8.8 19.5623 8.8 20.5V23H4C2.34772 23 1 21.6523 1 20V15.2H3.5C4.43771 15.2 5.2 14.4377 5.2 13.5C5.2 12.5623 4.43772 11.8 3.5 11.8H1.01V7C1.01 5.35425 2.3412 4 4 4H7V3.5ZM10.5 2C9.67229 2 9 2.67228 9 3.5V6H4C3.4588 6 3.01 6.44575 3.01 7V9.8H3.5C5.54228 9.8 7.2 11.4577 7.2 13.5C7.2 15.5423 5.54228 17.2 3.5 17.2H3V20C3 20.5477 3.45228 21 4 21H6.8V20.5C6.8 18.4577 8.45771 16.8 10.5 16.8C12.5423 16.8 14.2 18.4577 14.2 20.5V21H17C17.5477 21 18 20.5477 18 20V15H20.5C21.3277 15 22 14.3277 22 13.5C22 12.6723 21.3277 12 20.5 12H18V7C18 6.45228 17.5477 6 17 6H12V3.5C12 2.67228 11.3277 2 10.5 2Z" fill=""/></svg>',
			],
			[
				'label' => __( 'Create Question', 'learnpress' ),
				'url'   => CourseBuilder::get_tab_link( 'questions', CourseBuilder::POST_NEW, 'overview' ),
				'color' => '#6b7280',
				'svg'  => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C17.523 2 22 6.477 22 12C22 17.523 17.523 22 12 22C6.477 22 2 17.523 2 12C2 6.477 6.477 2 12 2ZM12 4C9.87827 4 7.84344 4.84285 6.34315 6.34315C4.84285 7.84344 4 9.87827 4 12C4 14.1217 4.84285 16.1566 6.34315 17.6569C7.84344 19.1571 9.87827 20 12 20C14.1217 20 16.1566 19.1571 17.6569 17.6569C19.1571 16.1566 20 14.1217 20 12C20 9.87827 19.1571 7.84344 17.6569 6.34315C16.1566 4.84285 14.1217 4 12 4ZM12 16C12.2652 16 12.5196 16.1054 12.7071 16.2929C12.8946 16.4804 13 16.7348 13 17C13 17.2652 12.8946 17.5196 12.7071 17.7071C12.5196 17.8946 12.2652 18 12 18C11.7348 18 11.4804 17.8946 11.2929 17.7071C11.1054 17.5196 11 17.2652 11 17C11 16.7348 11.1054 16.4804 11.2929 16.2929C11.4804 16.1054 11.7348 16 12 16ZM12 6.5C12.8423 6.50003 13.6583 6.79335 14.3078 7.3296C14.9573 7.86585 15.3998 8.61154 15.5593 9.43858C15.7188 10.2656 15.5853 11.1224 15.1818 11.8617C14.7783 12.601 14.1299 13.1768 13.348 13.49C13.2328 13.5337 13.1286 13.6024 13.043 13.691C12.999 13.741 12.992 13.805 12.993 13.871L13 14C12.9997 14.2549 12.9021 14.5 12.7272 14.6854C12.5522 14.8707 12.313 14.9822 12.0586 14.9972C11.8042 15.0121 11.5536 14.9293 11.3582 14.7657C11.1627 14.6021 11.0371 14.3701 11.007 14.117L11 14V13.75C11 12.597 11.93 11.905 12.604 11.634C12.8783 11.5245 13.1176 11.3423 13.2962 11.107C13.4748 10.8717 13.5859 10.5922 13.6176 10.2986C13.6493 10.0049 13.6004 9.70813 13.4762 9.44014C13.352 9.17215 13.1571 8.94307 12.9125 8.77748C12.6679 8.61189 12.3829 8.51606 12.0879 8.50027C11.793 8.48448 11.4993 8.54934 11.2384 8.68787C10.9775 8.8264 10.7593 9.03338 10.6072 9.28658C10.4551 9.53978 10.3748 9.82962 10.375 10.125C10.375 10.3902 10.2696 10.6446 10.0821 10.8321C9.89457 11.0196 9.64022 11.125 9.375 11.125C9.10978 11.125 8.85543 11.0196 8.66789 10.8321C8.48036 10.6446 8.375 10.3902 8.375 10.125C8.375 9.16359 8.75692 8.24156 9.43674 7.56174C10.1166 6.88192 11.0386 6.5 12 6.5Z" fill=""/></svg>',
			],
		];

		$buttons_html = '';
		foreach ( $actions as $action ) {
			if ( ! empty( $action['attr'] ) ) {
				// Render as button with data attribute (opens popup)
				$buttons_html .= sprintf(
					'<button type="button" %s class="quick-action__btn" style="--action-color: %s; --action-bg: %s10">
						<span class="quick-action__icon">%s</span>
						<span class="quick-action__label">%s</span>
					</button>',
					esc_attr( $action['attr'] ),
					esc_attr( $action['color'] ),
					esc_attr( $action['color'] ),
					$action['svg'],
					esc_html( $action['label'] )
				);
			} else {
				// Render as link
				$buttons_html .= sprintf(
					'<a href="%s" class="quick-action__btn" style="--action-color: %s; --action-bg: %s10">
						<span class="quick-action__icon">%s</span>
						<span class="quick-action__label">%s</span>
					</a>',
					esc_url( $action['url'] ),
					esc_attr( $action['color'] ),
					esc_attr( $action['color'] ),
					$action['svg'],
					esc_html( $action['label'] )
				);
			}
		}

		return sprintf(
			'<div class="lp-cb-dashboard__quick-actions">
				<h3 class="quick-actions__title">%s</h3>
				<div class="quick-actions__grid">%s</div>
			</div>',
			esc_html__( 'Quick Action', 'learnpress' ),
			$buttons_html
		);
	}

	/**
	 * Render recent courses section using course items from Courses tab.
	 *
	 * @param UserModel $user
	 * @return string
	 */
	private function render_recent_courses_section( UserModel $user ): string {
		$content = '';

		try {
			$filter              = new LP_Course_Filter();
			$filter->limit       = 3;
			$filter->order_by    = 'post_date';
			$filter->order       = 'DESC';
			$filter->post_status = [ 'publish', 'pending', 'draft', 'private' ];

			if ( ! user_can( $user->get_id(), 'administrator' ) ) {
				$filter->post_author = $user->get_id();
			}

			$total_courses = 0;
			$courses       = \LearnPress\Models\Courses::get_courses( $filter, $total_courses );

			$list_html = '';
			if ( ! empty( $courses ) ) {
				$html_list_course = '';
				foreach ( $courses as $course_obj ) {
					$course = \LearnPress\Models\CourseModel::find( $course_obj->ID, true );
					if ( $course ) {
						// Reuse course item from Courses tab
						$html_list_course .= BuilderTabCourseTemplate::render_course( $course );
					}
				}

				if ( ! empty( $html_list_course ) ) {
					$list_html = Template::combine_components( [
						'wrapper'     => '<div class="courses-builder__course-tab learn-press-courses"><ul class="cb-list-course">',
						'list_course' => $html_list_course,
						'wrapper_end' => '</ul></div>',
					] );
				}
			}

			if ( empty( $list_html ) ) {
				$list_html = '<div class="no-data">' . esc_html__( 'No recent courses found', 'learnpress' ) . '</div>';
			}

			$content = sprintf(
				'<div class="lp-cb-dashboard__recent-courses" style="margin-top: 30px;">
					<div class="recent-courses__header">
						<h3 class="recent-courses__title">%s</h3>
					</div>
					<div class="recent-courses__list">%s</div>
				</div>',
				esc_html__( 'Recent Courses', 'learnpress' ),
				$list_html
			);
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}
}
