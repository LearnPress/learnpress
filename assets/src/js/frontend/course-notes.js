/**
 * LP Student Notes - learning page widget (lesson only).
 *
 * - Sidebar panel: list, add text note, edit, delete.
 * - Select text in the lesson content → floating "Add Note" → highlight note.
 * - Only the note clicked in the sidebar is highlighted in the lesson content.
 *
 * Data: window.lpCourseNotes (CourseNoteTemplate::get_js_data()).
 * Transport: window.lpAJAXG.fetchAJAX (actions lp_note_save / lp_note_delete).
 * All note values are plain text and are rendered with textContent only.
 *
 * @since   4.4.9.2
 * @version 1.0.0
 */
import SweetAlert from 'sweetalert2';
import * as lpUtils from '../utils.js';
import * as lpToastify from '../lpToastify.js';
import {
	createAnchor,
	getText,
	locateInText,
	MARK_CLASS,
	unwrap,
	wrapPosition,
} from './course-notes-anchor.js';

const TYPE_TEXT = 'text';
const TYPE_HIGHLIGHT = 'highlight';
const SCOPE = 'lesson_content';
const MIN_SELECTION_LENGTH = 2;
const HELP_STORAGE_KEY = 'lp-notes-help-hidden';
const NOTES_PAGE_SIZE = 10;

export class CourseNotes {
	static selectors = {
		root: '#lp-notes',
		content: '#lp-notes-content',
		help: '.lp-notes__help',
		helpDismiss: '.lp-notes__help-dismiss',
		add: '.lp-notes__add',
		form: '.lp-notes__form',
		formQuote: '.lp-notes__form-quote',
		formContent: '.lp-notes__form-content',
		formCancel: '.lp-notes__form-cancel',
		formSave: '.lp-notes__form-save',
		list: '.lp-notes__list',
		showMore: '.lp-notes__show-more',
		viewAll: '.lp-notes__view-all',
		empty: '.lp-notes__empty',
		cardTemplate: '.lp-notes__card-template',
		card: '.lp-notes__card',
		cardEdit: '.lp-notes__card-edit',
		cardDelete: '.lp-notes__card-delete',
		selectionBtn: '.lp-notes__selection-btn',
		mark: `mark.${ MARK_CLASS }`,
	};

	constructor() {
		this.config = null;
		this.root = null;
		this.contentRoot = null;
		this.el = {};
		this.notes = [];
		this.visibleCount = NOTES_PAGE_SIZE;
		this.activeNoteId = null;
		// Note IDs whose highlight could not be found in the lesson.
		this.orphanedIds = new Set();
		// Form states: { mode: 'create'|'edit', noteType, noteId, anchor }.
		this.formStates = new WeakMap();
		this.formPlaceholder = null;
		this.sidebarEditCard = null;
		this.selectionRange = null;
		this.isSaving = false;
		this.panelRoot = null;
		this.popupRoot = null;
		this.popupForm = null;
		this.popupEditCard = null;
	}

	get formState() {
		return this.formStates.get( this.el.form ) || null;
	}

	init() {
		this.config = window.lpCourseNotes || null;
		this.root = document.querySelector( CourseNotes.selectors.root );
		if ( ! this.config || ! this.root ) {
			return;
		}

		this.el.selectionBtn = this.root.querySelector( CourseNotes.selectors.selectionBtn );

		this.contentRoot = document.querySelector( this.config.contentSelector );
		this.notes = Array.isArray( this.config.notes ) ? this.config.notes : [];

		this.findOrphanedNotes();
		this.bindEvents();
	}

