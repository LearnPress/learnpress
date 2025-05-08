import { __ } from '@wordpress/i18n';
import {
	SelectControl,
	ToggleControl,
	PanelBody,
	RangeControl,
	TextControl,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';
import { useBlockProps, InspectorControls, InnerBlocks } from '@wordpress/block-editor';

const Edit = ( props ) => {
	const blockProps = useBlockProps();
	const { attributes, setAttributes, clientId } = props;
	const { courseQuery } = attributes;

	const QUERY_LOOP_TEMPLATE = [ [ 'learnpress/course-item-template' ] ];
	const resetAllTaxonomy = () => {
		setAttributes( {
			courseQuery: {
				...courseQuery,
				term_id: '',
				tag_id: '',
			},
		} );
	};

	const orderByData = [
		{ label: 'Newly published', value: 'post_date' },
		{ label: 'Title a-z', value: 'post_title' },
		{ label: 'Title z-a', value: 'post_title_desc' },
		{ label: 'Price high to low', value: 'price' },
		{ label: 'Price low to high', value: 'price_low' },
		{ label: 'Popular', value: 'popular' },
		{ label: 'Average Ratings', value: 'rating' },
	];
	const paginationTypes = [
		{ label: __( 'Number', 'learnpress' ), value: 'number' },
		{ label: __( 'Load more', 'learnpress' ), value: 'load-more' },
		{ label: __( 'Infinite Scroll', 'learnpress' ), value: 'infinite' },
	];

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Query Settings', 'learnpress' ) }>
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
					<ToggleControl
						label={ __( 'Related Course', 'learnpress' ) }
						checked={ courseQuery.related }
						onChange={ ( related ) => {
							if ( related ) {
								setAttributes( {
									courseQuery: {
										...courseQuery,
										term_id: '',
										tag_id: '',
										pagination: false,
										order_by: 'post_date',
										related,
									},
								} );
							} else {
								setAttributes( {
									courseQuery: { ...courseQuery, related },
								} );
							}
						} }
					/>
					<ToggleControl
						label={ __( 'Enable AJAX', 'learnpress' ) }
						checked={ courseQuery.load_ajax }
						onChange={ ( load_ajax ) => {
							setAttributes( {
								courseQuery: {
									...courseQuery,
									load_ajax,
									load_ajax_after: ! load_ajax ? false : courseQuery.load_ajax_after,
								},
							} );
						} }
					/>

					{ ! courseQuery.related && (
						<SelectControl
							label={ __( 'Order by', 'learnpress' ) }
							value={ courseQuery.order_by }
							options={ orderByData }
							onChange={ ( order_by ) => {
								setAttributes( {
									courseQuery: { ...courseQuery, order_by },
								} );
							} }
						/>
					) }

					{ ! courseQuery.related && (
						<ToggleControl
							label={ __( 'Pagination', 'learnpress' ) }
							checked={ courseQuery.pagination }
							onChange={ ( pagination ) => {
								setAttributes( {
									courseQuery: {
										...courseQuery,
										pagination,
										pagination_type: ! pagination ? 'number' : courseQuery.pagination_type,
									},
								} );
							} }
						/>
					) }

					{ courseQuery.pagination && (
						<SelectControl
							label={ __( 'Pagination Type', 'learnpress' ) }
							value={ courseQuery.pagination_type }
							options={ paginationTypes }
							onChange={ ( pagination_type ) => {
								setAttributes( {
									courseQuery: { ...courseQuery, pagination_type },
								} );
							} }
						/>
					) }
				</PanelBody>
				{ /* { ! courseQuery.related && (
					<ToolsPanel label={ __( 'Filter', 'learnpress' ) } resetAll={ resetAllTaxonomy }>
						<ToolsPanelItem
							label={ __( 'Taxonomy', 'learnpress' ) }
							onSelect={ () => resetAllTaxonomy() }
							hasValue={ () => !! courseQuery.term_id || !! courseQuery.tag_id }
							onDeselect={ () => resetAllTaxonomy() }
						>
							<TextControl
								label={ __( 'Category', 'learnpress' ) }
								onChange={ ( term_id ) => {
									setAttributes( {
										courseQuery: {
											...courseQuery,
											term_id,
										},
									} );
								} }
								value={ courseQuery.term_id ?? '' }
							/>

							<TextControl
								label={ __( 'Tag', 'learnpress' ) }
								onChange={ ( tag_id ) => {
									setAttributes( {
										courseQuery: { ...courseQuery, tag_id },
									} );
								} }
								value={ courseQuery.tag_id ?? '' }
							/>
						</ToolsPanelItem>
					</ToolsPanel>
				) } */ }
			</InspectorControls>
			<div { ...blockProps }>
				<InnerBlocks template={ QUERY_LOOP_TEMPLATE } />
			</div>
		</>
	);
};

export default Edit;
