/**
 * LP Student Notes - learning page widget (lesson only).
 *
 * - Sidebar panel: list, add text note, edit, delete.
 * - Select text in the lesson content → floating "Add Note" → highlight note.
 * - Highlights are rendered as <mark class="lp-note-hl"> from the stored anchors.
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
	getMarks,
	getText,
	locateInText,
	MARK_CLASS,
	unwrap,
	wrapPosition,
} from './course-notes-anchor.js';

const PENDING_ID = 'pending';
const TYPE_TEXT = 'text';
const TYPE_HIGHLIGHT = 'highlight';
const SCOPE = 'lesson_content';
const MIN_SELECTION_LENGTH = 2;
const HELP_STORAGE_KEY = 'lp-notes-help-hidden';

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
		// Note IDs whose highlight could not be found in the lesson.
		this.orphanedIds = new Set();
		// Form state: { mode: 'create'|'edit', noteType, noteId, anchor }.
		this.formState = null;
		this.selectionRange = null;
		this.isSaving = false;
		this.panelRoot = null;
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

		this.renderHighlights();
		this.bindEvents();
		this.openFromHash();
	}

	bindEvents() {
		const s = CourseNotes.selectors;

		lpUtils.eventHandlers( 'click', [
			{ selector: `${ s.content } ${ s.helpDismiss }`, class: this, callBack: this.handleHelpDismiss.name },
			{ selector: `${ s.content } ${ s.add }`, class: this, callBack: this.handleAddClick.name },
			{ selector: `${ s.content } ${ s.formCancel }`, class: this, callBack: this.handleFormCancel.name },
			{ selector: `${ s.content } ${ s.cardEdit }`, class: this, callBack: this.handleCardEdit.name },
			{ selector: `${ s.content } ${ s.cardDelete }`, class: this, callBack: this.handleCardDelete.name },
			{ selector: `${ s.content } ${ s.card }`, class: this, callBack: this.handleCardClick.name },
			{ selector: s.mark, class: this, callBack: this.handleMarkClick.name },
			{ selector: `${ s.root } ${ s.selectionBtn }`, class: this, callBack: this.handleSelectionBtnClick.name },
		] );

		lpUtils.eventHandlers( 'submit', [
			{ selector: `${ s.content } ${ s.form }`, class: this, callBack: this.handleFormSubmit.name },
		] );

		window.addEventListener( 'hashchange', () => this.openFromHash() );

		document.addEventListener( 'lp-learning-content-bar-rendered', ( e ) => {
			if ( e.detail?.item !== 'notes' ) {
				return;
			}
			const content = e.detail.contentBar.querySelector( s.content );
			if ( ! content || this.panelRoot === content ) {
				return;
			}
			this.panelRoot = content;
			for ( const key of [ 'help', 'form', 'formQuote', 'formContent', 'formSave', 'add', 'list', 'empty', 'cardTemplate' ] ) {
				this.el[ key ] = content.querySelector( s[ key ] );
			}
			this.initHelp();
			this.renderList();
		} );
		document.addEventListener( 'lp-learning-content-bar-closed', ( e ) => {
			if ( e.detail?.item === 'notes' && ! this.isSaving ) {
				this.closeForm();
				this.setActiveNote( null );
			}
		} );

		if ( this.config.canEdit && this.contentRoot && this.el.selectionBtn ) {
			// Keep the text selection when pressing the floating button.
			this.el.selectionBtn.addEventListener( 'mousedown', ( e ) => e.preventDefault() );

			const onSelectionEnd = lpUtils.debounce( () => this.updateSelectionButton(), 50 );
			document.addEventListener( 'mouseup', onSelectionEnd );
			document.addEventListener( 'keyup', onSelectionEnd );
			document.addEventListener( 'touchend', onSelectionEnd );
			document.addEventListener( 'scroll', () => this.hideSelectionButton(), true );
			window.addEventListener( 'resize', () => this.hideSelectionButton() );
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
		this.openForm( { mode: 'create', noteType: TYPE_TEXT } );
	}

	handleFormCancel( args ) {
		args.e.preventDefault();
		this.closeForm();
	}

	/**
	 * @param {Object} state { mode, noteType, noteId?, anchor?, content?, quote? }
	 */
	openForm( state ) {
		if ( ! this.config.canEdit ) {
			return;
		}
		this.openPanel();
		if ( ! this.el.form ) {
			return;
		}

		// Switching to another form drops the unsaved highlight of the previous one.
		if ( this.isPendingHighlightForm() ) {
			this.removePendingHighlight();
		}

		this.formState = state;
		this.el.formContent.value = state.content || '';
		this.el.formQuote.textContent = state.quote || '';
		this.el.formQuote.hidden = ! state.quote;
		this.el.form.hidden = false;
		if ( this.el.add ) {
			this.el.add.hidden = true;
		}

		this.el.formContent.focus();
	}

	closeForm() {
		if ( ! this.el.form ) {
			return;
		}

		if ( this.isPendingHighlightForm() ) {
			this.removePendingHighlight();
		}

		this.formState = null;
		this.el.form.hidden = true;
		this.el.formContent.value = '';
		this.el.formQuote.textContent = '';
		this.el.formQuote.hidden = true;
		if ( this.el.add ) {
			this.el.add.hidden = false;
		}
	}

	isPendingHighlightForm() {
		return this.formState?.mode === 'create' && this.formState.noteType === TYPE_HIGHLIGHT;
	}

	handleFormSubmit( args ) {
		args.e.preventDefault();
		if ( ! this.formState || this.isSaving ) {
			return;
		}

		const content = this.el.formContent.value.trim();
		const { mode, noteType, noteId, anchor } = this.formState;

		if ( noteType === TYPE_TEXT && ! content ) {
			lpToastify.show( this.config.i18n.contentEmpty, 'error' );
			this.el.formContent.focus();
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

		this.setSaving( true );
		this.request( dataSend, ( data, message ) => {
			const note = data.note;
			if ( mode === 'edit' ) {
				this.notes = this.notes.map( ( n ) => ( String( n.note_id ) === String( note.note_id ) ? note : n ) );
			} else {
				this.notes.unshift( note );
				if ( noteType === TYPE_HIGHLIGHT && this.contentRoot ) {
					// Keep the pending marks: they become the saved highlight.
					getMarks( this.contentRoot, PENDING_ID ).forEach( ( mark ) => {
						mark.dataset.noteId = String( note.note_id );
						mark.classList.remove( 'is-pending' );
					} );
				}
			}

			this.formState = null;
			this.closeForm();
			this.renderList();
			this.setActiveNote( note.note_id );
			lpToastify.show( message, 'success' );
		}, () => this.setSaving( false ) );
	}

	setSaving( isSaving ) {
		this.isSaving = isSaving;
		if ( this.el.formSave ) {
			this.el.formSave.disabled = isSaving;
			lpUtils.lpSetLoadingEl( this.el.formSave, isSaving ? 1 : 0 );
		}
	}

	// ---------------------------------------------------------------------
	// Cards
	// ---------------------------------------------------------------------

	renderList() {
		if ( ! this.el.list || ! this.el.cardTemplate ) {
			return;
		}

		this.el.list.textContent = '';
		this.notes.forEach( ( note ) => this.el.list.appendChild( this.createCard( note ) ) );

		if ( this.el.empty ) {
			this.el.empty.hidden = this.notes.length > 0;
		}
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

		card.id = `lp-note-${ note.note_id }`;
		card.dataset.noteId = String( note.note_id );
		card.classList.toggle( 'is-highlight', isHighlight );

		const date = set( '.lp-notes__card-date', note.created_at_display );
		date?.setAttribute( 'datetime', `${ String( note.created_at ).replace( ' ', 'T' ) }Z` );
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

	handleCardEdit( args ) {
		args.e.preventDefault();
		args.e.stopPropagation();
		const note = this.getNote( args.target.closest( CourseNotes.selectors.card )?.dataset.noteId );
		if ( ! note ) {
			return;
		}

		this.openForm( {
			mode: 'edit',
			noteType: note.note_type,
			noteId: note.note_id,
			content: note.content,
			quote: note.note_type === TYPE_HIGHLIGHT ? note.highlight_text : '',
		} );
	}

	handleCardDelete( args ) {
		args.e.preventDefault();
		args.e.stopPropagation();
		const note = this.getNote( args.target.closest( CourseNotes.selectors.card )?.dataset.noteId );
		if ( ! note ) {
			return;
		}

		SweetAlert.fire( {
			title: this.config.i18n.deleteConfirm,
			icon: 'warning',
			showCancelButton: true,
			confirmButtonColor: 'var(--lp-primary-color, #ffb606)',
		} ).then( ( result ) => {
			if ( ! result.isConfirmed ) {
				return;
			}

			this.request( { action: 'lp_note_delete', note_id: note.note_id }, ( data, message ) => {
				this.notes = this.notes.filter( ( n ) => String( n.note_id ) !== String( note.note_id ) );
				if ( this.contentRoot ) {
					unwrap( this.contentRoot, note.note_id );
				}

				if ( this.formState?.noteId === note.note_id ) {
					this.closeForm();
				}

				this.renderList();
				lpToastify.show( message, 'success' );
			} );
		} );
	}

	handleCardClick( args ) {
		if ( args.target.closest( 'button' ) ) {
			return;
		}

		const noteId = args.target.closest( CourseNotes.selectors.card )?.dataset.noteId;
		const marks = this.contentRoot ? getMarks( this.contentRoot, noteId ) : [];
		this.setActiveNote( noteId );
		if ( marks.length ) {
			marks[ 0 ].scrollIntoView( { behavior: 'smooth', block: 'center' } );
		}
	}

	handleMarkClick( args ) {
		const noteId = args.target.closest( CourseNotes.selectors.mark )?.dataset.noteId;
		if ( ! noteId || noteId === PENDING_ID || ! this.getNote( noteId ) ) {
			return;
		}

		// Let the user select text inside a highlight without jumping to the panel.
		if ( ! this.getSelection()?.isCollapsed ) {
			return;
		}

		this.openPanel();
		this.setActiveNote( noteId, true );
	}

	/**
	 * Mark a note as active in the list and the content.
	 *
	 * @param {string|number|null} noteId
	 * @param {boolean}            scrollCard
	 */
	setActiveNote( noteId, scrollCard = false ) {
		this.panelRoot?.querySelectorAll( `${ CourseNotes.selectors.card }.is-active` ).forEach( ( el ) => el.classList.remove( 'is-active' ) );
		this.contentRoot?.querySelectorAll( `${ CourseNotes.selectors.mark }.is-active` ).forEach( ( el ) => el.classList.remove( 'is-active' ) );

		if ( noteId === null || noteId === undefined ) {
			return;
		}

		const card = this.el.list?.querySelector( `[data-note-id="${ CSS.escape( String( noteId ) ) }"]` );
		if ( card ) {
			card.classList.add( 'is-active' );
			if ( scrollCard ) {
				card.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
				card.focus( { preventScroll: true } );
			}
		}

		if ( this.contentRoot ) {
			getMarks( this.contentRoot, noteId ).forEach( ( mark ) => mark.classList.add( 'is-active' ) );
		}
	}

	/**
	 * Open the panel on a note from the URL hash (#lp-note-{id}), e.g. from the admin page.
	 */
	openFromHash() {
		const match = window.location.hash.match( /^#lp-note-(\d+)$/ );
		if ( ! match || ! this.getNote( match[ 1 ] ) ) {
			return;
		}

		this.openPanel();
		this.setActiveNote( match[ 1 ], true );
		const marks = this.contentRoot ? getMarks( this.contentRoot, match[ 1 ] ) : [];
		if ( marks.length ) {
			marks[ 0 ].scrollIntoView( { block: 'center' } );
		}
	}

	// ---------------------------------------------------------------------
	// Highlights
	// ---------------------------------------------------------------------

	renderHighlights() {
		this.orphanedIds.clear();
		if ( ! this.contentRoot ) {
			this.notes.forEach( ( n ) => n.note_type === TYPE_HIGHLIGHT && this.orphanedIds.add( String( n.note_id ) ) );
			return;
		}

		// Wrapping highlights preserves the text, so read it once for all anchors.
		const text = getText( this.contentRoot );
		// Oldest first, so newer highlights are nested inside older ones.
		[ ...this.notes ].reverse().forEach( ( note ) => {
			if ( note.note_type !== TYPE_HIGHLIGHT ) {
				return;
			}

			const position = locateInText( text, note.anchor );
			if ( ! position || ! wrapPosition( this.contentRoot, position.start, position.end, note.note_id ).length ) {
				this.orphanedIds.add( String( note.note_id ) );
			}
		} );
	}

	removePendingHighlight() {
		if ( this.contentRoot ) {
			unwrap( this.contentRoot, PENDING_ID );
		}
	}

	/**
	 * Show the floating button when the selection is inside the lesson content.
	 */
	updateSelectionButton() {
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

		const rects = range.getClientRects();
		const rect = rects.length ? rects[ rects.length - 1 ] : range.getBoundingClientRect();
		const btn = this.el.selectionBtn;

		this.selectionRange = range.cloneRange();
		btn.hidden = false;

		const maxLeft = document.documentElement.clientWidth - btn.offsetWidth - 8;
		const left = Math.max( 8, Math.min( rect.right - ( btn.offsetWidth / 2 ), maxLeft ) );
		btn.style.left = `${ left + window.scrollX }px`;
		btn.style.top = `${ rect.bottom + window.scrollY + 8 }px`;
	}

	hideSelectionButton() {
		if ( this.el.selectionBtn ) {
			this.el.selectionBtn.hidden = true;
		}
	}

	handleSelectionBtnClick( args ) {
		args.e.preventDefault();
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

		// Drop a previous unsaved highlight before marking the new one.
		this.removePendingHighlight();
		this.formState = null;
		wrapPosition( this.contentRoot, anchor.position.start, anchor.position.end, PENDING_ID ).forEach( ( mark ) =>
			mark.classList.add( 'is-pending' )
		);

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
	 * @param {Function} onSuccess   ( data, message )
	 * @param {Function} onCompleted
	 */
	request( dataSend, onSuccess, onCompleted ) {
		const ajaxHandle = window.lpAJAXG;
		if ( ! ajaxHandle || typeof ajaxHandle.fetchAJAX !== 'function' ) {
			lpToastify.show( this.config.i18n.error, 'error' );
			onCompleted?.();
			return;
		}

		ajaxHandle.fetchAJAX( dataSend, {
			success: ( response ) => {
				if ( response?.status !== 'success' ) {
					lpToastify.show( response?.message || this.config.i18n.error, 'error' );
					return;
				}

				onSuccess( response.data || {}, response.message || '' );
			},
			error: () => lpToastify.show( this.config.i18n.error, 'error' ),
			completed: () => onCompleted?.(),
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