	bindEvents() {
		const s = CourseNotes.selectors;

		lpUtils.eventHandlers( 'click', [
			{ selector: `.lp-addon-content-bar ${ s.viewAll }`, class: this, callBack: this.handleViewAll.name },
			{ selector: `${ s.content } ${ s.helpDismiss }`, class: this, callBack: this.handleHelpDismiss.name },
			{ selector: `${ s.content } ${ s.add }`, class: this, callBack: this.handleAddClick.name },
			{ selector: `${ s.content } ${ s.showMore }`, class: this, callBack: this.handleShowMore.name },
			{ selector: `${ s.content } ${ s.formCancel }, .lp-notes__all ${ s.formCancel }`, class: this, callBack: this.handleFormCancel.name },
			{ selector: `${ s.content } ${ s.cardEdit }, .lp-notes__all ${ s.cardEdit }`, class: this, callBack: this.handleCardEdit.name },
			{ selector: `${ s.content } ${ s.cardDelete }, .lp-notes__all ${ s.cardDelete }`, class: this, callBack: this.handleCardDelete.name },
			{ selector: `${ s.content } ${ s.card }`, class: this, callBack: this.handleCardClick.name },
			{ selector: `${ s.root } ${ s.selectionBtn }`, class: this, callBack: this.handleSelectionBtnClick.name },
		] );

		lpUtils.eventHandlers( 'submit', [
			{ selector: `${ s.content } ${ s.form }, .lp-notes__all ${ s.form }`, class: this, callBack: this.handleFormSubmit.name },
		] );

		document.addEventListener( 'lp-learning-content-bar-rendered', ( e ) => {
			if ( e.detail?.item !== 'notes' ) {
				this.setActiveNote( null );
				return;
			}
			const content = e.detail.contentBar.querySelector( s.content );
			if ( ! content || this.panelRoot === content ) {
				return;
			}
			this.panelRoot = content;
			this.el.viewAll = e.detail.contentBar.querySelector( s.viewAll );
			for ( const key of [ 'help', 'form', 'formQuote', 'formContent', 'formSave', 'add', 'list', 'showMore', 'empty', 'cardTemplate' ] ) {
				this.el[ key ] = content.querySelector( s[ key ] );
			}
			this.initHelp();
			this.renderList();
		} );
		document.addEventListener( 'lp-learning-content-bar-closed', ( e ) => {
			if ( e.detail?.item === 'notes' ) {
				if ( ! this.isSaving ) {
					this.closeForm();
				}
				this.setActiveNote( null );
			}
		} );

		if ( ( this.config.canCreate ?? this.config.canEdit ) && this.contentRoot && this.el.selectionBtn ) {
			// Keep the text selection when pressing the floating button.
			this.el.selectionBtn.addEventListener( 'mousedown', ( e ) => e.preventDefault() );

			const onSelectionEnd = lpUtils.debounce( () => this.updateSelectionButton(), 50 );
			document.addEventListener( 'mouseup', onSelectionEnd );
			document.addEventListener( 'keyup', onSelectionEnd );
			document.addEventListener( 'touchend', onSelectionEnd );
			document.addEventListener( 'scroll', onSelectionEnd, true );
			window.addEventListener( 'resize', onSelectionEnd );
		}
	}

	// ---------------------------------------------------------------------
	// Panel
	// ---------------------------------------------------------------------

	openPanel() {
		document.dispatchEvent( new CustomEvent( 'lp-learning-content-bar-open', {
			detail: { item: 'notes' },
		} ) );
	}

	initHelp() {
		if ( this.el.help && this.storageGet( HELP_STORAGE_KEY ) === 'yes' ) {
			this.el.help.hidden = true;
		}
	}

	handleViewAll( args ) {
		args.e.preventDefault();
		if ( this.isSaving ) {
			return;
		}
		const template = this.root.querySelector( '.lp-notes__all-template' );
		if ( ! template || typeof window.lpAJAXG?.getElements !== 'function' ) {
			return;
		}
		this.setActiveNote( null );
		this.hideSelectionButton();
		this.openCourseNotes( template.innerHTML );
	}

	openCourseNotes( html, onOpen ) {
		let openedPopup;
		SweetAlert.fire( {
			title: this.config.i18n.courseNotes,
			html,
			width: 800,
			showConfirmButton: false,
			showCloseButton: true,
			customClass: { popup: 'lp-notes-modal' },
			allowOutsideClick: () => ! this.isSaving,
			allowEscapeKey: () => ! this.isSaving,
			didOpen: ( popup ) => {
				openedPopup = popup;
				this.popupRoot = popup;
				popup.addEventListener( 'click', ( e ) => {
					if ( this.isSaving && e.target.closest( '.page-numbers' ) ) {
						e.preventDefault();
						e.stopPropagation();
					}
				}, true );
				window.lpAJAXG.getElements();
				onOpen?.();
			},
			didClose: () => {
				if ( this.popupRoot === openedPopup ) {
					this.closeForm( this.popupForm );
					this.popupRoot = null;
				}
			},
		} );
	}

