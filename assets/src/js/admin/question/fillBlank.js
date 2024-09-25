import lplistAPI from '../../api';
import { lpFetchAPI } from '../../utils';
import { updateStatus } from './apiRequests';
import { getQuestionId } from './eventHandlers';

let currentAbortController = null;

const apiRequest = async ( url, method = 'POST', data, callbacks = {}, questionEditEl ) => {
	if ( ! url ) {
		return;
	}

	if ( currentAbortController ) {
		currentAbortController.abort();
	}

	currentAbortController = new AbortController();
	const { signal } = currentAbortController;

	let params = {
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': lpDataAdmin.nonce,
		},
		method,
		body: JSON.stringify( data ),
		signal,
	};

	if ( method === 'GET' ) {
		params = {};
	}

	updateStatus( 'loading', questionEditEl );
	const { success = () => {}, error = () => {}, completed = () => {} } = callbacks;
	await lpFetchAPI( url, params, {
		success,
		error,
		completed: () => {
			if ( completed ) {
				completed();
			}
			updateStatus( 'success', questionEditEl );
		},
	} );
};

const changeTitleAnswerApi = ( questionEditEl ) => {
	const data = convertContentToJSON( questionEditEl );
	const URL = lplistAPI.admin.apiUpdateAnswerTitle;

	apiRequest( URL, 'POST', data, {}, questionEditEl );
};

const updateDataIndex = ( questionEditEl ) => {
	if ( ! questionEditEl ) {
		return;
	}

	const content = questionEditEl.querySelector( '.content-editable' );
	const fibBlankEls = Array.from( content.querySelectorAll( '.fib-blank' ) );
	if ( ! fibBlankEls.length ) {
		return;
	}

	fibBlankEls.forEach( ( fibBlankEl, index ) => {
		fibBlankEl.dataset.index = index + 1;
	} );
};

const setupClickHandler = ( targetElement, questionEditEl ) => {
	targetElement.addEventListener( 'click', () => {
		const selection = window.getSelection();
		if ( ! selection.isCollapsed ) {
			const contentEditEl = questionEditEl.querySelector( '.content-editable' );
			contentEditEl.dataset.isChanged = 'true';
			const selectedText = selection.toString().trim();
			if ( selectedText.length > 0 ) {
				const range = selection.getRangeAt( 0 );
				const bElement = document.createElement( 'b' );
				const id = LP.uniqueId();
				bElement.className = 'fib-blank';
				bElement.id = 'fib-blank-' + id;
				bElement.dataset.id = id;
				bElement.textContent = selectedText;
				bElement.dataset.comparison = 'equal';
				bElement.dataset.matchCase = 'false';
				bElement.dataset.open = 'false';
				const fragment = document.createDocumentFragment();
				fragment.appendChild( bElement );
				range.deleteContents();
				range.insertNode( fragment );
				selection.removeAllRanges();
				updateDataIndex( questionEditEl );
				targetElement.disabled = true;
			}

			renderBlank( questionEditEl );
			checkDisableAllAction( questionEditEl );
			changeTitleAnswerApi( questionEditEl );
		} else {
			targetElement.disabled = true;
		}
	} );
};

const isTextSelectedInElement = ( element ) => {
	return new Promise( ( resolve ) => {
		if ( ! ( element instanceof Element ) ) {
			resolve( false );
			return;
		}

		const selection = window.getSelection();

		if ( selection.isCollapsed ) {
			resolve( false );
			return;
		}

		const range = selection.getRangeAt( 0 );

		if ( element.contains( range.startContainer ) || element.contains( range.endContainer ) ) {
			const newSelection = window.getSelection();
			const selectedText = newSelection.toString().trim();

			if ( newSelection.isCollapsed || selectedText.length === 0 ) {
				resolve( false );
				return;
			}

			const fragment = range.cloneContents();
			const hasBoldTag = fragment.querySelector( 'b' );

			if ( hasBoldTag ) {
				resolve( false );
				return;
			}

			let node = range.startContainer;
			while ( node ) {
				if ( node.nodeType === Node.ELEMENT_NODE && node.tagName === 'B' ) {
					resolve( false );
					return;
				}
				node = node.parentNode;
			}
			node = range.endContainer;
			while ( node ) {
				if ( node.nodeType === Node.ELEMENT_NODE && node.tagName === 'B' ) {
					resolve( false );
					return;
				}
				node = node.parentNode;
			}
			resolve( true );
		} else {
			resolve( false );
		}
	} );
};

