/**
 * Statistics dashboard global filter bar.
 *
 * WC-style date-range dropdown ( Presets/Custom tabs + Compare to ) +
 * instructor/category scope selects + CSV export trigger.
 *
 * Preset and compare selections apply immediately; the Custom tab applies on
 * Update. Mutates state only through lpStatsState.set(); tab modules listen
 * for the filter-changed event and never talk to this class directly.
 *
 * The toggle label is derived from state, not set imperatively per handler:
 * LP_STATS_FILTER_CHANGED paints an optimistic label the instant the filter
 * moves, and LP_STATS_RANGE_RESOLVED ( echoed by every stats payload ) then
 * reconciles it to the server-resolved label — which is what keeps a panel
 * left open past midnight from showing a stale "to date" range.
 *
 * Preset range labels come pre-resolved from the server ( dateRange.presets in
 * lpAdminStatisticSettings ) — the only client-side date formatting is the
 * custom range, via Intl.
 *
 * @since 4.4.2
 * @version 2.1.0
 */

import * as lpUtils from 'lpAssetsJsPath/utils.js';
import {
	lpStatsState,
	LP_STATS_EXPORT_CSV,
	LP_STATS_FILTER_CHANGED,
	LP_STATS_RANGE_RESOLVED,
} from './state.js';
import { lpStatsFetch, getStatsConfig, getStatsI18n } from './api.js';
import { intlFormat } from './chart.js';

