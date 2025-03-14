import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<InspectorControls>
				<PanelBody title="Settings">
					<ToggleControl
						label="Open links in new tab"
						checked={ props.attributes.target ? true : false }
						onChange={ ( value ) => {
							props.setAttributes( {
								target: value ? true : false,
							} );
						} }
					/>
					<ToggleControl
						label="Add nofollow attribute"
						checked={ props.attributes.nofollow ? true : false }
						onChange={ ( value ) => {
							props.setAttributes( {
								nofollow: value ? true : false,
							} );
						} }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<div className="instructor-social">
					<i className="lp-user-ico lp-icon-facebook"></i>
					<i className="lp-user-ico lp-icon-twitter"></i>
					<i className="lp-user-ico lp-icon-youtube-play"></i>
					<i className="lp-user-ico lp-icon-linkedin"></i>
				</div>
			</div>
		</>
	);
};
