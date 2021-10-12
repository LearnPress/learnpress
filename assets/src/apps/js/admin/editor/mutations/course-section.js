const CourseCurriculum = {
	SORT_SECTION( state, orders ) {
		state.sections = state.sections.map( function( section ) {
			section.order = orders[ section.id ];

			return section;
		} );
	},
	SET_SECTIONS( state, sections ) {
		state.sections = sections;
	},
	ADD_NEW_SECTION( state, newSection ) {
		if ( newSection.open === undefined ) {
			newSection.open = true;
		}
		let pos;

		if ( newSection.temp_id ) {
			state.sections.map( function( section, i ) {
				if ( newSection.temp_id == section.id ) {
					pos = i;
					return false;
				}
			} );
		}

		if ( pos !== undefined ) {
			$Vue.set( state.sections, pos, newSection );
		} else {
			state.sections.push( newSection );
		}
	},
	ADD_EMPTY_SECTION( state, section ) {
		section.open = true;
		state.sections.push( section );
	},
	REMOVE_SECTION( state, index ) {
		state.sections.splice( index, 1 );
	},
	REMOVE_SECTION_ITEM( state, payload ) {
		const section = state.sections.find( function( section ) {
			return ( section.id === payload.section_id );
		} );

		let items = section.items || [],
			item = payload.item,
			index = -1;
		items.forEach( function( it, i ) {
			if ( it.id === item.id ) {
				index = i;
			}
		} );

		if ( index !== -1 ) {
			if ( payload.oldId !== undefined ) {
				items[ index ].id = payload.oldId;
				return;
			}

			if ( item.temp_id ) {
				items[ index ].id = item.temp_id;
			} else {
				items.splice( index, 1 );
			}
		}
	},
	UPDATE_SECTION_ITEMS( state, payload ) {
		const section = state.sections.find( function( section ) {
			return parseInt( section.id ) === parseInt( payload.section_id );
		} );

		if ( ! section ) {
			return;
		}
		section.items = payload.items;
	},
	UPDATE_SECTION_ITEM( state, payload ) {

	},

	CLOSE_SECTION( state, section ) {
		state.sections.forEach( function( _section, index ) {
			if ( section.id === _section.id ) {
				state.sections[ index ].open = false;
			}
		} );
	},

	OPEN_SECTION( state, section ) {
		state.sections.forEach( function( _section, index ) {
			if ( section.id === _section.id ) {
				state.sections[ index ].open = true;
			}
		} );
	},

	OPEN_ALL_SECTIONS( state ) {
		state.sections = state.sections.map( function( _section ) {
			_section.open = true;

			return _section;
		} );
	},

	CLOSE_ALL_SECTIONS( state ) {
		state.sections = state.sections.map( function( _section ) {
			_section.open = false;

			return _section;
		} );
	},

	UPDATE_SECTION_REQUEST( state, sectionId ) {
		$Vue.set( state.statusUpdateSection, sectionId, 'updating' );
	},

	UPDATE_SECTION_SUCCESS( state, sectionId ) {
		$Vue.set( state.statusUpdateSection, sectionId, 'successful' );
	},

	UPDATE_SECTION_FAILURE( state, sectionId ) {
		$Vue.set( state.statusUpdateSection, sectionId, 'failed' );
	},

	UPDATE_SECTION_ITEM_REQUEST( state, itemId ) {
		$Vue.set( state.statusUpdateSectionItem, itemId, 'updating' );
	},

	UPDATE_SECTION_ITEM_SUCCESS( state, itemId ) {
		$Vue.set( state.statusUpdateSectionItem, itemId, 'successful' );
	},

	UPDATE_SECTION_ITEM_FAILURE( state, itemId ) {
		$Vue.set( state.statusUpdateSectionItem, itemId, 'failed' );
	},
	APPEND_EMPTY_ITEM_TO_SECTION( state, data ) {
		const section = state.sections.find( function( section ) {
			return parseInt( section.id ) === parseInt( data.section_id );
		} );

		if ( ! section ) {
			return;
		}

		section.items.push( { id: data.item.id, title: data.item.title, type: 'empty-item' } );
	},
	UPDATE_ITEM_SECTION_BY_ID( state, data ) {
		const section = state.sections.find( function( section ) {
			return parseInt( section.id ) === parseInt( data.section_id );
		} );

		if ( ! section ) {
			return;
		}

		for ( let i = 0; i < section.items.length; i++ ) {
			try {
				if ( ! section.items[ i ] ) {
					continue;
				}

				const item_id = section.items[ i ].id;

				if ( item_id ) {
					if ( data.items[ item_id ] ) {
						$Vue.set( section.items, i, data.items[ item_id ] );
					}
				}
			} catch ( ex ) {
				console.log( ex );
			}
		}

		//section.items.push({id: data.item.id, title: data.item.title, type: 'empty-item'});
	},
};

export default CourseCurriculum;