	setCardEditing( card, editing ) {
		if ( card ) {
			card.querySelector( '.lp-notes__card-actions' ).hidden = editing;
			card.querySelector( '.lp-notes__card-content' ).hidden = editing;
		}
	}

	attachEditForm( card, form ) {
		this.setCardEditing( card, true );
		form.hidden = false;
		card.appendChild( form );
	}

	restoreSidebarForm() {
		if ( this.sidebarEditCard ) {
			this.setCardEditing( this.sidebarEditCard, false );
			this.formPlaceholder.after( this.el.form );
			this.sidebarEditCard = null;
		}
	}

	handleHelpDismiss( args ) {
		args.e.preventDefault();
		this.el.help.hidden = true;
		this.storageSet( HELP_STORAGE_KEY, 'yes' );
	}

	// ---------------------------------------------------------------------
	// Form
	// ---------------------------------------------------------------------

	handleAddClick( args ) {
		args.e.preventDefault();
		if ( this.config.canCreate ?? this.config.canEdit ) {
			this.openForm( { mode: 'create', noteType: TYPE_TEXT } );
		}
	}

	handleFormCancel( args ) {
		args.e.preventDefault();
		if ( ! this.isSaving ) {
			this.closeForm( args.target.closest( CourseNotes.selectors.form ) );
		}
	}

	/**
	 * @param {Object}           state { mode, noteType, noteId?, anchor?, content?, quote? }
	 * @param {HTMLElement|null} card  Note card for inline editing.
	 */
	openForm( state, card = null ) {
		const popup = card?.closest( '.lp-notes__all' );
		if ( this.isSaving || ( ! popup && ! this.config.canEdit ) ) {
			return;
		}
		let form;
		if ( popup ) {
			const source = this.root.querySelector( '.lp-notes__popup-form-template' )?.content.querySelector( CourseNotes.selectors.form ) || this.el.form;
			if ( ! source ) {
				return;
			}
			this.closeForm( this.popupForm );
			form = source.cloneNode( true );
			form.querySelector( CourseNotes.selectors.formContent ).id = `lp-notes-popup-input-${ state.noteId }`;
			this.popupForm = form;
			this.popupEditCard = card;
		} else {
			this.openPanel();
			form = this.el.form;
			if ( ! form ) {
				return;
			}
			this.setActiveNote( null );
			this.restoreSidebarForm();
			if ( this.el.add ) {
				this.el.add.hidden = true;
			}
			if ( card ) {
				if ( ! this.formPlaceholder ) {
					this.formPlaceholder = document.createComment( 'Notes form' );
					form.before( this.formPlaceholder );
				}
				this.sidebarEditCard = card;
			}
		}
		this.formStates.set( form, state );
		const input = form.querySelector( CourseNotes.selectors.formContent );
		input.value = state.content || '';
		const quote = form.querySelector( CourseNotes.selectors.formQuote );
		quote.textContent = state.mode === 'edit' ? '' : state.quote || '';
		quote.hidden = ! quote.textContent;
		form.hidden = false;
		if ( card ) {
			this.attachEditForm( card, form );
		}
		input.focus( { preventScroll: state.mode === 'edit' } );
	}

	closeForm( form = this.el.form ) {
		if ( ! form ) {
			return;
		}
		if ( form === this.popupForm ) {
			this.setCardEditing( this.popupEditCard, false );
			form.remove();
			this.popupForm = null;
			this.popupEditCard = null;
		} else {
			this.restoreSidebarForm();
			if ( this.el.add ) {
				this.el.add.hidden = false;
			}
		}
		this.formStates.delete( form );
		form.hidden = true;
		form.querySelector( CourseNotes.selectors.formContent ).value = '';
		const quote = form.querySelector( CourseNotes.selectors.formQuote );
		quote.textContent = '';
		quote.hidden = true;
	}