const insertNewBlank = ( questionEditEl ) => {
	if ( ! questionEditEl ) {
		return;
	}
	const contentEditEl = questionEditEl.querySelector( '.content-editable' );
	const addNewBlankEl = questionEditEl.querySelector( '.btn-add-new' );

	if ( ! contentEditEl || ! addNewBlankEl ) {
		return;
	}

	contentEditEl.addEventListener( 'mouseup', () => {
		isTextSelectedInElement( contentEditEl ).then( ( result ) => {
			if ( result ) {
				addNewBlankEl.disabled = false;
			} else {
				addNewBlankEl.disabled = true;
			}
		} );
	} );

	setupClickHandler( addNewBlankEl, questionEditEl );
};

const changeContentAnswer = ( questionEditEl ) => {
	const editableEl = questionEditEl.querySelector( '.content-editable' );
	if ( ! editableEl ) {
		return;
	}

	let previousHTML = editableEl.innerHTML;
	editableEl.addEventListener( 'focus', function() {
		if ( editableEl.dataset.isChanged === 'true' ) {
			previousHTML = editableEl.innerHTML;
			editableEl.dataset.isChanged = 'false';
		}
	} );

	editableEl.addEventListener( 'keydown', function( event ) {
		if ( event.key === 'Enter' ) {
			event.preventDefault();
			const currentHTML = editableEl.innerHTML;
			if ( previousHTML !== currentHTML && currentHTML !== '' ) {
				previousHTML = currentHTML;
				checkDisableAllAction( questionEditEl );
				renderBlank( questionEditEl );
				changeTitleAnswerApi( questionEditEl );
			}
		}
	} );

	editableEl.addEventListener( 'blur', function( event ) {
		const currentHTML = editableEl.innerHTML;
		if ( previousHTML !== currentHTML && currentHTML !== '' ) {
			previousHTML = currentHTML;
			const data = convertContentToJSON( questionEditEl );
			checkDisableAllAction( questionEditEl );
			renderBlank( questionEditEl );
			changeTitleAnswerApi( questionEditEl );
		}
	} );
};

