<?php

namespace LearnPress\Tests\Unit\Helpers;

use LearnPress\Helpers\LPDateTime;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;

/**
 * Unit tests for LPDateTime::get_duration_in_seconds().
 */
class LPDateTimeTest extends BrainMonkeyTestCase {
	protected function setUp(): void {
		parent::setUp();

		defined( 'MINUTE_IN_SECONDS' ) || define( 'MINUTE_IN_SECONDS', 60 );
		defined( 'HOUR_IN_SECONDS' ) || define( 'HOUR_IN_SECONDS', 3600 );
		defined( 'DAY_IN_SECONDS' ) || define( 'DAY_IN_SECONDS', 86400 );
		defined( 'WEEK_IN_SECONDS' ) || define( 'WEEK_IN_SECONDS', 604800 );
	}

	/**
	 * @dataProvider provide_durations
	 */
	public function test_get_duration_in_seconds( string $duration, int $expected ): void {
		$this->assertSame( $expected, LPDateTime::get_duration_in_seconds( $duration ) );
	}

	public function provide_durations(): array {
		return [
			'minute'            => [ '4 minute', 240 ],
			'minutes plural'    => [ '4 minutes', 240 ],
			'hour'              => [ '2 hour', 7200 ],
			'day'               => [ '1 day', 86400 ],
			'week'              => [ '1 week', 604800 ],
			'uppercase'         => [ '3 MINUTE', 180 ],
			'zero'              => [ '0 minute', 0 ],
			'unknown unit'      => [ '4 fortnight', 0 ],
			'invalid'           => [ 'abc', 0 ],
			'empty'             => [ '', 0 ],
		];
	}
}
