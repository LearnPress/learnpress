/**
 * LP Student Notes - text anchoring helpers.
 *
 * A highlight is stored as { scope, quote: { exact, prefix, suffix }, position: { start, end } }
 * where positions are character offsets in the text content of the lesson content root.
 * Wrapping text in <mark> does not change the text content, so offsets stay valid.
 *
 * @since   4.4.9.2
 * @version 1.0.0
 */

export const CONTEXT_LENGTH = 32;
export const MARK_CLASS = 'lp-note-hl';

/**
 * Text nodes under root, in document order.
 *
 * @param {Node} root
 * @return {Text[]} text nodes
 */
export const getTextNodes = ( root ) => {
	const nodes = [];
	const walker = document.createTreeWalker( root, NodeFilter.SHOW_TEXT );
	let node = walker.nextNode();
	while ( node ) {
		nodes.push( node );
		node = walker.nextNode();
	}

	return nodes;
};

/**
 * Full text of root, matching the offsets used by anchors.
 *
 * @param {Node} root
 * @return {string} text
 */
export const getText = ( root ) => getTextNodes( root ).map( ( n ) => n.data ).join( '' );

/**
 * Build an anchor from a DOM range inside root.
 *
 * @param {Element} root
 * @param {Range}   range
 * @param {string}  scope
 * @return {Object|null} anchor
 */
export const createAnchor = ( root, range, scope ) => {
	if ( ! root.contains( range.commonAncestorContainer ) ) {
		return null;
	}

	const before = document.createRange();
	before.selectNodeContents( root );
	before.setEnd( range.startContainer, range.startOffset );

	const start = before.toString().length;
	const exact = range.toString();
	const end = start + exact.length;
	if ( ! exact.trim() ) {
		return null;
	}

	const text = getText( root );

	return {
		scope,
		quote: {
			exact,
			prefix: text.slice( Math.max( 0, start - CONTEXT_LENGTH ), start ),
			suffix: text.slice( end, end + CONTEXT_LENGTH ),
		},
		position: { start, end },
	};
};

/**
 * Number of identical chars from the end of a and b.
 *
 * @param {string} a
 * @param {string} b
 * @return {number} count
 */
const commonSuffixLength = ( a, b ) => {
	let i = 0;
	while ( i < a.length && i < b.length && a[ a.length - 1 - i ] === b[ b.length - 1 - i ] ) {
		i++;
	}

	return i;
};

/**
 * Number of identical chars from the start of a and b.
 *
 * @param {string} a
 * @param {string} b
 * @return {number} count
 */
const commonPrefixLength = ( a, b ) => {
	let i = 0;
	while ( i < a.length && i < b.length && a[ i ] === b[ i ] ) {
		i++;
	}

	return i;
};

/**
 * Find where an anchor is in a text.
 * 1. Stored position still holds the quote → use it.
 * 2. Otherwise pick the occurrence of the quote with the best prefix/suffix match,
 *    then the closest to the stored position.
 *
 * @param {string} text
 * @param {Object} anchor
 * @return {{start: number, end: number}|null} position, null when orphaned
 */
export const locateInText = ( text, anchor ) => {
	const exact = anchor?.quote?.exact || '';
	if ( ! exact ) {
		return null;
	}

	const start = parseInt( anchor?.position?.start ?? -1, 10 );
	if ( start >= 0 && text.slice( start, start + exact.length ) === exact ) {
		return { start, end: start + exact.length };
	}

	const prefix = anchor.quote.prefix || '';
	const suffix = anchor.quote.suffix || '';
	let best = null;
	let index = text.indexOf( exact );

	while ( index !== -1 ) {
		const end = index + exact.length;
		const score =
			commonSuffixLength( text.slice( Math.max( 0, index - prefix.length ), index ), prefix ) +
			commonPrefixLength( text.slice( end, end + suffix.length ), suffix );
		const distance = start >= 0 ? Math.abs( index - start ) : 0;

		if ( ! best || score > best.score || ( score === best.score && distance < best.distance ) ) {
			best = { start: index, end, score, distance };
		}

		index = text.indexOf( exact, index + 1 );
	}

	return best ? { start: best.start, end: best.end } : null;
};

/**
 * Split text nodes so that [start, end) is covered by whole text nodes.
 *
 * @param {Element} root
 * @param {number}  start
 * @param {number}  end
 * @return {Text[]} text nodes covering the range
 */
const getTextNodesInRange = ( root, start, end ) => {
	const result = [];
	let offset = 0;

	for ( let node of getTextNodes( root ) ) {
		const nodeStart = offset;
		const nodeEnd = offset + node.data.length;
		offset = nodeEnd;

		if ( nodeEnd <= start || nodeStart >= end ) {
			continue;
		}

		if ( start > nodeStart ) {
			node = node.splitText( start - nodeStart );
		}

		const nodeLength = Math.min( nodeEnd, end ) - Math.max( nodeStart, start );
		if ( nodeLength < node.data.length ) {
			node.splitText( nodeLength );
		}

		result.push( node );
	}

	return result;
};

/**
 * Wrap the text between start and end in <mark> elements (one per text node).
 * Whitespace-only nodes are skipped to keep structures like lists/tables valid.
 *
 * @param {Element} root
 * @param {number}  start
 * @param {number}  end
 * @param {string}  noteId
 * @return {HTMLElement[]} created marks
 */
export const wrapPosition = ( root, start, end, noteId ) => {
	const marks = [];

	getTextNodesInRange( root, start, end ).forEach( ( node ) => {
		if ( ! node.data.trim() ) {
			return;
		}

		const mark = document.createElement( 'mark' );
		mark.className = MARK_CLASS;
		mark.dataset.noteId = String( noteId );
		node.parentNode.insertBefore( mark, node );
		mark.appendChild( node );
		marks.push( mark );
	} );

	return marks;
};

/**
 * Get marks of a note.
 *
 * @param {Element} root
 * @param {string}  noteId
 * @return {HTMLElement[]} marks
 */
export const getMarks = ( root, noteId ) =>
	Array.from( root.querySelectorAll( `mark.${ MARK_CLASS }` ) ).filter(
		( mark ) => mark.dataset.noteId === String( noteId )
	);

/**
 * Remove marks of a note, keeping their text.
 *
 * @param {Element} root
 * @param {string}  noteId
 */
export const unwrap = ( root, noteId ) => {
	const parents = new Set();

	getMarks( root, noteId ).forEach( ( mark ) => {
		const parent = mark.parentNode;
		while ( mark.firstChild ) {
			parent.insertBefore( mark.firstChild, mark );
		}
		parent.removeChild( mark );
		parents.add( parent );
	} );

	parents.forEach( ( parent ) => parent.normalize() );
};
