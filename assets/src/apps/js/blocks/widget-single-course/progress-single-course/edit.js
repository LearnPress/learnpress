import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<InspectorControls>
				<PanelBody title="Custom Settings">
					<ToggleGroupControl
						label="Layout"
						isBlock
						value={ props.attributes.layout ?? 'classic' }
						onChange={ ( value ) =>
							props.setAttributes( { layout: value } )
						}
					>
						<ToggleGroupControlOption
							value="classic"
							label="Classic"
						/>
						<ToggleGroupControlOption
							value="modern"
							label="Modern"
						/>
					</ToggleGroupControl>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				{ props.attributes.layout === 'modern' ? (
					<>
						<div className="course-progress">
							<span>{ 'Course passing progress: 0%' }</span>
							<div className="line"></div>
							<span>{ 'Start date: 2025' }</span>
						</div>
					</>
				) : (
					<>
						<div
							className="course-results-progress"
							style={ { marginBottom: '10px' } }
						>
							<div
								className="items-progress"
								style={ {
									display: 'flex',
									justifyContent: 'space-between',
								} }
							>
								<strong>{ 'Lessons completed: ' }</strong>
								<span className="number">{ '0/1' }</span>
							</div>

							<div
								className="items-progress"
								style={ {
									display: 'flex',
									justifyContent: 'space-between',
								} }
							>
								<strong>{ 'Quizzes finished: ' }</strong>
								<span>{ '0/1' }</span>
							</div>

							<div
								className="items-progress"
								style={ {
									display: 'flex',
									justifyContent: 'space-between',
								} }
							>
								<strong>{ 'Course progress: ' }</strong>
								<span>{ '0%' }</span>
							</div>
						</div>
					</>
				) }
			</div>
		</>
	);
};