const renderBlank = ( el ) => {
	if ( ! el ) {
		return;
	}
	const tableFibBlank = el.querySelector( 'table.fib-blanks' );
	if ( ! tableFibBlank ) {
		return;
	}
	removeAllExceptFibBlankWithDisplayNone( tableFibBlank );

	const content = el.querySelector( '.content-editable' );
	const fibBlankEls = Array.from( content.querySelectorAll( '.fib-blank' ) );
	if ( ! fibBlankEls.length ) {
		return;
	}

	const fibBlankTemplateEl = tableFibBlank.querySelector( '.fib-blank' );
	if ( ! fibBlankTemplateEl ) {
		return;
	}

	fibBlankEls.forEach( ( fibBlankEl, index ) => {
		const dataFibBlank = {
			id: fibBlankEl.dataset.id ?? '',
			index: index + 1 ?? '',
			value: fibBlankEl.innerText ?? '',
			comparison: fibBlankEl.dataset?.comparison ?? '',
			matchCase: fibBlankEl.dataset?.matchCase ?? '',
		};
		const cloneEl = fibBlankTemplateEl.cloneNode( true );
		cloneEl.style.display = 'table-row-group';
		const positionEl = cloneEl.querySelector( '.blank-position' );
		if ( positionEl ) {
			positionEl.innerText = '#' + dataFibBlank.index;
		}
		const fillEl = cloneEl.querySelector( '.blank-fill input' );
		if ( fillEl ) {
			fillEl.dataset.id = 'fib-blank-' + dataFibBlank.id;
			fillEl.value = dataFibBlank.value;
			let previousValue = fillEl.value;
			fillEl.addEventListener( 'blur', ( e ) => {
				const currentValue = fillEl.value;
				if ( previousValue !== currentValue && currentValue !== '' ) {
					previousValue = currentValue;
					fibBlankEl.innerText = currentValue;
					changeTitleAnswerApi( el );
				}
			} );
			fillEl.addEventListener( 'keydown', ( e ) => {
				if ( e.key === 'Enter' ) {
					e.preventDefault();
					const currentValue = fillEl.value;
					if ( previousValue !== currentValue && currentValue !== '' ) {
						previousValue = currentValue;
						fibBlankEl.innerText = currentValue;
						changeTitleAnswerApi( el );
					}
				}
			} );
		}

		const optionEl = cloneEl.querySelector( '.blank-actions .option' );
		const removeBlankEl = cloneEl.querySelector( '.blank-actions .delete' );
		const ulOption = cloneEl.querySelector( '.blank-options ul' );
		if ( removeBlankEl ) {
			removeBlankEl.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				const id = fillEl.dataset.id;
				removeBlank( id, el );
			} );
		}

		if ( optionEl && ulOption ) {
			optionEl.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				if ( ulOption.style.display === 'block' ) {
					ulOption.style.display = '';
				} else {
					ulOption.style.display = 'block';
				}
			} );
		}

		const matchCaseEl = cloneEl.querySelector( 'input[type="checkbox"]' );
		if ( matchCaseEl ) {
			if ( dataFibBlank.matchCase === 'true' ) {
				matchCaseEl.checked = true;
			}

			matchCaseEl.addEventListener( 'click', function() {
				if ( matchCaseEl.checked ) {
					fibBlankEl.dataset.matchCase = 'true';
				} else {
					fibBlankEl.dataset.matchCase = 'false';
				}
				changeTitleAnswerApi( el );
			} );
		}

		let comparisonEl;
		if ( dataFibBlank.comparison ) {
			comparisonEl = cloneEl.querySelector( `input[type="radio"][value=${ dataFibBlank.comparison }]` );
		} else {
			comparisonEl = cloneEl.querySelector( `input[type="radio"][value=""]` );
			if ( ! comparisonEl ) {
				comparisonEl = cloneEl.querySelector( `input[type="radio"][value="equal"]` );
			}
		}

		if ( comparisonEl ) {
			comparisonEl.checked = true;
		}

		const comparisonEls = Array.from( cloneEl.querySelectorAll( 'input[type="radio"]' ) );
		comparisonEls.forEach( ( comparisonEl ) => {
			comparisonEl.name = 'comparison-' + dataFibBlank.id;
			comparisonEl.addEventListener( 'click', ( e ) => {
				if ( comparisonEl.checked ) {
					fibBlankEl.dataset.comparison = comparisonEl.value;
				}
				changeTitleAnswerApi( el );
			} );
		} );
		tableFibBlank.appendChild( cloneEl );
	} );
};

const checkDisableAllAction = ( questionEditEl ) => {
	if ( ! questionEditEl ) {
		return;
	}

	const addNewBlankEl = questionEditEl.querySelector( '.btn-remove-all' );
	if ( addNewBlankEl ) {
		const contentEditEl = questionEditEl.querySelector( '.content-editable' );
		const blankEl = contentEditEl.querySelector( '.fib-blank' );
		if ( blankEl ) {
			addNewBlankEl.disabled = false;
		} else {
			addNewBlankEl.disabled = true;
		}
	}

	const clearEl = questionEditEl.querySelector( '.btn-clear' );
	if ( clearEl ) {
		const contentEditEl = questionEditEl.querySelector( '.content-editable' );
		if ( contentEditEl.innerText ) {
			clearEl.disabled = false;
		} else {
			clearEl.disabled = true;
		}
	}
};

const removeAllBlank = ( questionEditEl ) => {
	if ( ! questionEditEl ) {
		return;
	}

	const contentEditEl = questionEditEl.querySelector( '.content-editable' );
	const removeAllBlankEl = questionEditEl.querySelector( '.btn-remove-all' );

	if ( ! contentEditEl || ! removeAllBlankEl ) {
		return;
	}

	removeAllBlankEl.addEventListener( 'click', ( e ) => {
		e.preventDefault();
		const isConfirmed = confirm( 'Bạn có chắc chắn muốn tiếp tục không?' );
		if ( ! isConfirmed ) {
			return;
		}

		removeAllBlankEl.disabled = true;

		const fibBlankEls = Array.from( contentEditEl.querySelectorAll( '.fib-blank' ) );
		if ( ! fibBlankEls.length ) {
			return;
		}

		fibBlankEls.forEach( ( fibBlankEl ) => {
			const content = fibBlankEl.innerHTML;
			const textNode = document.createTextNode( content );
			fibBlankEl.replaceWith( textNode );
		} );

		renderBlank( questionEditEl );
		changeTitleAnswerApi( questionEditEl );
	} );
};

