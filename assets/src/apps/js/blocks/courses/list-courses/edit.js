import { __ } from '@wordpress/i18n';
import { Placeholder, SelectControl, PanelBody, RangeControl } from '@wordpress/components';
import { useBlockProps, InspectorControls, InnerBlocks } from '@wordpress/block-editor';
import { useState, useEffect } from '@wordpress/element';

const Edit = ( props ) => {
	const blockProps = useBlockProps();
	const { attributes, setAttributes } = props;
	const { query, coursesData } = attributes;

	const QUERY_LOOP_TEMPLATE = [
		[ 'learnpress/course-item-template', { postTest: 'lp_lesson' }, [
			[ 'learnpress/course-title' ],
			[ 'core/site-title' ],
		] ],
	];

	const fetchLearnPressCourses = async ( query ) => {
		try {
			const url = 'http://lp.test/wp-json/lp/v1/courses/archive-course';
			const params = '?return_type=json';
			const response = await fetch( url + params, {
				method: 'GET',
			} );

			return await response.json();
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
						value={ query.limit }
						onChange={ ( limit ) =>
							setAttributes( { query: { ...query, limit } } )
						}
						min={ 1 }
						max={ 100 }
					/>
					<SelectControl
						label={ __( 'Order by' ) }
						value={ query.order_by }
						options={ [
							{ label: __( 'Date' ), value: 'post_date' },
							{ label: __( 'Title A-Z' ), value: 'post_title' },
							{ label: __( 'Title Z-A' ), value: 'post_title_desc' },
							{ label: __( 'Price high to low' ), value: 'price' },
							{ label: __( 'Price low to high' ), value: 'price_low' },
							{ label: __( 'Menu Order' ), value: 'menu_order' },
						] }
						onChange={ ( order_by ) =>
							setAttributes( { query: { ...query, order_by } } )
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
