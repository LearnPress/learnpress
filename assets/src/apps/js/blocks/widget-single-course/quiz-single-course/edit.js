import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	ToggleControl,
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
					{ props.attributes.layout !== 'modern' && (
						<ToggleControl
							label="Show Only Number"
							checked={
								props.attributes.showOnlyNumber ? true : false
							}
							onChange={ ( value ) =>
								props.setAttributes( {
									showOnlyNumber: value ? value : '',
								} )
							}
						/>
					) }
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				{ props.attributes.layout === 'modern' ? (
					<>
						<span>{ 'Quiz: 68 Quizzes' }</span>
					</>
				) : (
					<>
						{ props.attributes.layout === 'modern' ? (
							<>
								<span>{ 'Lesson: 68 Lessons' }</span>
							</>
						) : (
							<>
								<span>
									{ props.attributes.showOnlyNumber
										? '68'
										: '68 Quiz' }
								</span>
							</>
						) }
					</>
				) }
			</div>
		</>
	);
};