const clearContent = ( questionEditEl ) => {
	if ( ! questionEditEl ) {
		return;
	}

	const clearEl = questionEditEl.querySelector( '.btn-clear' );
	if ( ! clearEl ) {
		return;
	}

	clearEl.addEventListener( 'click', ( e ) => {
		e.preventDefault();
		const isConfirmed = confirm( 'Bạn có chắc chắn muốn tiếp tục không?' );
		if ( ! isConfirmed ) {
			return;
		}

		const contentEL = questionEditEl.querySelector( '.content-editable' );
		if ( ! contentEL ) {
			return;
		}
		contentEL.innerText = '';
		checkDisableAllAction( questionEditEl );
		renderBlank( questionEditEl );
		changeTitleAnswerApi( questionEditEl );
	} );
};

const removeBlank = ( id, questionEditEl ) => {
	if ( ! id || ! questionEditEl ) {
		return;
	}

	const contentEL = questionEditEl.querySelector( '.content-editable' );
	if ( ! contentEL ) {
		return;
	}
	const blankEl = contentEL.querySelector( `#${ id }` );
	if ( ! blankEl ) {
		return;
	}

	const content = blankEl.innerHTML;
	const textNode = document.createTextNode( content );
	blankEl.replaceWith( textNode );
	renderBlank( questionEditEl );
	changeTitleAnswerApi( questionEditEl );
	checkDisableAllAction( questionEditEl );
};

function removeAllExceptFibBlankWithDisplayNone( parentElement ) {
	const children = Array.from( parentElement.children );
	children.forEach( ( child ) => {
		const isFibBlankWithDisplayNone = child.classList.contains( 'fib-blank' ) &&
            child.style.display === 'none';
		if ( ! isFibBlankWithDisplayNone ) {
			parentElement.removeChild( child );
		}
	} );
}

function convertContentToJSON( questionEditEl ) {
	if ( ! questionEditEl ) {
		// console.error('Element with class "content-editable" not found.');
		return;
	}
	const contentEditable = questionEditEl.querySelector( '.content-editable' );
	const questionAnswerId = contentEditable.getAttribute( 'data-answer-id' );
	const order = contentEditable.getAttribute( 'data-order' );
	const isTrue = contentEditable.getAttribute( 'data-is-true' );
	const value = contentEditable.getAttribute( 'data-value' );
	const questionId = getQuestionId( questionEditEl );
	const blanks = {};
	let htmlContent = contentEditable.innerHTML;

	const blanksElements = contentEditable.querySelectorAll( 'b.fib-blank' );

	if ( blanksElements.length ) {
		blanksElements.forEach( ( element ) => {
			const id = element.getAttribute( 'data-id' );
			const fill = element.innerHTML;
			const comparison = element.getAttribute( 'data-comparison' );
			const open = element.getAttribute( 'data-open' ) === 'true';
			const matchCase = element.getAttribute( 'data-match-case' ) === 'true' ? 1 : 0;
			const index = element.getAttribute( 'data-index' );

			blanks[ id ] = {
				fill,
				id,
				comparison,
				match_case: matchCase,
				index: parseInt( index, 10 ),
				open,
			};

			const replacement = `[fib fill="${ fill }" id="${ id }" comparison="${ comparison }" match_case="${ matchCase }" open="${ open }"]`;
			htmlContent = htmlContent.replace( element.outerHTML, replacement );
		} );
	}

	const jsonResult = {
		questionId,
		answer: {
			order,
			value,
			is_true: isTrue,
			question_answer_id: questionAnswerId,
			title: htmlContent,
			blanks,
		},
	};

	return jsonResult;
}

export { insertNewBlank, renderBlank, checkDisableAllAction, removeAllBlank, clearContent, changeContentAnswer };
