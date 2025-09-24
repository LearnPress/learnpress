import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	SelectControl,
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

export const edit = ( props ) => {
	const { attributes, setAttributes, context } = props;
	const blockProps = useBlockProps();
	const { lpCourseData } = context;
	const courseImage =
		lpCourseData?.image ||
		'<div class="course-img"><img src="/wp-content/plugins/learnpress/assets/images/no-image.png" alt="course thumbnail placeholder"</div>';

	const sizeOption = [
		{ label: 'Thumbnail', value: 'thumbnail' },
		{ label: 'Medium', value: 'medium' },
		{ label: 'Large', value: 'large' },
		{ label: 'Full', value: 'full' },
		{ label: 'Custom', value: 'custom' },
	];

	const getStyledCourseImage = () => {
		if (
			lpCourseData?.image &&
			attributes.size === 'custom' &&
			attributes.customWidth === 500 &&
			attributes.customHeight === 300
		) {
			return lpCourseData?.image;
		}

		let width = 2560;
		let height = 2560;

		if ( attributes.size === 'thumbnail' ) {
			width = 150;
			height = 150;
		}

		if ( attributes.size === 'medium' ) {
			width = 300;
			height = 300;
		}

		if ( attributes.size === 'large' ) {
			width = 1024;
			height = 724;
		}

		if ( attributes.size === 'full' ) {
			width = 2560;
			height = 2560;
		}

		if ( attributes.size === 'custom' ) {
			if ( attributes.customWidth && attributes.customWidth > 0 ) {
				width = attributes.customWidth;
			}
			if ( attributes.customHeight && attributes.customHeight > 0 ) {
				height = attributes.customHeight;
			}

			if ( attributes.customWidth == 0 ) {
				width = 2560;
			}

			if ( attributes.customHeight == 0 ) {
				width = 2560;
			}
		}

		const ratio = width / height;
		return `<div class="course-img"><img src="/wp-content/plugins/learnpress/assets/images/no-image.png" width="${ width }" height="${ height }" style="aspect-ratio: ${ ratio }; object-fit:cover;" alt="course thumbnail placeholder"</div>`;
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'learnpress' ) }>
					<SelectControl
						label={ __( 'Size', 'learnpress' ) }
						value={ props.attributes.size }
						options={ sizeOption }
						onChange={ ( value ) => {
							if ( value !== 'custom' ) {
								setAttributes( { customWidth: 500, customHeight: 300 } );
							}
							setAttributes( { size: value } );
						} }
					/>

					{ props.attributes.size === 'custom' ? (
						<div style={ { display: 'flex', gap: '12px' } }>
							<InputControl
								label={ __( 'Width', 'learnpress' ) }
								type="number"
								min={ 0 }
								style={ { flex: 1 } }
								placeholder={ __( 'Auto', 'learnpress' ) }
								suffix={ <div style={ { marginRight: '8px' } }>{ __( 'px', 'learnpress' ) }</div> }
								value={ props.attributes.customWidth }
								onChange={ ( value ) => {
									const numValue = parseInt( value, 10 );
									if ( ! isNaN( Number( numValue ) ) && numValue >= 0 ) {
										setAttributes( { customWidth: Number( numValue ) } );
									} else {
										setAttributes( { customWidth: 500 } );
									}
								} }
							/>
							<InputControl
								label={ __( 'Height', 'learnpress' ) }
								type="number"
								min={ 0 }
								style={ { flex: 1 } }
								placeholder={ __( 'Auto', 'learnpress' ) }
								suffix={ <div style={ { marginRight: '8px' } }>{ __( 'px', 'learnpress' ) }</div> }
								value={ props.attributes.customHeight }
								onChange={ ( value ) => {
									const numValue = parseInt( value, 10 );
									if ( ! isNaN( Number( numValue ) ) && numValue >= 0 ) {
										setAttributes( { customHeight: Number( numValue ) } );
									} else {
										setAttributes( { customHeight: 300 } );
									}
								} }
							/>
						</div>
					) : (
						''
					) }

					<ToggleControl
						label={ __( 'Make the image a link', 'learnpress' ) }
						checked={ props.attributes.isLink ? true : false }
						onChange={ ( value ) => {
							props.setAttributes( {
								isLink: value ? true : false,
							} );
						} }
					/>
					{ props.attributes.isLink ? (
						<ToggleControl
							label={ __( 'Open is new tab', 'learnpress' ) }
							checked={ props.attributes.target ? true : false }
							onChange={ ( value ) => {
								props.setAttributes( {
									target: value ? true : false,
								} );
							} }
						/>
					) : (
						''
					) }
				</PanelBody>
			</InspectorControls>
			<a>
				<div
					{ ...blockProps }
					dangerouslySetInnerHTML={ {
						__html: getStyledCourseImage() || courseImage,
					} }
				></div>
			</a>
		</>
	);
};
