import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

const Edit = ( props ) => {
	const { attributes, setAttributes, context } = props;
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div class="course-material">
					<h3 class="course-material__title">{ __( 'Course Material', 'learnpress' ) }</h3>
					<div class="lp-list-material">
						<div class="lp-material-skeleton">
							<table class="course-material-table">
								<thead>
									<tr>
										<th class="lp-material-th-file-name">{ __( 'Name', 'learnpress' ) }</th>
										<th class="lp-material-th-file-type">{ __( 'Type', 'learnpress' ) }</th>
										<th class="lp-material-th-file-size">{ __( 'Size', 'learnpress' ) }</th>
										<th class="lp-material-th-file-link">{ __( 'Download', 'learnpress' ) }</th>
									</tr>
								</thead>
								<tbody id="material-file-list">
									<tr class="lp-material-item">
										<td class="lp-material-file-name">Course Materials</td>
										<td class="lp-material-file-type">txt</td>
										<td class="lp-material-file-size">1KB</td>
										<td class="lp-material-file-link">
											<a>
												<i class="lp-icon-file-download btn-download-material"></i>
											</a>
										</td>
									</tr>
								</tbody>
							</table>
							<button class="lp-button lp-loadmore-material">
								{ __( 'Load more', 'learnpress' ) }
							</button>
						</div>
					</div>
				</div>
			</div>
		</>
	);
};

export default Edit;
