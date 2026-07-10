/**
 * Statistics dashboard global filter bar.
 *
 * Time buttons + instructor/category scope selects + CSV export trigger.
 * Mutates state only through lpStatsState.set(); tab modules listen for
 * the filter-changed event and never talk to this class directly.
 *
 * @since 4.4.2
 * @version 1.0.0
 */

import * as lpUtils from 'lpAssetsJsPath/utils.js';
import { lpStatsState, LP_STATS_EXPORT_CSV } from './state.js';
import { lpStatsFetch } from './api.js';

export class LpStatsFilterBar {
	static selectors = {
		elContainer: '.lp-statistics-filter-bar',
		elBtnFilterTime: '.btn-filter-time',
		elCustomFilter: '.custom-filter-time',
		elCustomFilterBtn: '.custom-filter-btn',
		elCustomDateFrom: '#ct-filter-1',
		elCustomDateTo: '#ct-filter-2',
		elSelectInstructor: '.lp-stats-filter-instructor',
		elSelectCategory: '.lp-stats-filter-category',
		elBtnExport: '.lp-stats-export-csv',
	};

	init() {
		this.elContainer = document.querySelector(
			LpStatsFilterBar.selectors.elContainer
		);
		if ( ! this.elContainer ) {
			return;
		}

		this.loadFilterOptions();
		this.events();
	}

	events() {
		if ( LpStatsFilterBar._loadedEvents ) {
			return;
		}
		LpStatsFilterBar._loadedEvents = this;

		lpUtils.eventHandlers( 'click', [
			{
				selector: LpStatsFilterBar.selectors.elBtnFilterTime,
				class: this,
				callBack: this.changeTimeFilter.name,
			},
			{
				selector: LpStatsFilterBar.selectors.elCustomFilterBtn,
				class: this,
				callBack: this.applyCustomRange.name,
			},
			{
				selector: LpStatsFilterBar.selectors.elBtnExport,
				class: this,
				callBack: this.exportCsv.name,
			},
		] );

		lpUtils.eventHandlers( 'change', [
			{
				selector: LpStatsFilterBar.selectors.elSelectInstructor,
				class: this,
				callBack: this.changeScope.name,
			},
			{
				selector: LpStatsFilterBar.selectors.elSelectCategory,
				class: this,
				callBack: this.changeScope.name,
			},
		] );
	}

	/**
	 * Populate the two scope selects from the filter-options endpoint,
	 * then restore any deep-linked selection already held by the state.
	 */
	loadFilterOptions() {
		lpStatsFetch(
			'filter-options',
			{},
			{
				success: ( response ) => {
					const { instructors = [], categories = [] } = response.data || {};
					this.fillSelect(
						LpStatsFilterBar.selectors.elSelectInstructor,
						instructors,
						lpStatsState.get().instructor_id
					);
					this.fillSelect(
						LpStatsFilterBar.selectors.elSelectCategory,
						categories,
						lpStatsState.get().category_id
					);
				},
			}
		);
	}

	/**
	 * Append { id, name } options — createElement + textContent only,
	 * names are user-controlled.
	 *
	 * @param {string} selector Select element selector inside the bar.
	 * @param {Array}  items    [ { id, name } ].
	 * @param {number} selected Id to preselect (deep link), 0 for "All".
	 */
	fillSelect( selector, items, selected = 0 ) {
		const elSelect = this.elContainer.querySelector( selector );
		if ( ! elSelect ) {
			return;
		}

		items.forEach( ( item ) => {
			const option = document.createElement( 'option' );
			option.value = item.id;
			option.textContent = item.name;
			elSelect.appendChild( option );
		} );

		if ( selected ) {
			elSelect.value = String( selected );
			// Unknown deep-link id → back to "All", state follows the visible truth.
			if ( elSelect.value !== String( selected ) ) {
				elSelect.value = '0';
			}
		}
	}

	changeTimeFilter( args ) {
		const btn = args.target.closest(
			LpStatsFilterBar.selectors.elBtnFilterTime
		);
		if ( ! btn || ! this.elContainer.contains( btn ) ) {
			return;
		}

		this.elContainer
			.querySelectorAll( LpStatsFilterBar.selectors.elBtnFilterTime )
			.forEach( ( el ) => el.classList.remove( 'active' ) );
		btn.classList.add( 'active' );

		const filtertype = btn.dataset.filter;
		const elCustom = this.elContainer.querySelector(
			LpStatsFilterBar.selectors.elCustomFilter
		);

		if ( 'custom' === filtertype ) {
			// Show the two date inputs; state changes when Filter is clicked.
			if ( elCustom ) {
				elCustom.style.display = 'flex';
			}
			return;
		}

		if ( elCustom ) {
			elCustom.style.display = '';
		}
		lpStatsState.set( { filtertype, date: '' } );
	}

	applyCustomRange( args ) {
		const btn = args.target.closest(
			LpStatsFilterBar.selectors.elCustomFilterBtn
		);
		if ( ! btn || ! this.elContainer.contains( btn ) ) {
			return;
		}

		const from = this.elContainer.querySelector(
			LpStatsFilterBar.selectors.elCustomDateFrom
		)?.value;
		const to = this.elContainer.querySelector(
			LpStatsFilterBar.selectors.elCustomDateTo
		)?.value;
		if ( ! from || ! to ) {
			return;
		}

		lpStatsState.set( { filtertype: 'custom', date: `${ from }+${ to }` } );
	}

	changeScope( args ) {
		const elSelect = args.target.closest( 'select' );
		if ( ! elSelect || ! this.elContainer.contains( elSelect ) ) {
			return;
		}

		const elInstructor = this.elContainer.querySelector(
			LpStatsFilterBar.selectors.elSelectInstructor
		);
		const elCategory = this.elContainer.querySelector(
			LpStatsFilterBar.selectors.elSelectCategory
		);

		lpStatsState.set( {
			instructor_id: parseInt( elInstructor?.value, 10 ) || 0,
			category_id: parseInt( elCategory?.value, 10 ) || 0,
		} );
	}

	exportCsv( args ) {
		const btn = args.target.closest(
			LpStatsFilterBar.selectors.elBtnExport
		);
		if ( ! btn || ! this.elContainer.contains( btn ) ) {
			return;
		}

		document.dispatchEvent( new CustomEvent( LP_STATS_EXPORT_CSV ) );
	}
}

export const lpStatsFilterBar = new LpStatsFilterBar();