	handleFormSubmit( args ) {
		args.e.preventDefault();
		const form = args.target.closest( CourseNotes.selectors.form );
		const popup = form?.closest( '.lp-notes__all' );
		const state = this.formStates.get( form );
		if ( ! state || ! form || this.isSaving ) {
			return;
		}

		const input = form.querySelector( CourseNotes.selectors.formContent );
		const content = input.value.trim();
		const { mode, noteType, noteId, anchor } = state;

		if ( noteType === TYPE_TEXT && ! content ) {
			lpToastify.show( this.config.i18n.contentEmpty, 'error' );
			input.focus();
			return;
		}

		const dataSend = { action: 'lp_note_save', content };
		if ( mode === 'edit' ) {
			dataSend.note_id = noteId;
		} else {
			Object.assign( dataSend, {
				course_id: this.config.courseId,
				item_id: this.config.itemId,
				item_type: this.config.itemType,
				note_type: noteType,
				anchor: noteType === TYPE_HIGHLIGHT ? anchor : null,
			} );
		}

		this.request( dataSend, ( data, message ) => {
			const note = data.note;
			if ( mode === 'edit' ) {
				this.notes = this.notes.map( ( n ) => ( String( n.note_id ) === String( note.note_id ) ? { ...n, ...note } : n ) );
			} else {
				this.notes.unshift( note );
			}

			const card = form.closest( CourseNotes.selectors.card );
			if ( card ) {
				card.querySelector( '.lp-notes__card-content' ).textContent = note.content;
			}
			this.closeForm( form );
			if ( ! popup ) {
				this.setActiveNote( note.note_id );
			}
			this.renderList();
			lpToastify.show( message, 'success' );
		} );
	}

	setSaving( isSaving ) {
		this.isSaving = isSaving;
		if ( this.el.viewAll ) {
			this.el.viewAll.setAttribute( 'aria-disabled', String( isSaving ) );
		}
		const controls = [ 'add', 'formContent', 'formCancel', 'cardEdit', 'cardDelete' ]
			.map( ( key ) => CourseNotes.selectors[ key ] ).join( ', ' );
		this.panelRoot?.querySelectorAll( controls ).forEach( ( el ) => {
			el.disabled = isSaving;
		} );
		this.popupRoot?.querySelectorAll( 'button, textarea' ).forEach( ( el ) => {
			el.disabled = isSaving;
		} );

		if ( this.el.selectionBtn ) {
			this.el.selectionBtn.disabled = isSaving;
		}
		if ( isSaving ) {
			this.hideSelectionButton();
		}
		for ( const form of [ this.el.form, this.popupForm ] ) {
			const save = form?.querySelector( CourseNotes.selectors.formSave );
			if ( save ) {
				save.disabled = isSaving;
				lpUtils.lpSetLoadingEl( save, isSaving ? 1 : 0 );
			}
		}
	}

	// ---------------------------------------------------------------------
	// Cards
	// ---------------------------------------------------------------------

	renderList() {
		if ( ! this.el.list || ! this.el.cardTemplate ) {
			return;
		}

		if ( this.activeNoteId !== null && ! this.getNote( this.activeNoteId ) ) {
			this.setActiveNote( null );
		}

		const scrollContainer = this.panelRoot.closest( '.lp-addon-content-bar__content' );
		const scrollTop = scrollContainer?.scrollTop;
		const editingId = this.sidebarEditCard?.dataset.noteId;
		this.restoreSidebarForm();
		this.el.list.textContent = '';
		this.appendVisibleNotes();
		if ( editingId ) {
			const card = this.el.list.querySelector( `[data-note-id="${ CSS.escape( editingId ) }"]` );
			if ( card ) {
				this.sidebarEditCard = card;
				this.attachEditForm( card, this.el.form );
			} else {
				this.closeForm();
			}
		}
		if ( scrollContainer ) {
			scrollContainer.scrollTop = scrollTop;
		}

		if ( this.el.empty ) {
			this.el.empty.hidden = this.notes.length > 0;
		}
	}

	appendVisibleNotes() {
		const start = this.el.list.children.length;
		this.notes.slice( start, this.visibleCount ).forEach( ( note ) => this.el.list.appendChild( this.createCard( note ) ) );
		if ( this.el.showMore ) {
			this.el.showMore.hidden = this.visibleCount >= this.notes.length;
		}
	}

	handleShowMore( args ) {
		args.e.preventDefault();
		const start = this.el.list.children.length;
		this.visibleCount += NOTES_PAGE_SIZE;
		this.appendVisibleNotes();
		this.el.list.children[ start ]?.focus( { preventScroll: true } );
	}

