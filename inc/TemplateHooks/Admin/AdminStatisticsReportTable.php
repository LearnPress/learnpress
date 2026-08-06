<?php

namespace LearnPress\TemplateHooks\Admin;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Statistics\DashboardStatisticsDB;
use LearnPress\Statistics\InstructorStatisticsProvider;
use LearnPress\Statistics\OrderExceptionsProvider;
use LearnPress\Statistics\PeriodHelper;
use LearnPress\Statistics\PeriodResolver;
use LearnPress\Statistics\StatisticsScope;
use LearnPress\TemplateHooks\Table\TableListTemplate;
use LP_Debug;
use LP_Helper;
use stdClass;
use Throwable;
use Exception;

defined( 'ABSPATH' ) || exit();

/**
 * Server-rendered statistics report tables.
 *
 * Replaces the JS-rendered report-popup tables with the author's
 * TableListTemplate, delivered/paginated through TemplateAJAX + loadAJAX.js
 * (same pattern as AdminListStudentsEnrolled). The heavy lifting — filtered,
 * paginated, searchable queries — reuses the DashboardStatisticsDB /
 * provider methods; this class only shapes rows into the table markup.
 *
 * @since 4.4.2
 * @version 1.0.0
 */
class AdminStatisticsReportTable {
	use Singleton;

	const PER_PAGE = 20;

	public function init() {
		add_filter( 'lp/rest/ajax/allow_callback', array( $this, 'allow_callback' ) );
	}

	/**
	 * Whitelist the AJAX render callbacks.
	 *
	 * @param array $callbacks
	 * @return array
	 */
	public function allow_callback( array $callbacks ): array {
		$callbacks[] = self::class . ':render_report_table';
		$callbacks[] = self::class . ':render_report_csv';

		return $callbacks;
	}

	/**
	 * Row cap for the PHP-merged reports (top_courses / course_performance /
	 * instructor_report) and for CSV export.
	 *
	 * @return int
	 */
	private static function max_rows(): int {
		return (int) apply_filters( 'learn-press/statistics/report-max-rows', 2000 );
	}

	/**
	 * AJAX callback: render one page of a report as a TableListTemplate table.
	 *
	 * @param array $data Sent args ( report, filtertype, date, instructor_id, category_id, paged, search, ... ).
	 * @return stdClass { content }
	 */
	public static function render_report_table( array $data ): stdClass {
		$content          = new stdClass();
		$content->content = '';

		try {
			self::guard_permission();

			$self     = self::instance();
			$report   = sanitize_key( $data['report'] ?? '' );
			$paged    = max( 1, absint( $data['paged'] ?? 1 ) );
			$search   = trim( (string) LP_Helper::sanitize_params_submitted( $data['search'] ?? '' ) );
			$per_page = self::PER_PAGE;
			$total    = 0;

			$rows = $self->fetch_rows( $report, $data, $paged, $per_page, $total );
			/**
			 * Filter the fetched report rows before the table / CSV is built.
			 *
			 * @param array  $rows   Row data for the current page.
			 * @param string $report Report id.
			 * @param array  $data   Sent args.
			 * @since 4.4.2
			 */
			$rows    = (array) apply_filters( 'learn-press/statistics/report/rows', $rows, $report, $data );
			$columns = $self->columns_for( $report, $rows );
			/**
			 * Filter the report column specs before the table / CSV is built.
			 *
			 * Each column: [ 'label', 'class'?, 'key'?, 'render'?( row ):html, 'csv'?( row ):string ].
			 * A 'render' callback owns its own escaping ( its return is echoed as cell HTML ).
			 *
			 * @param array  $columns Column specs.
			 * @param string $report  Report id.
			 * @param array  $rows    Current page rows.
			 * @since 4.4.2
			 */
			$columns = (array) apply_filters( 'learn-press/statistics/report/columns', $columns, $report, $rows );

			if ( empty( $columns ) ) {
				throw new Exception( esc_html__( 'Unknown report.', 'learnpress' ) );
			}

			$content->content = $self->capped_notice( $report, $total ) . $self->html_table( $report, $rows, $columns, $paged, $per_page, $total );
		} catch ( Throwable $e ) {
			$content->content = Template::print_message( $e->getMessage(), 'error', false );
			LP_Debug::error_log( $e );
		}

		return $content;
	}

