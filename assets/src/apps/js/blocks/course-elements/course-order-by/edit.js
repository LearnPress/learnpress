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
					<select name="order_by" className="block-courses-order-by">
						<option value="post_date" selected="selected">
							Newly published
						</option>
						<option value="post_title">Title a-z</option>
						<option value="post_title_desc">Title z-a</option>
						<option value="price">Price high to low</option>
						<option value="price_low">Price low to high</option>
						<option value="popular">Popular</option>
						<option value="rating">Average Ratings</option>
					</select>
				</div>
			</div>
		</>
	);
};
