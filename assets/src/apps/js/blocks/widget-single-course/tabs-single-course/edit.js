import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();

	return <div { ...blockProps }>
		<ul className="learn-press-nav-tabs course-nav-tabs">
			<li className="course-nav active">
				<span>{ 'Overview' }</span>
			</li>
			<li className="course-nav">
				<span>{ 'Curriculum' }</span>
			</li>
			<li className="course-nav">
				<span>{ 'Instructor' }</span>
			</li>
			<li className="course-nav">
				<span>{ 'FAQs' }</span>
			</li>
		</ul>
		<div className="course-nav-tabs__content">
			<div className="line"></div>
			<div className="line"></div>
			<div className="line"></div>
		</div>
	</div>;
};