	/**
	 * Notice shown when a PHP-merged report is truncated at max_rows(). Those
	 * reports ( top_courses / course_performance ) merge two query result sets
	 * in PHP and paginate the merged array, so rows beyond the cap are not
	 * reachable — surface that instead of silently hiding them. Empty string
	 * for SQL-paginated reports or when the cap was not hit.
	 *
	 * @param string $report
	 * @param int    $total
	 * @return string
	 * @since 4.4.2
	 */
	private function capped_notice( string $report, int $total ): string {
		$php_merged = array( 'top_courses', 'course_performance' );
		if ( ! in_array( $report, $php_merged, true ) || $total < self::max_rows() ) {
			return '';
		}

		$message = sprintf(
			/* translators: %d: maximum number of rows shown. */
			__( 'Showing the first %d rows. Narrow the period or filters to see the rest.', 'learnpress' ),
			self::max_rows()
		);

		return '<p class="lp-stats-report-capped">' . esc_html( $message ) . '</p>';
	}

	/**
	 * AJAX callback: build the full (capped) result set as a CSV string.
	 *
	 * @param array $data
	 * @return stdClass { csv, filename }
	 */
	public static function render_report_csv( array $data ): stdClass {
		$out           = new stdClass();
		$out->csv      = '';
		$out->filename = 'learnpress-report.csv';

		try {
			self::guard_permission();

			$self   = self::instance();
			$report = sanitize_key( $data['report'] ?? '' );
			$search = trim( (string) LP_Helper::sanitize_params_submitted( $data['search'] ?? '' ) );
			$total  = 0;

			// One page big enough to hold everything ( capped ).
			$rows = $self->fetch_rows( $report, $data, 1, self::max_rows(), $total );
			/** This filter is documented in inc/TemplateHooks/Admin/AdminStatisticsReportTable.php */
			$rows    = (array) apply_filters( 'learn-press/statistics/report/rows', $rows, $report, $data );
			$columns = $self->columns_for( $report, $rows );
			/** This filter is documented in inc/TemplateHooks/Admin/AdminStatisticsReportTable.php */
			$columns = (array) apply_filters( 'learn-press/statistics/report/columns', $columns, $report, $rows );

			if ( ! empty( $columns ) ) {
				$out->csv      = $self->build_csv( $columns, $rows );
				$out->filename = $self->csv_filename( $report, $data );
			}
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $out;
	}

	/**
	 * @throws Exception
	 */
	private static function guard_permission() {
		$allowed = apply_filters( 'learnpress/admin-statistics/permission', current_user_can( 'administrator' ) );
		if ( ! $allowed ) {
			throw new Exception( esc_html__( 'You do not have permission to view this report.', 'learnpress' ) );
		}
	}

	/**
	 * Map the request filtertype/date into [ filter_type, time ].
	 * Same PeriodResolver as LP_REST_Admin_Statistics_Controller::get_statistics_filter(),
	 * so report popups/CSV honor every preset — new and legacy — identically.
	 *
	 * @param array $data
	 * @return array
	 */
	private function resolve_filter( array $data ): array {
		$range = PeriodResolver::resolve(
			(string) ( $data['filtertype'] ?? 'today' ),
			(string) LP_Helper::sanitize_params_submitted( $data['date'] ?? '' )
		);

		return $range->legacy_pair();
	}

	/**
	 * Fetch one page of rows for a report ( total set by-ref ).
	 *
	 * @param string $report
	 * @param array  $data
	 * @param int    $paged
	 * @param int    $limit
	 * @param int    $total
	 * @return array
	 */
	private function fetch_rows( string $report, array $data, int $paged, int $limit, int &$total ): array {
		$filter = $this->resolve_filter( $data );
		$scope  = StatisticsScope::from_params( $data );
		$type   = $filter['filter_type'];
		$time   = (string) $filter['time'];
		$offset = ( $paged - 1 ) * $limit;
		$db     = DashboardStatisticsDB::getInstance();
		$search = trim( (string) LP_Helper::sanitize_params_submitted( $data['search'] ?? '' ) );

		/**
		 * Filter the report query args before fetching a page of rows.
		 *
		 * Bounded scalars only ( no raw SQL ); every value is re-sanitized below
		 * so a handler cannot smuggle unsafe input. Use to change page size,
		 * inject a default search term, or repoint the report.
		 *
		 * @param array  $args   [ report, paged, limit, search ].
		 * @param string $report Report id.
		 * @param array  $data   Raw sent args.
		 * @since 4.4.2
		 */
		$args = apply_filters(
			'learn-press/statistics/report/query-args',
			array(
				'report' => $report,
				'paged'  => $paged,
				'limit'  => $limit,
				'search' => $search,
			),
			$report,
			$data
		);

		$report = sanitize_key( $args['report'] ?? $report );
		$paged  = max( 1, absint( $args['paged'] ?? $paged ) );
		$limit  = max( 1, absint( $args['limit'] ?? $limit ) );
		$search = trim( (string) ( $args['search'] ?? $search ) );
		$offset = ( $paged - 1 ) * $limit; // Derived from the resolved paged/limit so it can never desync.

		switch ( $report ) {
			case 'top_courses':
				$all   = $db->get_top_courses_performance( $type, $time, $scope, self::max_rows(), $search );
				$total = count( $all );
				return $this->enrich_course_rows( $db, array_slice( $all, $offset, $limit ) );

			case 'course_performance':
				$all   = $db->get_top_courses_performance( $type, $time, $scope, self::max_rows(), $search );
				$total = count( $all );
				return $this->format_course_performance_rows( $db, array_slice( $all, $offset, $limit ) );

			case 'top_sold_courses':
				$total = $db->count_top_sold_courses( $type, $time, $scope, $search );
				$sold  = array_map(
					function ( $row ) {
						$row['revenue_formatted'] = html_entity_decode( learn_press_format_price( $row['revenue'] ) );
						$row['aov_formatted']     = null !== $row['aov'] ? html_entity_decode( learn_press_format_price( $row['aov'] ) ) : null;
						return $row;
					},
					$db->get_top_sold_courses_detailed( $type, $time, $scope, $limit, $offset, $search )
				);
				return $this->attach_revenue_trend( $db, $filter, $scope, $sold );

			case 'exceptions':
				$status   = sanitize_key( $data['order_status'] ?? '' );
				$provider = OrderExceptionsProvider::getInstance();
				$total    = $provider->count_exceptions( $type, $time, $scope, $search, $status );
				return $provider->get_exceptions( $type, $time, $scope, $limit, $offset, $search, $status );

			case 'top_students':
				$total = $db->count_top_students( $type, $time, $scope, $search );
				return $db->get_top_students( $type, $time, $scope, $limit, $offset, $search );

			case 'courses_by_students':
				$total = $db->count_courses_by_students( $type, $time, $scope, $search );
				return $db->get_courses_by_students( $type, $time, $scope, $limit, $offset, $search );

			case 'instructor_performance':
				$total = $db->count_instructor_performance( $type, $time, $scope, $search );
				return InstructorStatisticsProvider::format_performance(
					$db->get_instructor_performance( $type, $time, $scope, $limit, $offset, $search )
				);

			case 'instructor_report':
				$instructor_id = absint( $data['instructor_id'] ?? 0 );
				$report_data   = InstructorStatisticsProvider::get_report( $instructor_id, $paged, $limit, $search );
				$total         = (int) ( $report_data['total'] ?? 0 );
				return $report_data['rows'] ?? array();

			default:
				$total = 0;
				return array();
		}
	}

	/**
	 * Shape merged course-performance rows for the Courses report ( name,
	 * instructor, revenue, enrollments, completion ). Self-contained so the AJAX
	 * router never depends on the REST controller being loaded.
	 *
	 * @param DashboardStatisticsDB $db
	 * @param array                 $rows From get_top_courses_performance().
	 * @return array
	 */
	private function format_course_performance_rows( DashboardStatisticsDB $db, array $rows ): array {
		$course_ids  = array_map(
			function ( $row ) {
				return absint( $row['course_id'] ?? 0 );
			},
			$rows
		);
		$instructors = $db->get_course_instructor_names( $course_ids );
		$categories  = $db->get_course_category_names( $course_ids );

		return array_map(
			function ( $row ) use ( $instructors, $categories ) {
				$course_id = absint( $row['course_id'] ?? 0 );
				$revenue   = (float) ( $row['revenue'] ?? 0 );
				$cats      = $categories[ $course_id ] ?? array();

				return array(
					'course_id'         => $course_id,
					'name'              => (string) ( $row['course_name'] ?? '' ),
					'category'          => implode( ', ', $cats ), // all — CSV
					'category_primary'  => $cats[0] ?? '',         // first — table cell
					'instructor'        => $instructors[ $course_id ] ?? '',
					'revenue'           => $revenue,
					'revenue_formatted' => html_entity_decode( learn_press_format_price( $revenue ) ),
					'orders'            => (int) ( $row['order_count'] ?? 0 ),
					'enrollments'       => (int) ( $row['enrolled'] ?? 0 ),
					'completed'         => (int) ( $row['completed'] ?? 0 ),
					'completion_rate'   => $row['completion_rate'] ?? null,
					'edit_link'         => $course_id > 0 ? (string) get_edit_post_link( $course_id, 'raw' ) : '',
				);
			},
			$rows
		);
	}

	/**
	 * Attach instructor + category names and formatted revenue to raw
	 * top-course rows ( keys kept: course_name/order_count/enrolled/... ).
	 *
	 * @param DashboardStatisticsDB $db
	 * @param array                 $rows From get_top_courses_performance().
	 * @return array
	 */
	private function enrich_course_rows( DashboardStatisticsDB $db, array $rows ): array {
		$course_ids  = array_map(
			function ( $row ) {
				return absint( $row['course_id'] ?? 0 );
			},
			$rows
		);
		$instructors = $db->get_course_instructor_names( $course_ids );
		$categories  = $db->get_course_category_names( $course_ids );

		return array_map(
			function ( $row ) use ( $instructors, $categories ) {
				$course_id                = absint( $row['course_id'] ?? 0 );
				$cats                     = $categories[ $course_id ] ?? array();
				$row['instructor']        = $instructors[ $course_id ] ?? '';
				$row['category']          = implode( ', ', $cats ); // all — CSV
				$row['category_primary']  = $cats[0] ?? '';         // first — table cell
				$row['revenue_formatted'] = html_entity_decode( learn_press_format_price( $row['revenue'] ?? 0 ) );
				return $row;
			},
			$rows
		);
	}

	/**
	 * Add a revenue "trend" ( vs the equivalent previous period ) to sold-course
	 * rows. One extra query for the page's course IDs; no-op on an empty page.
	 *
	 * @param DashboardStatisticsDB $db
	 * @param array                 $filter [ filter_type, time ] for the current range.
	 * @param StatisticsScope|null  $scope
	 * @param array                 $rows
	 * @return array
	 */
	private function attach_revenue_trend( DashboardStatisticsDB $db, array $filter, ?StatisticsScope $scope, array $rows ): array {
		if ( empty( $rows ) ) {
			return $rows;
		}

		$prev     = PeriodHelper::get_previous_filter( $filter );
		$prev_map = array();
		if ( $prev ) {
			$course_ids = array_map(
				function ( $row ) {
					return absint( $row['course_id'] ?? 0 );
				},
				$rows
			);
			$prev_map   = $db->get_course_revenue_totals(
				(string) ( $prev['filter_type'] ?? '' ),
				(string) ( $prev['time'] ?? '' ),
				$scope,
				$course_ids
			);
		}

		return array_map(
			function ( $row ) use ( $prev_map ) {
				$current          = (float) ( $row['revenue'] ?? 0 );
				$previous         = (float) ( $prev_map[ absint( $row['course_id'] ?? 0 ) ] ?? 0 );
				$row['trend']     = $this->trend_direction( $current, $previous );
				$row['trend_pct'] = $this->trend_pct( $current, $previous );
				return $row;
			},
			$rows
		);
	}

	/**
	 * @param float $current
	 * @param float $previous
	 * @return string up|down|flat
	 */
	private function trend_direction( float $current, float $previous ): string {
		if ( $current > $previous ) {
			return 'up';
		}
		if ( $current < $previous ) {
			return 'down';
		}

		return 'flat';
	}

	/**
	 * @param float $current
	 * @param float $previous
	 * @return float|null Percentage change, or null when there is no prior baseline.
	 */
	private function trend_pct( float $current, float $previous ) {
		if ( $previous <= 0 ) {
			return null;
		}

		return round( ( $current - $previous ) / $previous * 100, 1 );
	}

	/**
	 * @param array $row
	 * @return string HTML trend badge ( arrow + % ).
	 */
	private function trend_cell( array $row ): string {
		$dir    = (string) ( $row['trend'] ?? 'flat' );
		$pct    = $row['trend_pct'] ?? null;
		$arrows = array(
			'up'   => '▲',
			'down' => '▼',
			'flat' => '—',
		);
		$colors = array(
			'up'   => 'green',
			'down' => 'red',
			'flat' => 'grey',
		);
		$arrow  = $arrows[ $dir ] ?? '—';
		$color  = $colors[ $dir ] ?? 'grey';

		if ( null !== $pct ) {
			$label = $arrow . ' ' . abs( $pct ) . '%';
		} elseif ( 'up' === $dir ) {
			$label = $arrow . ' ' . __( 'New', 'learnpress' );
		} else {
			$label = $arrow;
		}

		return $this->badge( $color, $label );
	}

	/**
	 * @param array $row
	 * @return string CSV representation of the trend.
	 */
	private function trend_csv( array $row ): string {
		$pct = $row['trend_pct'] ?? null;
		if ( null === $pct ) {
			return 'up' === ( $row['trend'] ?? '' ) ? __( 'New', 'learnpress' ) : '';
		}

		return $pct . '%';
	}

	/**
	 * Column specs for a report. Each column:
	 *   [ 'label', 'class'?, 'key'?, 'render'?( row ):html, 'csv'?( row ):string ]
	 *
	 * @param string $report
	 * @param array  $rows Current page ( used for conditional columns ).
	 * @return array
	 */
	private function columns_for( string $report, array $rows ): array {
		$i18n = array(
			'course'          => __( 'Course', 'learnpress' ),
			'category'        => __( 'Category', 'learnpress' ),
			'instructor'      => __( 'Instructor', 'learnpress' ),
			'revenue'         => __( 'Revenue', 'learnpress' ),
			'total_revenue'   => __( 'Total revenue', 'learnpress' ),
			'orders'          => __( 'Orders', 'learnpress' ),
			'enrolled'        => __( 'Enrolled', 'learnpress' ),
			'enrollments'     => __( 'Enrollments', 'learnpress' ),
			'completion'      => __( 'Completion', 'learnpress' ),
			'avg_completion'  => __( 'Avg completion', 'learnpress' ),
			'completed'       => __( 'Completed', 'learnpress' ),
			'started'         => __( 'Started', 'learnpress' ),
			'active_7d'       => __( 'Active 7d', 'learnpress' ),
			'student'         => __( 'Student', 'learnpress' ),
			'students'        => __( 'Students', 'learnpress' ),
			'active_students' => __( 'Active students', 'learnpress' ),
			'courses'         => __( 'Courses', 'learnpress' ),
			'courses_managed' => __( 'Courses managed', 'learnpress' ),
			'status'          => __( 'Status', 'learnpress' ),
			'trend'           => __( 'Trend', 'learnpress' ),
			'aov'             => __( 'AOV', 'learnpress' ),
			'order_id'        => __( 'Order ID', 'learnpress' ),
			'issue'           => __( 'Issue', 'learnpress' ),
			'date'            => __( 'Date', 'learnpress' ),
			'severity'        => __( 'Severity', 'learnpress' ),
			'avg_score'       => __( 'Quiz pass rate', 'learnpress' ),
			'last_active'     => __( 'Last active', 'learnpress' ),
			'sold'            => __( 'Sold', 'learnpress' ),
		);

		$col_text       = function ( $key, $label ) {
			return array(
				'label' => $label,
				'key'   => $key,
			);
		};
		$col_revenue    = function ( $label = null ) use ( $i18n ) {
			return array(
				'label'  => $label ?: $i18n['revenue'],
				'render' => function ( $row ) {
					return esc_html( (string) ( $row['revenue_formatted'] ?? '' ) );
				},
				'csv'    => function ( $row ) {
					return (string) ( $row['revenue'] ?? 0 );
				},
			);
		};
		$col_completion = function ( $key, $label ) {
			return array(
				'label'  => $label,
				'render' => function ( $row ) use ( $key ) {
					return $this->completion_cell( $row[ $key ] ?? null );
				},
				'csv'    => function ( $row ) use ( $key ) {
					$val = $row[ $key ] ?? null;
					return null === $val ? '' : $val . '%';
				},
			);
		};
		// Table shows only the primary category; CSV exports every category.
		$col_category = function () use ( $i18n ) {
			return array(
				'label'  => $i18n['category'],
				'render' => function ( $row ) {
					return esc_html( (string) ( $row['category_primary'] ?? '' ) );
				},
				'csv'    => function ( $row ) {
					return (string) ( $row['category'] ?? '' );
				},
			);
		};

		switch ( $report ) {
			case 'top_courses':
				return array(
					$col_text( 'course_name', $i18n['course'] ),
					$col_category(),
					$col_text( 'instructor', $i18n['instructor'] ),
					$col_revenue(),
					$col_text( 'order_count', $i18n['orders'] ),
					$col_text( 'enrolled', $i18n['enrollments'] ),
					$col_completion( 'completion_rate', $i18n['completion'] ),
				);

			case 'course_performance':
				return array(
					$col_text( 'name', $i18n['course'] ),
					$col_category(),
					$col_text( 'instructor', $i18n['instructor'] ),
					$col_revenue(),
					$col_text( 'orders', $i18n['orders'] ),
					$col_text( 'enrollments', $i18n['enrollments'] ),
					$col_completion( 'completion_rate', $i18n['completion'] ),
				);

			case 'top_sold_courses':
				return array(
					$col_text( 'name', $i18n['course'] ),
					$col_revenue(),
					$col_text( 'orders', $i18n['orders'] ),
					array(
						'label'  => $i18n['aov'],
						'render' => function ( $row ) {
							return esc_html( (string) ( $row['aov_formatted'] ?? '' ) );
						},
						'csv'    => function ( $row ) {
							return (string) ( $row['aov'] ?? '' );
						},
					),
					array(
						'label'  => $i18n['status'],
						'render' => function ( $row ) {
							$slug = (string) ( $row['status_label'] ?? '' );
							return $this->badge( $this->sold_status_color( $slug ), $this->sold_status_label( $slug ) );
						},
						'csv'    => function ( $row ) {
							return $this->sold_status_label( (string) ( $row['status_label'] ?? '' ) );
						},
					),
					array(
						'label'  => $i18n['trend'],
						'render' => function ( $row ) {
							return $this->trend_cell( $row );
						},
						'csv'    => function ( $row ) {
							return $this->trend_csv( $row );
						},
					),
				);

			case 'exceptions':
				return array(
					array(
						'label'  => $i18n['order_id'],
						'render' => function ( $row ) {
							$id   = absint( $row['order_id'] ?? 0 );
							$link = (string) ( $row['edit_link'] ?? '' );
							if ( $link ) {
								return sprintf( '<a href="%s">#%d</a>', esc_url( $link ), $id );
							}
							return esc_html( (string) $id );
						},
						'csv'    => function ( $row ) {
							return (string) absint( $row['order_id'] ?? 0 );
						},
					),
					$col_text( 'student', $i18n['student'] ),
					$col_text( 'course', $i18n['course'] ),
					$col_text( 'issue', $i18n['issue'] ),
					$col_text( 'date', $i18n['date'] ),
					array(
						'label'  => $i18n['severity'],
						'render' => function ( $row ) {
							$slug = (string) ( $row['severity'] ?? '' );
							return $this->badge( $this->severity_color( $slug ), $this->severity_label( $slug ) );
						},
						'csv'    => function ( $row ) {
							return $this->severity_label( (string) ( $row['severity'] ?? '' ) );
						},
					),
				);

			case 'top_students':
				$columns = array(
					$col_text( 'name', $i18n['student'] ),
					$col_text( 'enrolled', $i18n['enrolled'] ),
					$col_text( 'completed', $i18n['completed'] ),
				);

				$has_scores = false;
				foreach ( $rows as $row ) {
					if ( null !== ( $row['avg_score'] ?? null ) ) {
						$has_scores = true;
						break;
					}
				}
				if ( $has_scores ) {
					$columns[] = array(
						'label'  => $i18n['avg_score'],
						'render' => function ( $row ) {
							$val = $row['avg_score'] ?? null;
							return null === $val ? '—' : esc_html( $val . '%' );
						},
						'csv'    => function ( $row ) {
							$val = $row['avg_score'] ?? null;
							return null === $val ? '' : $val . '%';
						},
					);
				}

				$columns[] = array(
					'label'  => $i18n['last_active'],
					'render' => function ( $row ) {
						return $this->last_active_cell( $row['last_active'] ?? '' );
					},
					'csv'    => function ( $row ) {
						return (string) ( $row['last_active'] ?? '' );
					},
				);
				$columns[] = array(
					'label'  => $i18n['status'],
					'render' => function ( $row ) {
						$slug = (string) ( $row['status'] ?? '' );
						return $this->badge( $this->student_status_color( $slug ), $this->student_status_label( $slug ) );
					},
					'csv'    => function ( $row ) {
						return $this->student_status_label( (string) ( $row['status'] ?? '' ) );
					},
				);

				return $columns;

			case 'courses_by_students':
				return array(
					$col_text( 'name', $i18n['course'] ),
					$col_text( 'enrolled', $i18n['enrolled'] ),
					$col_text( 'started', $i18n['started'] ),
					$col_text( 'completed', $i18n['completed'] ),
					$col_completion( 'completion_rate', $i18n['completion'] ),
					$col_text( 'active_7d', $i18n['active_7d'] ),
				);

			case 'instructor_performance':
				return array(
					$col_text( 'name', $i18n['instructor'] ),
					$col_text( 'courses', $i18n['courses_managed'] ),
					$col_text( 'students', $i18n['active_students'] ),
					$col_revenue( $i18n['total_revenue'] ),
					$col_completion( 'avg_completion', $i18n['avg_completion'] ),
				);

			case 'instructor_report':
				return array(
					$col_text( 'name', $i18n['course'] ),
					$col_text( 'sold', $i18n['sold'] ),
					$col_revenue(),
					$col_text( 'enrolled', $i18n['enrolled'] ),
				);

			default:
				return array();
		}
	}

	/**
	 * Assemble the TableListTemplate table + pagination footer.
	 *
	 * @param string $report
	 * @param array  $rows
	 * @param array  $columns
	 * @param int    $paged
	 * @param int    $per_page
	 * @param int    $total
	 * @return string
	 */
	private function html_table( string $report, array $rows, array $columns, int $paged, int $per_page, int $total ): string {
		$header = array();
		foreach ( $columns as $key => $col ) {
			$header[ $key ] = array(
				'class' => $col['class'] ?? '',
				'title' => $col['label'] ?? '',
			);
		}

		$rows_html = '';
		foreach ( $rows as $row ) {
			$tds = '';
			foreach ( $columns as $col ) {
				$tds .= sprintf( '<td>%s</td>', $this->cell_html( $col, $row ) );
			}
			$rows_html .= '<tr>' . $tds . '</tr>';
		}

		$footer = sprintf(
			'<div class="lp-stats-report-table-footer"><span class="lp-stats-report-table-footer__count">%s</span>%s</div>',
			TableListTemplate::instance()->html_page_result(
				array(
					'paged'      => $paged,
					'per_page'   => $per_page,
					'total_rows' => $total,
					'item_name'  => $this->item_name( $report, $total ),
				)
			),
			$this->html_pagination( $total, $paged, $per_page )
		);

		$table_args = array(
			'class_table' => 'lp-stats-report-table',
			'header'      => $header,
			'body'        => array( 'rows_html' => $rows_html ),
			'footer'      => empty( $rows ) ? '' : $footer,
		);

		return TableListTemplate::instance()->html_table( $table_args );
	}

	/**
	 * @param array $col
	 * @param array $row
	 * @return string HTML for a single cell.
	 */
	private function cell_html( array $col, array $row ): string {
		if ( isset( $col['render'] ) && is_callable( $col['render'] ) ) {
			return (string) call_user_func( $col['render'], $row );
		}

		$key = $col['key'] ?? '';
		return esc_html( (string) ( $row[ $key ] ?? '' ) );
	}

	/**
	 * @param int $total
	 * @param int $paged
	 * @param int $per_page
	 * @return string
	 */
	private function html_pagination( int $total, int $paged, int $per_page ): string {
		$total_pages = max( 1, ceil( $total / max( 1, $per_page ) ) );
		if ( $total_pages < 2 ) {
			return '';
		}

		return Template::instance()->html_pagination(
			array(
				'total_pages' => $total_pages,
				'paged'       => $paged,
				'wrapper'     => array(
					'<nav class="learn-press-pagination navigation pagination lp-pagination">' => '</nav>',
				),
			)
		);
	}

	/**
	 * @param string $report
	 * @param int    $total
	 * @return string
	 */
	private function item_name( string $report, int $total ): string {
		$course_reports = array( 'top_courses', 'course_performance', 'top_sold_courses', 'courses_by_students', 'instructor_report' );
		if ( in_array( $report, $course_reports, true ) ) {
			return _n( 'course', 'courses', $total, 'learnpress' );
		}
		if ( 'top_students' === $report ) {
			return _n( 'student', 'students', $total, 'learnpress' );
		}
		if ( 'instructor_performance' === $report ) {
			return _n( 'instructor', 'instructors', $total, 'learnpress' );
		}
		if ( 'exceptions' === $report ) {
			return _n( 'order', 'orders', $total, 'learnpress' );
		}

		return _n( 'item', 'items', $total, 'learnpress' );
	}

	/**
	 * @param array $columns
	 * @param array $rows
	 * @return string
	 */
	private function build_csv( array $columns, array $rows ): string {
		$lines = array();

		$head = array();
		foreach ( $columns as $col ) {
			$head[] = $this->csv_escape( $col['label'] ?? '' );
		}
		$lines[] = implode( ',', $head );

		foreach ( $rows as $row ) {
			$cells = array();
			foreach ( $columns as $col ) {
				if ( isset( $col['csv'] ) && is_callable( $col['csv'] ) ) {
					$value = call_user_func( $col['csv'], $row );
				} else {
					$key   = $col['key'] ?? '';
					$value = $row[ $key ] ?? '';
				}
				$cells[] = $this->csv_escape( $value );
			}
			$lines[] = implode( ',', $cells );
		}

		return implode( "\r\n", $lines );
	}

	/**
	 * RFC-4180 escaping + CSV-injection guard ( mirrors csv.js ).
	 *
	 * @param mixed $value
	 * @return string
	 */
	private function csv_escape( $value ): string {
		$str = (string) ( $value ?? '' );

		if ( preg_match( '/^[=+\-@]/', $str ) ) {
			$str = "'" . $str;
		}
		if ( preg_match( '/[",\r\n]/', $str ) ) {
			$str = '"' . str_replace( '"', '""', $str ) . '"';
		}

		return $str;
	}

	/**
	 * @param string $report
	 * @param array  $data
	 * @return string
	 */
	private function csv_filename( string $report, array $data ): string {
		$filtertype = sanitize_key( $data['filtertype'] ?? 'today' );
		$report     = $report ?: 'report';

		return sprintf( 'learnpress-%s-%s.csv', sanitize_title( $report ), sanitize_title( $filtertype ) );
	}

	// --- Cell / badge helpers ( ported from the tab JS ) ---------------------

	/**
	 * @param string $color '' | green | yellow | red | grey
	 * @param string $label
	 * @return string
	 */
	private function badge( string $color, string $label ): string {
		if ( '' === $color ) {
			return esc_html( $label );
		}

		return sprintf(
			'<span class="lp-badge lp-badge--%s">%s</span>',
			esc_attr( $color ),
			esc_html( $label )
		);
	}

	/**
	 * @param float|null $rate
	 * @return string
	 */
	private function completion_cell( $rate ): string {
		if ( null === $rate ) {
			return '—';
		}

		return $this->badge( $this->completion_color( (float) $rate ), $rate . '%' );
	}

	/**
	 * @param float $rate
	 * @return string
	 */
	private function completion_color( float $rate ): string {
		$bands  = (array) apply_filters(
			'learn-press/statistics/completion-badge-thresholds',
			array(
				'green'  => 60,
				'yellow' => 40,
			)
		);
		$green  = (float) ( $bands['green'] ?? 60 );
		$yellow = (float) ( $bands['yellow'] ?? 40 );

		if ( $rate >= $green ) {
			return 'green';
		}

		return $rate >= $yellow ? 'yellow' : 'red';
	}

	private function sold_status_label( string $slug ): string {
		$labels = array(
			'healthy'             => __( 'Healthy', 'learnpress' ),
			'watch_completion'    => __( 'Watch completion', 'learnpress' ),
			'high_failed_quizzes' => __( 'High failed quizzes', 'learnpress' ),
		);

		return $labels[ $slug ] ?? $slug;
	}

	private function sold_status_color( string $slug ): string {
		if ( 'high_failed_quizzes' === $slug ) {
			return 'red';
		}
		if ( 'watch_completion' === $slug ) {
			return 'yellow';
		}

		return 'green';
	}

	private function severity_label( string $slug ): string {
		$labels = array(
			'high'   => __( 'High', 'learnpress' ),
			'medium' => __( 'Medium', 'learnpress' ),
			'low'    => __( 'Low', 'learnpress' ),
		);

		return $labels[ $slug ] ?? $slug;
	}

	private function severity_color( string $slug ): string {
		if ( 'high' === $slug ) {
			return 'red';
		}
		if ( 'medium' === $slug ) {
			return 'yellow';
		}

		return 'grey';
	}

	private function student_status_label( string $slug ): string {
		$labels = array(
			'active'  => __( 'Active', 'learnpress' ),
			'at_risk' => __( 'At risk', 'learnpress' ),
			'idle'    => __( 'Idle', 'learnpress' ),
		);

		return $labels[ $slug ] ?? $slug;
	}

	private function student_status_color( string $slug ): string {
		$colors = array(
			'active'  => 'green',
			'at_risk' => 'yellow',
			'idle'    => 'grey',
		);

		return $colors[ $slug ] ?? '';
	}

	/**
	 * @param string $value MySQL datetime.
	 * @return string
	 */
	private function last_active_cell( string $value ): string {
		if ( empty( $value ) ) {
			return '—';
		}

		$ts = strtotime( $value );
		if ( ! $ts ) {
			return '—';
		}

		return esc_html( date_i18n( get_option( 'date_format' ), $ts ) );
	}
}
