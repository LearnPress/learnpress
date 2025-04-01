import { __ } from '@wordpress/i18n';
import {
	SelectControl,
	ToggleControl,
	PanelBody,
	RangeControl,
} from '@wordpress/components';
import {
	useBlockProps,
	InspectorControls,
	InnerBlocks,
} from '@wordpress/block-editor';

const Edit = ( props ) => {
	const blockProps = useBlockProps();
	const { attributes, setAttributes, clientId } = props;
	const { courseQuery } = attributes;

	const QUERY_LOOP_TEMPLATE = [ [ 'learnpress/course-item-template' ] ];

	const orderByData = [
		{ label: 'Newly published', value: 'post_date' },
		{ label: 'Title a-z', value: 'post_title' },
		{ label: 'Title z-a', value: 'post_title_desc' },
		{ label: 'Price high to low', value: 'price' },
		{ label: 'Price low to high', value: 'price_low' },
		{ label: 'Popular', value: 'popular' },
		{ label: 'Average Ratings', value: 'rating' },
	];

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Query Settings' ) }>
					<RangeControl
						label={ __( 'Posts per page' ) }
						value={ courseQuery.limit }
						onChange={ ( limit ) =>
							setAttributes( {
								courseQuery: { ...courseQuery, limit },
							} )
						}
						min={ 1 }
						max={ 20 }
					/>
					<SelectControl
						label={ __( 'Order by' ) }
						value={ courseQuery.order_by }
						options={ orderByData }
						onChange={ ( order_by ) => {
							setAttributes( {
								courseQuery: { ...courseQuery, order_by },
							} );
						} }
					/>
					<ToggleControl
						label={ __( 'Related Course' ) }
						checked={ courseQuery.related }
						onChange={ ( related ) => {
							setAttributes( {
								courseQuery: { ...courseQuery, related },
							} );
						} }
					/>
					<ToggleControl
						label={ __( 'Pagination' ) }
						checked={ courseQuery.pagination }
						onChange={ ( pagination ) => {
							setAttributes( {
								courseQuery: { ...courseQuery, pagination },
							} );
						} }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<div className="post-query-wrapper">
					<InnerBlocks template={ QUERY_LOOP_TEMPLATE } />
				</div>
			</div>
		</>
	);
};

export default Edit;