	/**
	 * @param {Object} note
	 * @return {HTMLElement} card
	 */
	createCard( note ) {
		const fragment = this.el.cardTemplate.content.cloneNode( true );
		const card = fragment.querySelector( CourseNotes.selectors.card );
		const isHighlight = note.note_type === TYPE_HIGHLIGHT;
		const set = ( selector, text ) => {
			const el = card.querySelector( selector );
			if ( el ) {
				el.textContent = text || '';
			}

			return el;
		};

		card.dataset.noteId = String( note.note_id );
		card.classList.toggle( 'is-highlight', isHighlight );
		card.classList.toggle( 'is-active', card.dataset.noteId === this.activeNoteId );
		card.querySelectorAll( `${ CourseNotes.selectors.cardEdit }, ${ CourseNotes.selectors.cardDelete }` ).forEach( ( el ) => {
			el.disabled = this.isSaving;
		} );

		set( '.lp-notes__card-content', note.content );

		const quote = set( '.lp-notes__card-quote', isHighlight ? note.highlight_text : '' );
		if ( quote ) {
			quote.hidden = ! isHighlight;
		}

		const orphaned = set( '.lp-notes__card-orphaned', this.config.i18n.orphaned );
		if ( orphaned ) {
			orphaned.hidden = ! ( isHighlight && this.orphanedIds.has( String( note.note_id ) ) );
		}

		return card;
	}

	getNote( noteId ) {
		return this.notes.find( ( n ) => String( n.note_id ) === String( noteId ) ) || null;
	}

	getCardNote( card ) {
		if ( ! card ) {
			return null;
		}
		return card.closest( '.lp-notes__all' ) ? {
			note_id: card.dataset.noteId,
			note_type: card.dataset.noteType,
			content: card.querySelector( '.lp-notes__card-content' ).textContent,
		} : this.getNote( card.dataset.noteId );
	}

	handleCardEdit( args ) {
		args.e.preventDefault();
		args.e.stopPropagation();
		const card = args.target.closest( CourseNotes.selectors.card );
		const note = this.getCardNote( card );
		if ( ! note ) {
			return;
		}
		this.openForm( {
			mode: 'edit', noteType: note.note_type, noteId: note.note_id, content: note.content,
		}, card );
	}

	handleCardDelete( args ) {
		args.e.preventDefault();
		args.e.stopPropagation();
		if ( this.isSaving ) {
			return;
		}
		const card = args.target.closest( CourseNotes.selectors.card );
		const popupCard = card?.closest( '.lp-notes__all' ) ? card : null;
		const noteId = this.getCardNote( card )?.note_id;
		if ( ! noteId ) {
			return;
		}
		// SweetAlert has one active popup. Retain the course view while confirming.
		const courseContent = popupCard?.closest( '.lp-notes__all' );
		const scrollTop = courseContent?.querySelector( '.lp-notes__list' )?.scrollTop;
		if ( courseContent ) {
			courseContent.remove();
			this.popupRoot = null;
		}
		SweetAlert.fire( {
			title: this.config.i18n.deleteConfirm,
			icon: 'warning',
			showCancelButton: true,
			confirmButtonColor: 'var(--lp-primary-color, #ffb606)',
		} ).then( ( result ) => {
			const onConfirmed = () => {
				if ( result.isConfirmed && ! this.isSaving ) {
					this.deleteNote( noteId, popupCard );
				}
			};
			if ( courseContent ) {
				this.openCourseNotes( courseContent, () => {
					const list = courseContent.querySelector( '.lp-notes__list' );
					if ( list ) {
						list.scrollTop = scrollTop;
					}
					onConfirmed();
				} );
			} else {
				onConfirmed();
			}
		} );
	}

