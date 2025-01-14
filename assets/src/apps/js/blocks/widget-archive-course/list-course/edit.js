import {
	InnerBlocks,
	useBlockProps,
	InspectorControls,
} from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	SelectControl,
	TextControl,
} from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	const { updateBlockAttributes } = useDispatch( 'core/block-editor' );
	const { clientId } = props;
	const childBlocks = useSelect(
		( select ) => select( 'core/block-editor' ).getBlocks( clientId ),
		[ clientId ],
	);

	const updateLayoutChildBlocks = ( newLayout ) => {
		childBlocks.forEach( ( block ) => {
			if ( block.name === 'learnpress/template-course-archive-course' ) {
				updateBlockAttributes( block.clientId, { layout: newLayout } );
			}
		} );
	};

	const updatePerPageChildBlocks = ( newPerPage ) => {
		childBlocks.forEach( ( block ) => {
			if ( block.name === 'learnpress/template-course-archive-course' ) {
				updateBlockAttributes( block.clientId, { perPage: newPerPage } );
			}
		} );
	};

	const updateOrderByChildBlocks = ( newOrderBy ) => {
		const traverseBlocks = ( blocks ) => {
			blocks.forEach( ( block ) => {
				if ( block.name === 'learnpress/template-course-archive-course' ) {
					updateBlockAttributes( block.clientId, { orderBy: newOrderBy } );
				} else if ( block.name === 'learnpress/order-by-archive-course' ) {
					updateBlockAttributes( block.clientId, { orderBy: newOrderBy } );
				} else if ( block.name === 'core/group' || block.innerBlocks.length > 0 ) {
					traverseBlocks( block.innerBlocks );
				}
			} );
		};

		traverseBlocks( childBlocks );
	};

	const paginationData = [
		{ label: 'Number', value: 'number' },
		{ label: 'Load More', value: 'load-more' },
		{ label: 'Infinite Scroll', value: 'infinite' },
	];

	const layoutData = [
		{ label: 'List', value: 'list' },
		{ label: 'Grid', value: 'grid' },
	];

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
				<PanelBody title="Custom Settings">
					<TextControl
						label="Course Per Page"
						type="number"
						onChange={ ( value ) => {
							props.setAttributes( {
								perPage: value ? parseInt( value, 10 ) : 8,
							} );

							updatePerPageChildBlocks(
								value ? parseInt( value, 10 ) : 8,
							);
						} }
						value={ props.attributes.perPage ?? 8 }
					/>

					<SelectControl
						label="Layout"
						value={ props.attributes.layout ?? 'list' }
						options={ layoutData }
						onChange={ ( value ) => {
							props.setAttributes( {
								layout: value ? value : 'list',
							} );

							updateLayoutChildBlocks( value );
						} }
					/>

					<ToggleControl
						label="Custom Layout"
						help={
							'When enabled, loading AJAX Courses will be disabled.'
						}
						checked={ props.attributes.custom ? true : false }
						onChange={ ( value ) =>
							props.setAttributes( { custom: value ? value : '' } )
						}
					/>

					{ ! props.attributes.custom ? (
						<ToggleControl
							label="Load Ajax"
							help={
								'Do not apply AJAX when reloading the Course Archive page.'
							}
							checked={ props.attributes.load ? true : false }
							onChange={ ( value ) =>
								props.setAttributes( {
									load: value ? value : '',
								} )
							}
						/>
					) : (
						''
					) }

					{ ! props.attributes.custom ? (
						<SelectControl
							label="Pagination"
							value={ props.attributes.pagination ?? 'number' }
							options={ paginationData }
							onChange={ ( value ) =>
								props.setAttributes( {
									pagination: value ? value : 'number',
								} )
							}
						/>
					) : (
						''
					) }

					<SelectControl
						label="Order By Default"
						value={ props.attributes.orderBy ?? 'post_date' }
						options={ orderByData }
						onChange={ ( value ) => {
							props.setAttributes( {
								orderBy: value ? value : 'post_date',
							} );

							updateOrderByChildBlocks( value );
						} }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<div className={ 'list-course ' + props.attributes.layout }>
					<InnerBlocks key={ props.attributes.orderBy } />
				</div>
			</div>
		</>
	);
};
