<?php
/**
 * Class PeriodRange
 *
 * @package LearnPress/Classes/Statistics
 * @since 4.4.2
 */

namespace LearnPress\Statistics;

defined( 'ABSPATH' ) || exit();

/**
 * Value object describing one resolved statistics window.
 *
 * Carries both the concrete [ start … end ] datetimes and the legacy-compatible
 * { filter_type, time } pair consumed by LP_Statistics_DB / DashboardStatisticsDB,
 * so existing query methods keep working unchanged. `granularity` is the explicit
 * chart resolution — the DB group-by never re-guesses it.
 *
 * Instances are only built by PeriodResolver.
 *
 * @since 4.4.2
 */
class PeriodRange {
	/**
	 * @var string Requested preset id, e.g. 'month', 'last_quarter', 'custom'.
	 */
	public $preset;

	/**
	 * @var string Window start, 'Y-m-d H:i:s'.
	 */
	public $start;

	/**
	 * @var string Window end, 'Y-m-d H:i:s'.
	 */
	public $end;

	/**
	 * @var string Chart resolution: PeriodResolver::GRAN_HOUR|GRAN_DAY|GRAN_MONTH.
	 */
	public $granularity;

	/**
	 * @var string Human label, e.g. 'Month to date (Jul 1 – 14)'.
	 */
	public $label;

	/**
	 * @var string Legacy DB filter type paired with $time: date|custom.
	 */
	public $filter_type;

	/**
	 * @var string|int Legacy DB filter value paired with $filter_type.
	 */
	public $time;

	/**
	 * @param string     $preset      Preset id as requested.
	 * @param string     $start       'Y-m-d H:i:s'.
	 * @param string     $end         'Y-m-d H:i:s'.
	 * @param string     $granularity hour|day|month.
	 * @param string     $label       Display label.
	 * @param string     $filter_type Legacy DB filter type.
	 * @param string|int $time        Legacy DB filter value.
	 */
	public function __construct( string $preset, string $start, string $end, string $granularity, string $label, string $filter_type, $time ) {
		$this->preset      = $preset;
		$this->start       = $start;
		$this->end         = $end;
		$this->granularity = $granularity;
		$this->label       = $label;
		$this->filter_type = $filter_type;
		$this->time        = $time;
	}

	/**
	 * The { filter_type, time } pair every legacy DB method understands.
	 *
	 * @return array [ 'filter_type' => string, 'time' => string|int ]
	 */
	public function legacy_pair(): array {
		return array(
			'filter_type' => $this->filter_type,
			'time'        => $this->time,
		);
	}
}
