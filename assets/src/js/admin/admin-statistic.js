/**
 * Statistics dashboard entry — bootstraps the per-tab modules.
 *
 * All four tabs run on the statistics/* module stack (state, api, chart,
 * data-table, report-modal); the legacy per-tab loaders are gone.
 *
 * @since 4.2.5.5
 * @version 2.0.0
 */

import * as lpUtils from 'lpAssetsJsPath/utils.js';
import { lpStatsFilterBar, LpStatsFilterBar } from './statistics/filter-bar.js';
import { lpStatsReportModal } from './statistics/report-modal.js';
import { lpStatsTabOverview, LpStatsTabOverview } from './statistics/tab-overview.js';
import { lpStatsTabOrders, LpStatsTabOrders } from './statistics/tab-orders.js';
import { lpStatsTabCourses, LpStatsTabCourses } from './statistics/tab-courses.js';
import { lpStatsTabUsers, LpStatsTabUsers } from './statistics/tab-users.js';
import { lpStatsTabInstructors, LpStatsTabInstructors } from './statistics/tab-instructors.js';

lpUtils.lpOnElementReady( LpStatsFilterBar.selectors.elContainer, () => {
	lpStatsFilterBar.init();
} );
// SweetAlert2 popup: delegated events only, no rendered container to wait for.
lpStatsReportModal.init();
lpUtils.lpOnElementReady( LpStatsTabOverview.selectors.elContainer, () => {
	lpStatsTabOverview.init();
} );
lpUtils.lpOnElementReady( LpStatsTabOrders.selectors.elContainer, () => {
	lpStatsTabOrders.init();
} );
lpUtils.lpOnElementReady( LpStatsTabCourses.selectors.elContainer, () => {
	lpStatsTabCourses.init();
} );
lpUtils.lpOnElementReady( LpStatsTabUsers.selectors.elContainer, () => {
	lpStatsTabUsers.init();
} );
lpUtils.lpOnElementReady( LpStatsTabInstructors.selectors.elContainer, () => {
	lpStatsTabInstructors.init();
} );