	deleteNote( noteId, popupCard = null ) {
		this.request( { action: 'lp_note_delete', note_id: noteId }, ( data, message ) => {
			this.notes = this.notes.filter( ( n ) => String( n.note_id ) !== String( noteId ) );
			this.orphanedIds.delete( String( noteId ) );
			if ( String( this.formState?.noteId ) === String( noteId ) ) {
				this.closeForm();
			}
			if ( popupCard ) {
				const group = popupCard.closest( '.lp-notes__lesson' );
				popupCard.remove();
				if ( ! group.querySelector( CourseNotes.selectors.card ) ) {
					const loader = this.popupRoot.querySelector( '.lp-load-ajax-element' );
					const target = loader?.querySelector( '.lp-target' );
					if ( target ) {
						const params = JSON.parse( target.dataset.send );
						if ( this.popupRoot.querySelectorAll( '.lp-notes__lesson' ).length === 1 ) {
							params.args.paged = Math.max( 1, ( params.args.paged || 1 ) - 1 );
						}
						target.dataset.send = JSON.stringify( params );
						loader.classList.remove( 'loaded' );
						window.lpAJAXG.getElements();
					}
					group.remove();
				}
			}
			this.renderList();
			lpToastify.show( message, 'success' );
		} );
	}

	handleCardClick( args ) {
		if ( args.target.closest( 'button, form' ) ) {
			return;
		}

		const noteId = args.target.closest( CourseNotes.selectors.card )?.dataset.noteId;
		this.setActiveNote( noteId );
		const marks = this.showHighlight( noteId );
		if ( marks.length ) {
			marks[ 0 ].scrollIntoView( { behavior: 'smooth', block: 'center' } );
		}
	}

	/**
	 * Mark a note as active in the list and clear the previous content highlight.
	 *
	 * @param {string|number|null} noteId
	 */
	setActiveNote( noteId ) {
		this.activeNoteId = noteId === null || noteId === undefined ? null : String( noteId );
		this.panelRoot?.querySelectorAll( `${ CourseNotes.selectors.card }.is-active` ).forEach( ( el ) => el.classList.remove( 'is-active' ) );
		this.clearHighlight();

		if ( noteId === null || noteId === undefined ) {
			return;
		}

		const card = this.el.list?.querySelector( `[data-note-id="${ CSS.escape( String( noteId ) ) }"]` );
		if ( card ) {
			card.classList.add( 'is-active' );
		}
	}

	// ---------------------------------------------------------------------
	// Highlights
	// ---------------------------------------------------------------------

	findOrphanedNotes() {
		this.orphanedIds.clear();
		if ( ! this.contentRoot ) {
			this.notes.forEach( ( n ) => n.note_type === TYPE_HIGHLIGHT && this.orphanedIds.add( String( n.note_id ) ) );
			return;
		}

		const text = getText( this.contentRoot );
		this.notes.forEach( ( note ) => {
			if ( note.note_type !== TYPE_HIGHLIGHT ) {
				return;
			}

			const position = locateInText( text, note.anchor );
			if ( ! position ) {
				this.orphanedIds.add( String( note.note_id ) );
			}
		} );
	}

	showHighlight( noteId ) {
		const note = this.getNote( noteId );
		if ( ! this.contentRoot || note?.note_type !== TYPE_HIGHLIGHT ) {
			return [];
		}

		const position = locateInText( getText( this.contentRoot ), note.anchor );
		if ( ! position ) {
			return [];
		}

		const marks = wrapPosition( this.contentRoot, position.start, position.end, noteId );
		marks.forEach( ( mark ) => mark.classList.add( 'is-active' ) );
		return marks;
	}

	clearHighlight() {
		const noteIds = new Set( Array.from( this.contentRoot?.querySelectorAll( CourseNotes.selectors.mark ) || [], ( mark ) => mark.dataset.noteId ) );
		noteIds.forEach( ( noteId ) => unwrap( this.contentRoot, noteId ) );
	}

