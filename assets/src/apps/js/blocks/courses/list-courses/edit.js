import { __ } from '@wordpress/i18n';
import { SelectControl, PanelBody, RangeControl } from '@wordpress/components';
import { useBlockProps, InspectorControls, InnerBlocks } from '@wordpress/block-editor';

const Edit = ( props ) => {
	const blockProps = useBlockProps();
	const { attributes, setAttributes } = props;
	const { courseQuery } = attributes;

	const QUERY_LOOP_TEMPLATE = [
		[ 'learnpress/course-item-template', { postTest: 'lp_lesson' }, [
			[ 'learnpress/course-title' ],
			[ 'core/site-title' ],
		] ],
	];

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Query Settings' ) }>
					<RangeControl
						label={ __( 'Posts per page' ) }
						value={ courseQuery.limit }
						onChange={ ( limit ) =>
							setAttributes( { courseQuery: { ...courseQuery, limit } } )
						}
						min={ 1 }
						max={ 100 }
					/>
					<SelectControl
						label={ __( 'Order by' ) }
						value={ courseQuery.order_by }
						options={ [
							{ label: __( 'Date' ), value: 'post_date' },
							{ label: __( 'Title A-Z' ), value: 'post_title' },
							{ label: __( 'Title Z-A' ), value: 'post_title_desc' },
							{ label: __( 'Price high to low' ), value: 'price' },
							{ label: __( 'Price low to high' ), value: 'price_low' },
							{ label: __( 'Menu Order' ), value: 'menu_order' },
						] }
						onChange={ ( order_by ) =>
							setAttributes( { courseQuery: { ...courseQuery, order_by } } )
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