export class LpStatsFilterBar {
	static selectors = {
		elContainer: '.lp-statistics-filter-bar',
		elDaterange: '.lp-stats-daterange',
		elToggle: '.lp-stats-daterange__toggle',
		elToggleLabel: '.lp-stats-daterange__label',
		elPanel: '.lp-stats-daterange__panel',
		elTab: '.lp-stats-daterange__tab',
		elTabpanel: '.lp-stats-daterange__tabpanel',
		elPresetRadio: 'input[name="lp-stats-preset"]',
		elCompareRadio: 'input[name="lp-stats-compare"]',
		elCustomFrom: '.lp-stats-daterange__from',
		elCustomTo: '.lp-stats-daterange__to',
		elBtnUpdate: '.lp-stats-daterange__update',
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
				selector: LpStatsFilterBar.selectors.elToggle,
				class: this,
				callBack: this.togglePanel.name,
			},
			{
				selector: LpStatsFilterBar.selectors.elTab,
				class: this,
				callBack: this.switchTab.name,
			},
			// Presets commit on a real pointer click. Chromium also fires a click
			// on arrow-key radio navigation, but keyboard-synthesized clicks carry
			// detail 0 — changePreset ignores those so arrows browse; keyboard
			// commit is the Enter/Space keydown handler below. ( Committing on
			// 'change' would apply + close + refetch on every arrow keystroke. )
			{
				selector: LpStatsFilterBar.selectors.elPresetRadio,
				class: this,
				callBack: this.changePreset.name,
			},
			{
				selector: LpStatsFilterBar.selectors.elBtnUpdate,
				class: this,
				callBack: this.applyCustomRange.name,
			},
			{
				selector: LpStatsFilterBar.selectors.elBtnExport,
				class: this,
				callBack: this.exportCsv.name,
			},
		] );

		// Keyboard commit for the browsed preset ( Enter or Space on the focused
		// radio ); arrow keys move the selection without committing.
		lpUtils.eventHandlers( 'keydown', [
			{
				selector: LpStatsFilterBar.selectors.elPresetRadio,
				class: this,
				callBack: this.changePreset.name,
				conditionBeforeCallBack: ( args ) =>
					'Enter' === args.e.key || ' ' === args.e.key,
			},
		] );

		lpUtils.eventHandlers( 'change', [
			{
				selector: LpStatsFilterBar.selectors.elCompareRadio,
				class: this,
				callBack: this.changeCompare.name,
			},
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

		// Outside click / Esc close the popover.
		document.addEventListener( 'click', ( event ) => {
			if (
				this.isPanelOpen() &&
				! event.target.closest( LpStatsFilterBar.selectors.elDaterange )
			) {
				this.closePanel();
			}
		} );
		document.addEventListener( 'keydown', ( event ) => {
			if ( 'Escape' === event.key && this.isPanelOpen() ) {
				this.closePanel( true );
			}
		} );

		// Toggle label follows state: an optimistic label the moment the filter
		// moves, then the authoritative server label when the payload lands.
		document.addEventListener( LP_STATS_FILTER_CHANGED, ( event ) => {
			this.setToggleLabel( this.labelForState( event.detail ) );
		} );
		document.addEventListener( LP_STATS_RANGE_RESOLVED, ( event ) => {
			this.setToggleLabel( event.detail?.label );
		} );
	}

	// Popover open/close + tabs.

	panel() {
		return this.elContainer.querySelector( LpStatsFilterBar.selectors.elPanel );
	}

	isPanelOpen() {
		const elPanel = this.panel();
		return !! elPanel && ! elPanel.hidden;
	}

	togglePanel( args ) {
		const btn = args.target.closest( LpStatsFilterBar.selectors.elToggle );
		if ( ! btn || ! this.elContainer.contains( btn ) ) {
			return;
		}

		const elPanel = this.panel();
		if ( ! elPanel ) {
			// Toggle rendered without its panel ( template override ) — no-op,
			// like closePanel(), instead of throwing on a null deref.
			return;
		}

		if ( ! elPanel.hidden ) {
			this.closePanel();
			return;
		}

		elPanel.hidden = false;
		btn.setAttribute( 'aria-expanded', 'true' );

		// Focus-trap-lite: land on the checked preset ( or the active tab ).
		const checked = elPanel.querySelector(
			`${ LpStatsFilterBar.selectors.elPresetRadio }:checked`
		);
		const fallback = elPanel.querySelector(
			`${ LpStatsFilterBar.selectors.elTab }.active`
		);
		( checked && checked.offsetParent ? checked : fallback )?.focus();
	}

	/**
	 * @param {boolean} refocus Return focus to the toggle ( Esc ), not on outside click.
	 */
	closePanel( refocus = false ) {
		const elPanel = this.panel();
		if ( ! elPanel ) {
			return;
		}

		elPanel.hidden = true;
		const elToggle = this.elContainer.querySelector(
			LpStatsFilterBar.selectors.elToggle
		);
		elToggle?.setAttribute( 'aria-expanded', 'false' );
		if ( refocus ) {
			elToggle?.focus();
		}
	}

	switchTab( args ) {
		const btn = args.target.closest( LpStatsFilterBar.selectors.elTab );
		if ( ! btn || ! this.elContainer.contains( btn ) ) {
			return;
		}

		const tab = btn.dataset.tab;
		this.elContainer
			.querySelectorAll( LpStatsFilterBar.selectors.elTab )
			.forEach( ( el ) => {
				const active = el === btn;
				el.classList.toggle( 'active', active );
				el.setAttribute( 'aria-selected', active ? 'true' : 'false' );
			} );
		this.elContainer
			.querySelectorAll( LpStatsFilterBar.selectors.elTabpanel )
			.forEach( ( el ) => {
				el.hidden = el.dataset.tabpanel !== tab;
			} );
	}

	// Selection → state.

	changePreset( args ) {
		const radio = args.target.closest(
			LpStatsFilterBar.selectors.elPresetRadio
		);
		if ( ! radio || ! this.elContainer.contains( radio ) ) {
			return;
		}

		// A click with detail 0 is keyboard-synthesized ( arrow navigation, or
		// Space ) — let the user browse; the Enter/Space keydown binding is what
		// commits from the keyboard. Real pointer clicks have detail >= 1.
		if ( 'click' === args.e.type && ! args.e.detail ) {
			return;
		}

		// Label updates via the filter-changed listener ( derived from state ).
		this.closePanel( true );
		lpStatsState.set( { filtertype: radio.value, date: '' } );
	}

	changeCompare( args ) {
		const radio = args.target.closest(
			LpStatsFilterBar.selectors.elCompareRadio
		);
		if ( ! radio || ! this.elContainer.contains( radio ) ) {
			return;
		}

		// Popover stays open: compare is a modifier, not a range choice.
		lpStatsState.set( { compare: radio.value } );
	}

	applyCustomRange( args ) {
		const btn = args.target.closest( LpStatsFilterBar.selectors.elBtnUpdate );
		if ( ! btn || ! this.elContainer.contains( btn ) ) {
			return;
		}

		const from = this.elContainer.querySelector(
			LpStatsFilterBar.selectors.elCustomFrom
		)?.value;
		const to = this.elContainer.querySelector(
			LpStatsFilterBar.selectors.elCustomTo
		)?.value;
		if ( ! from || ! to ) {
			return;
		}

		// Uncheck any preset — the window is now the custom pair.
		this.elContainer
			.querySelectorAll( LpStatsFilterBar.selectors.elPresetRadio )
			.forEach( ( el ) => {
				el.checked = false;
			} );

		const [ start, end ] = [ from, to ].sort();
		// Label updates via the filter-changed listener ( derived from state ).
		this.closePanel( true );
		lpStatsState.set( { filtertype: 'custom', date: `${ start }+${ end }` } );
	}

	// Toggle label.

	setToggleLabel( label ) {
		const elLabel = this.elContainer.querySelector(
			LpStatsFilterBar.selectors.elToggleLabel
		);
		if ( elLabel && label ) {
			elLabel.textContent = label;
		}
	}

	/**
	 * Optimistic toggle label for the current filter state — a preset's
	 * server-resolved label, or "Custom (range)" for a custom window. The
	 * authoritative label arrives later via LP_STATS_RANGE_RESOLVED.
	 *
	 * @param {Object} filters { filtertype, date } from lpStatsState.
	 */
	labelForState( { filtertype, date } = {} ) {
		if ( 'custom' === filtertype && date ) {
			const [ start, end ] = date.split( '+' );
			if ( start && end ) {
				return `${ getStatsI18n( 'custom', 'Custom' ) } (${ this.customRangeLabel( start, end ) })`;
			}
		}

		return this.presetLabel( filtertype );
	}

	/**
	 * "Month to date (Jul 1 – 14)" from the server-resolved preset table.
	 *
	 * @param {string} value Preset id.
	 */
	presetLabel( value ) {
		const { presets = [] } = getStatsConfig().dateRange || {};
		const preset = presets.find( ( entry ) => entry.value === value );
		if ( ! preset ) {
			return value;
		}

		return preset.rangeLabel
			? `${ preset.name } (${ preset.rangeLabel })`
			: preset.name;
	}

	/**
	 * Locale-formatted custom range, densest unambiguous form
	 * ( "Jul 1 – 14", "Apr 1 – Jun 30", "Dec 29, 2025 – Jan 4, 2026" ).
	 *
	 * @param {string} start 'Y-m-d'.
	 * @param {string} end   'Y-m-d'.
	 */
	customRangeLabel( start, end ) {
		const dateFrom = new Date( `${ start }T00:00:00` );
		const dateTo = new Date( `${ end }T00:00:00` );
		if ( isNaN( dateFrom.getTime() ) || isNaN( dateTo.getTime() ) ) {
			return `${ start } – ${ end }`;
		}

		const sameYear = dateFrom.getFullYear() === dateTo.getFullYear();
		const sameMonth = sameYear && dateFrom.getMonth() === dateTo.getMonth();

		if ( sameMonth ) {
			const startPart = intlFormat( dateFrom, { month: 'short', day: 'numeric' } );
			return start === end
				? startPart
				: `${ startPart } – ${ intlFormat( dateTo, { day: 'numeric' } ) }`;
		}
		if ( sameYear ) {
			const options = { month: 'short', day: 'numeric' };
			return `${ intlFormat( dateFrom, options ) } – ${ intlFormat( dateTo, options ) }`;
		}

		const options = { month: 'short', day: 'numeric', year: 'numeric' };
		return `${ intlFormat( dateFrom, options ) } – ${ intlFormat( dateTo, options ) }`;
	}

	// Scope selects + export ( unchanged behavior ).

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
