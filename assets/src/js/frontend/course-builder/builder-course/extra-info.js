import * as lpUtils from 'lpAssetsJsPath/utils.js';
import Sortable from 'sortablejs';

export class MetaboxExtraInfo {
	constructor() {
		this.init();
	}

	static selectors = {
		elExtraMetaboxAdd: '.lp_course_extra_meta_box__add',
		elExtraMetaboxContent: '.lp_course_extra_meta_box__content',
		elExtraMetaboxFields: '.lp_course_extra_meta_box__fields',
		elExtraMetaboxField: '.lp_course_extra_meta_box__field',
		elExtraMetaboxDelete: '.lp_course_extra_meta_box__fields a.delete',

		elFaqMetaboxAdd: '.lp_course_faq_meta_box__add',
		elFaqMetaboxContent: '.lp_course_faq_meta_box__content',
		elFaqMetaboxFields: '.lp_course_faq_meta_box__fields',
		elFaqMetaboxField: '.lp_course_faq_meta_box__field',
		elFaqMetaboxDelete: '.lp_course_faq_meta_box__fields a.delete',
	};

	init() {
		this.initSortable();
		this.events();
	}

	events() {
		if ( MetaboxExtraInfo._loadedEvents ) {
			return;
		}
		MetaboxExtraInfo._loadedEvents = true;

		lpUtils.eventHandlers( 'click', [
			{
				selector: MetaboxExtraInfo.selectors.elExtraMetaboxAdd,
				class: this,
				callBack: this.handleExtraMetaboxAdd.name,
			},
			{
				selector: MetaboxExtraInfo.selectors.elExtraMetaboxDelete,
				class: this,
				callBack: this.handleExtraMetaboxDelete.name,
			},
			{
				selector: MetaboxExtraInfo.selectors.elFaqMetaboxAdd,
				class: this,
				callBack: this.handleFaqMetaboxAdd.name,
			},
			{
				selector: MetaboxExtraInfo.selectors.elFaqMetaboxDelete,
				class: this,
				callBack: this.handleFaqMetaboxDelete.name,
			},
		] );
	}

	handleExtraMetaboxAdd( args ) {
		const { e, target } = args;
		e.preventDefault();

		const content = target.closest( MetaboxExtraInfo.selectors.elExtraMetaboxContent );
		if ( ! content ) return;

		const fields = content.querySelector( MetaboxExtraInfo.selectors.elExtraMetaboxFields );
		const addData = target.dataset.add;

		if ( fields && addData ) {
			fields.insertAdjacentHTML( 'beforeend', addData );

			const lastField = fields.querySelector(
				`${ MetaboxExtraInfo.selectors.elExtraMetaboxField }:last-child`
			);
			if ( lastField ) {
				const input = lastField.querySelector( 'input' );
				if ( input ) {
					setTimeout( () => input.focus(), 100 );
				}
			}
		}

		return false;
	}

	handleExtraMetaboxDelete( args ) {
		const { e, target } = args;
		e.preventDefault();

		const field = target.closest( MetaboxExtraInfo.selectors.elExtraMetaboxField );
		if ( field ) {
			field.remove();
		}

		return false;
	}

	handleFaqMetaboxAdd( args ) {
		const { e, target } = args;
		e.preventDefault();

		const content = target.closest( MetaboxExtraInfo.selectors.elFaqMetaboxContent );
		if ( ! content ) return;

		const fields = content.querySelector( MetaboxExtraInfo.selectors.elFaqMetaboxFields );
		const addData = target.dataset.add;

		if ( fields && addData ) {
			fields.insertAdjacentHTML( 'beforeend', addData );
		}

		return false;
	}

	handleFaqMetaboxDelete( args ) {
		const { e, target } = args;
		e.preventDefault();

		const field = target.closest( MetaboxExtraInfo.selectors.elFaqMetaboxField );
		if ( field ) {
			field.remove();
		}

		return false;
	}

	initSortable() {
		const extraFieldsContainers = document.querySelectorAll( '.lp_course_extra_meta_box__fields' );

		extraFieldsContainers.forEach( ( container ) => {
			new Sortable( container, {
				animation: 150,
				handle: '.sort',
				draggable: '.lp_course_extra_meta_box__field',
				ghostClass: 'sortable-ghost',
				chosenClass: 'sortable-chosen',
				dragClass: 'sortable-drag',
				forceFallback: false,
				scrollSensitivity: 40,
				onStart: ( evt ) => {
					evt.item.classList.add( 'is-dragging' );
				},
				onEnd: ( evt ) => {
					evt.item.classList.remove( 'is-dragging' );
				},
			} );
		} );

		const faqFieldsContainers = document.querySelectorAll( '.lp_course_faq_meta_box__fields' );

		faqFieldsContainers.forEach( ( container ) => {
			new Sortable( container, {
				animation: 150,
				handle: '.sort',
				draggable: '.lp_course_faq_meta_box__field',
				ghostClass: 'sortable-ghost',
				chosenClass: 'sortable-chosen',
				dragClass: 'sortable-drag',
				forceFallback: false,
				scrollSensitivity: 40,
				onStart: ( evt ) => {
					evt.item.classList.add( 'is-dragging' );
				},
				onEnd: ( evt ) => {
					evt.item.classList.remove( 'is-dragging' );
				},
			} );
		} );
	}
}
