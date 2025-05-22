import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

import {
	useBlockProps,
	BlockControls,
	InspectorControls,
	AlignmentToolbar,
	JustifyToolbar,
	BlockVerticalAlignmentToolbar,
} from '@wordpress/block-editor';

const Edit = ( props ) => {
	const { attributes = {}, setAttributes } = props;
	const blockProps = useBlockProps( {
		style: {
			textAlign: attributes.textAlign,
			width: '100%',
		},
	} );

	// classOfDiv to fix align.
	let classOfDiv = blockProps.className;
	classOfDiv = classOfDiv
		.split( ' ' )
		.filter( ( cls ) => cls.startsWith( 'align' ) )
		.join( ' ' );

	return (
		<>
			<BlockControls>
				<AlignmentToolbar
					value={ attributes.textAlign }
					onChange={ ( newAlign ) => setAttributes( { textAlign: newAlign } ) }
				/>
				<JustifyToolbar
					value={ attributes.justifyContent }
					onChange={ ( newJustify ) => setAttributes( { justifyContent: newJustify } ) }
				/>
				<BlockVerticalAlignmentToolbar
					value={ attributes.alignItems }
					onChange={ ( newAlign ) => setAttributes( { alignItems: newAlign } ) }
				/>
			</BlockControls>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'learnpress' ) }>
					<ToggleGroupControl
						label={ __( 'Width', 'learnpress' ) }
						value={ attributes.width || '100' }
						onChange={ ( value ) => {
							setAttributes( {
								width: value || '100',
							} );
						} }
						isBlock={ true }
					>
						<ToggleGroupControlOption value="25" label="25%" />
						<ToggleGroupControlOption value="50" label="50%" />
						<ToggleGroupControlOption value="75" label="75%" />
						<ToggleGroupControlOption value="100" label="100%" />
					</ToggleGroupControl>
				</PanelBody>
			</InspectorControls>
			<div
				className={ classOfDiv }
				style={ {
					display: 'flex',
					textAlign: attributes.textAlign,
					alignItems: attributes.alignItems,
					justifyContent: attributes.justifyContent,
				} }
			>
				<a
					style={ {
						width: attributes.width ? `${ attributes.width }%` : undefined,
					} }
				>
					<button { ...blockProps }>{ __( 'Buy Now', 'learnpress' ) }</button>
				</a>
			</div>
		</>
	);
};

export default Edit;