	/**
	 * Show the floating button when the selection is inside the lesson content.
	 */
	updateSelectionButton() {
		if ( this.isSaving ) {
			this.hideSelectionButton();
			return;
		}
		const selection = this.getSelection();
		if ( ! selection || selection.isCollapsed || ! selection.rangeCount ) {
			this.hideSelectionButton();
			return;
		}

		const range = selection.getRangeAt( 0 );
		if ( ! this.contentRoot.contains( range.commonAncestorContainer ) || range.toString().trim().length < MIN_SELECTION_LENGTH ) {
			this.hideSelectionButton();
			return;
		}

		// A long selection can end outside the viewport or a scrolling lesson pane.
		// Anchor the button to the last line that is actually visible.
		const bounds = { left: 0, top: 0, right: document.documentElement.clientWidth, bottom: window.innerHeight };
		for ( let element = this.contentRoot; element; element = element.parentElement ) {
			const style = window.getComputedStyle( element );
			const clipsX = /auto|scroll|hidden|clip/.test( style.overflowX || style.overflow );
			const clipsY = /auto|scroll|hidden|clip/.test( style.overflowY || style.overflow );
			if ( clipsX || clipsY ) {
				const box = element.getBoundingClientRect();
				if ( clipsX ) {
					bounds.left = Math.max( bounds.left, box.left );
					bounds.right = Math.min( bounds.right, box.right );
				}
				if ( clipsY ) {
					bounds.top = Math.max( bounds.top, box.top );
					bounds.bottom = Math.min( bounds.bottom, box.bottom );
				}
			}
		}
		const rects = Array.from( range.getClientRects() );
		if ( ! rects.length ) {
			rects.push( range.getBoundingClientRect() );
		}
		const rect = rects.filter( ( box ) => box.width > 0 && box.height > 0 && box.bottom > bounds.top && box.top < bounds.bottom && box.right > bounds.left && box.left < bounds.right ).pop();
		if ( ! rect ) {
			this.hideSelectionButton();
			return;
		}
		const btn = this.el.selectionBtn;

		this.selectionRange = range.cloneRange();
		btn.hidden = false;

		const maxLeft = bounds.right - btn.offsetWidth - 8;
		const left = Math.max( bounds.left + 8, Math.min( rect.right - ( btn.offsetWidth / 2 ), maxLeft ) );
		const maxTop = bounds.bottom - btn.offsetHeight - 8;
		const below = rect.bottom + 8;
		const top = Math.max( bounds.top + 8, Math.min( below <= maxTop ? below : rect.top - btn.offsetHeight - 8, maxTop ) );
		btn.style.left = `${ left }px`;
		btn.style.top = `${ top }px`;
	}

	hideSelectionButton() {
		if ( this.el.selectionBtn ) {
			this.el.selectionBtn.hidden = true;
		}
	}

	handleSelectionBtnClick( args ) {
		args.e.preventDefault();
		if ( this.isSaving ) {
			return;
		}
		const range = this.selectionRange;
		this.hideSelectionButton();
		if ( ! range ) {
			return;
		}

		const anchor = createAnchor( this.contentRoot, range, SCOPE );
		this.selectionRange = null;
		this.getSelection()?.removeAllRanges();
		if ( ! anchor ) {
			return;
		}

		this.openForm( {
			mode: 'create',
			noteType: TYPE_HIGHLIGHT,
			anchor,
			quote: anchor.quote.exact,
		} );
	}

	// ---------------------------------------------------------------------
	// Helpers
	// ---------------------------------------------------------------------

	/**
	 * @param {Object}   dataSend
	 * @param {Function} onSuccess ( data, message )
	 */
	request( dataSend, onSuccess ) {
		const ajaxHandle = window.lpAJAXG;
		if ( ! ajaxHandle || typeof ajaxHandle.fetchAJAX !== 'function' ) {
			lpToastify.show( this.config.i18n.error, 'error' );
			return;
		}

		this.setSaving( true );
		ajaxHandle.fetchAJAX( dataSend, {
			success: ( response ) => {
				if ( response?.status !== 'success' ) {
					lpToastify.show( response?.message || this.config.i18n.error, 'error' );
					return;
				}

				onSuccess( response.data || {}, response.message || '' );
			},
			error: () => lpToastify.show( this.config.i18n.error, 'error' ),
			completed: () => this.setSaving( false ),
		} );
	}

	/**
	 * Current text selection of the document holding the widget.
	 *
	 * @return {Selection|null} selection
	 */
	getSelection() {
		return this.root.ownerDocument.defaultView.getSelection();
	}

	storageGet( key ) {
		try {
			return window.localStorage.getItem( key );
		} catch ( e ) {
			return null;
		}
	}

	storageSet( key, value ) {
		try {
			window.localStorage.setItem( key, value );
		} catch ( e ) {}
	}
}

const courseNotes = new CourseNotes();
lpUtils.lpOnElementReady( CourseNotes.selectors.root, () => {
	courseNotes.init();
} );
