const CourseCurriculum = {
	SORT_SECTION: function( state, orders ) {
		state.sections = state.sections.map( function( section ) {
			section.order = orders[section.id];

			return section;
		} );
	},
	SET_SECTIONS: function( state, sections ) {
		state.sections = sections;
	},
	ADD_NEW_SECTION: function( state, newSection ) {
		if ( newSection.open === undefined ) {
			newSection.open = true;
		}
		var pos;

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
	ADD_EMPTY_SECTION: function( state, section ) {
		section.open = true;
		state.sections.push( section );
	},
	REMOVE_SECTION: function( state, index ) {
		state.sections.splice( index, 1 );
	},
	REMOVE_SECTION_ITEM: function( state, payload ) {
		var section = state.sections.find( function( section ) {
			return ( section.id === payload.section_id );
		} );

		var items = section.items || [],
			item = payload.item,
			index = -1;
		items.forEach( function( it, i ) {
			if ( it.id === item.id ) {
				index = i;
			}
		} );

		if ( index !== -1 ) {
			if ( item.temp_id ) {
				items[index].id = item.temp_id;
			} else {
				items.splice( index, 1 );
			}
		}
	},
	UPDATE_SECTION_ITEMS: function( state, payload ) {
		var section = state.sections.find( function( section ) {
			return parseInt( section.id ) === parseInt( payload.section_id );
		} );

		if ( ! section ) {
			return;
		}
		section.items = payload.items;
	},
	UPDATE_SECTION_ITEM: function( state, payload ) {

	},

	CLOSE_SECTION: function( state, section ) {
		state.sections.forEach( function( _section, index ) {
			if ( section.id === _section.id ) {
				state.sections[index].open = false;
			}
		} );
	},

	OPEN_SECTION: function( state, section ) {
		state.sections.forEach( function( _section, index ) {
			if ( section.id === _section.id ) {
				state.sections[index].open = true;
			}
		} );
	},

	OPEN_ALL_SECTIONS: function( state ) {
		state.sections = state.sections.map( function( _section ) {
			_section.open = true;

			return _section;
		} );
	},

	CLOSE_ALL_SECTIONS: function( state ) {
		state.sections = state.sections.map( function( _section ) {
			_section.open = false;

			return _section;
		} );
	},

	UPDATE_SECTION_REQUEST: function( state, sectionId ) {
		$Vue.set( state.statusUpdateSection, sectionId, 'updating' );
	},

	UPDATE_SECTION_SUCCESS: function( state, sectionId ) {
		$Vue.set( state.statusUpdateSection, sectionId, 'successful' );
	},

	UPDATE_SECTION_FAILURE: function( state, sectionId ) {
		$Vue.set( state.statusUpdateSection, sectionId, 'failed' );
	},

	UPDATE_SECTION_ITEM_REQUEST: function( state, itemId ) {
		$Vue.set( state.statusUpdateSectionItem, itemId, 'updating' );
	},

	UPDATE_SECTION_ITEM_SUCCESS: function( state, itemId ) {
		$Vue.set( state.statusUpdateSectionItem, itemId, 'successful' );
	},

	UPDATE_SECTION_ITEM_FAILURE: function( state, itemId ) {
		$Vue.set( state.statusUpdateSectionItem, itemId, 'failed' );
	},
	APPEND_EMPTY_ITEM_TO_SECTION: function( state, data ) {
		var section = state.sections.find( function( section ) {
			return parseInt( section.id ) === parseInt( data.section_id );
		} );

		if ( ! section ) {
			return;
		}

		section.items.push( { id: data.item.id, title: data.item.title, type: 'empty-item' } );
	},
	UPDATE_ITEM_SECTION_BY_ID: function( state, data ) {
		var section = state.sections.find( function( section ) {
			return parseInt( section.id ) === parseInt( data.section_id );
		} );

		if ( ! section ) {
			return;
		}

		for ( var i = 0; i < section.items.length; i++ ) {
			try {
				if ( ! section.items[i] ) {
					continue;
				}

				var item_id = section.items[i].id;

				if ( item_id ) {
					if ( data.items[item_id] ) {
						$Vue.set( section.items, i, data.items[item_id] );
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
