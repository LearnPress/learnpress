import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
export const edit = ( props ) => {
	const { attributes, setAttributes, context } = props;
	const blockProps = useBlockProps();
	const orderByValue = context.lpCourseQuery?.order_by || 'post_date';
	const orderByData = [
		{ label: 'Newly published', value: 'post_date' },
		{ label: 'Title a-z', value: 'post_title' },
		{ label: 'Title z-a', value: 'post_title_desc' },
		{ label: 'Price high to low', value: 'price' },
		{ label: 'Price low to high', value: 'price_low' },
		{ label: 'Popular', value: 'popular' },
		{ label: 'Average Ratings', value: 'rating' },
	];

	const orderByLabel =
		orderByData.find( ( item ) => item.value === orderByValue )?.label ??
		'Unknown';

	return (
		<>
			<div { ...blockProps }>
				<div className="courses-order-by-wrapper">
					<span>{ orderByLabel }</span>
				</div>
			</div>
		</>
	);
};
