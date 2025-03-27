import { __ } from '@wordpress/i18n';
import { Placeholder, SelectControl, PanelBody, RangeControl } from '@wordpress/components';
import { useBlockProps, InspectorControls, InnerBlocks } from '@wordpress/block-editor';
import { useState, useEffect } from '@wordpress/element';

const Edit = ( props ) => {
	const blockProps = useBlockProps();
	const { attributes, setAttributes, clientId } = props;
	const { query, coursesData, queryID } = attributes;

	const QUERY_LOOP_TEMPLATE = [
		[ 'learnpress/course-item', { postTest: 'lp_lesson' }, [
			[ 'core/post-excerpt' ],
			[ 'learnpress/course-title' ],
		] ],
	];

	const fetchLearnPressCourses = async ( query ) => {
		try {
			const url = 'http://lp.test/wp-json/lp/v1/courses/archive-course';
			const params = '?return_type=json';
			const response = await fetch( url + params, {
				method: 'GET',
			} );

			const data = await response.json();
			return data;
		} catch ( error ) {
			console.error( 'Error fetching LearnPress courses:', error );
		}
	};

	// Fetch courses when query parameters change
	useEffect( () => {
		const fetchCourses = async () => {
			try {
				const data = await fetchLearnPressCourses( query );
				setAttributes( { coursesData: data } );
			} catch ( error ) {
				console.error( 'Failed to fetch courses:', error );
			} finally {
			}
		};

		fetchCourses();
	}, [ query ] );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Query Settings' ) }>
					<RangeControl
						label={ __( 'Posts per page' ) }
						value={ query.perPage }
						onChange={ ( perPage ) =>
							setAttributes( { query: { ...query, perPage } } )
						}
						min={ 1 }
						max={ 100 }
					/>
					<SelectControl
						label={ __( 'Order by' ) }
						value={ query.orderBy }
						options={ [
							{ label: __( 'Date' ), value: 'date' },
							{ label: __( 'Title' ), value: 'title' },
							{ label: __( 'Modified' ), value: 'modified' },
						] }
						onChange={ ( orderBy ) =>
							setAttributes( { query: { ...query, orderBy } } )
						}
					/>
					<SelectControl
						label={ __( 'Order' ) }
						value={ query.order }
						options={ [
							{ label: __( 'Descending' ), value: 'desc' },
							{ label: __( 'Ascending' ), value: 'asc' },
						] }
						onChange={ ( order ) =>
							setAttributes( {
								query: { ...query, order },
							} )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<div className="post-query-wrapper">
					<InnerBlocks
						template={ QUERY_LOOP_TEMPLATE }
					/>
				</div>
			</div>
		</>
	);
};

export default Edit;
