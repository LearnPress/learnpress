import {
	InnerBlocks,
	useBlockProps,
	BlockContextProvider,
	useInnerBlocksProps,
	InspectorControls,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalUseBlockPreview as useBlockPreview,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { memo, useMemo, useState, useEffect } from '@wordpress/element';
import { PanelBody, RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import API from '../../../../../js/api.js';

function PostTemplateInnerBlocks( { classList } ) {
	const innerBlocksProps = useInnerBlocksProps();
	return <li { ...innerBlocksProps } />;
}

function PostTemplateBlockPreview( {
	blocks,
	blockContextId,
	classList,
	isHidden,
	setActiveBlockContextId,
} ) {
	const blockPreviewProps = useBlockPreview( {
		blocks,
	} );

	const handleOnClick = () => {
		setActiveBlockContextId( blockContextId );
	};

	const style = {
		display: isHidden ? 'none' : undefined,
	};

	return (
		<li
			{ ...blockPreviewProps }
			tabIndex={ 0 }
			// eslint-disable-next-line jsx-a11y/no-noninteractive-element-to-interactive-role
			role="button"
			onClick={ handleOnClick }
			onKeyPress={ handleOnClick }
			style={ style }
		/>
	);
}

const fetchLearnPressCourses = async ( courseQuery, signal ) => {
	try {
		const url = API.apiCourses;
		let params = '?return_type=json';

		if ( courseQuery ) {
			params += `&${ new URLSearchParams( courseQuery ).toString() }`;
		}

		const response = await fetch( url + params, {
			method: 'GET',
			signal,
		} );

		if ( ! response.ok ) {
			throw new Error( `HTTP error! Status: ${ response.status }` );
		}

		return await response.json();
	} catch ( error ) {
		console.error( 'Error fetching LearnPress courses:', error );
		return null;
	}
};

const MemoizedPostTemplateBlockPreview = memo( PostTemplateBlockPreview );

const Edit = ( { clientId, context, attributes, setAttributes } ) => {
	const blockProps = useBlockProps();
	const [ activeBlockContextId, setActiveBlockContextId ] = useState();
	const [ coursesData, setCoursesData ] = useState();
	const [ listCourses, setListCourses ] = useState( [] );
	const [ loadingAPI, setLoadingAPI ] = useState( 0 );
	const { columns } = attributes;

	// Fetch courses when query parameters change
	useEffect( () => {
		const courseQuery = context.lpCourseQuery ?? {};
		console.log( courseQuery );
		let signal, controller;
		const fetchCourses = async () => {
			try {
				setLoadingAPI( 1 );
				controller = new AbortController();
				signal = controller.signal;

				const data = await fetchLearnPressCourses( courseQuery, signal );
				setCoursesData( data );
				setListCourses( data.data.courses );
			} catch ( error ) {
				if ( error.name !== 'AbortError' ) {
					console.error( 'Failed to fetch courses:', error );
				}
			} finally {
				setLoadingAPI( 0 );
			}
		};

		fetchCourses();

		return () => {
			controller.abort();
		};
	}, [ context.lpCourseQuery?.order_by, context.lpCourseQuery?.limit ] );

	const { blocks } = useSelect(
		( select ) => {
			const { getBlocks } = select( blockEditorStore );

			return {
				blocks: getBlocks( clientId ),
			};
		},
		[ clientId ],
	);

	const blockContexts = useMemo(
		() =>
			listCourses?.map( ( course ) => ( {
				lpCourseData: course,
				courseId: course?.ID,
			} ) ),
		[ listCourses ],
	);

	if ( loadingAPI ) {
		return (
			<ul { ...blockProps }>
				<li>{ __( 'Courses Fetching…', 'learnpress' ) }</li>
			</ul>
		);
	}

	if ( listCourses.length === 0 && ! loadingAPI ) {
		const dataDummy = [
			{
				ID: 1,
				post_title: __( 'Course One', 'learnpress' ),
			},
			{
				ID: 2,
				post_title: __( 'Course two', 'learnpress' ),
			},
		];
		setListCourses( dataDummy );
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Layout Settings' ) }>
					<RangeControl
						label={ __( 'Columns' ) }
						value={ columns }
						onChange={ ( value ) => setAttributes( { columns: value } ) }
						min={ 2 }
						max={ 6 }
					/>
				</PanelBody>
			</InspectorControls>
			<>
				<ul { ...blockProps }>
					{ blockContexts &&
						blockContexts.map( ( blockContext ) => (
							<BlockContextProvider
								key={ blockContext.courseId }
								value={ blockContext }
							>
								{ blockContext.courseId ===
								( activeBlockContextId ||
									blockContexts[ 0 ]?.courseId ) ? (
										<PostTemplateInnerBlocks
											classList={ blockContext.classList }
										/>
									) : null }
								<MemoizedPostTemplateBlockPreview
									blocks={ blocks }
									blockContextId={ blockContext.courseId }
									classList={ blockContext.classList }
									setActiveBlockContextId={
										setActiveBlockContextId
									}
									isHidden={
										blockContext.courseId ===
										( activeBlockContextId ||
											blockContexts[ 0 ]?.courseId )
									}
								/>
							</BlockContextProvider>
						) ) }
				</ul>
				{ context.lpCourseQuery?.pagination && (
					<div className="gutenberg-pagination">
						<div className="pagination-number">
							<nav className="learn-press-pagination navigation pagination">
								<ul className="page-numbers">
									<li>
										<span className="prev page-numbers">
											<i className="lp-icon-arrow-left"></i>
										</span>
									</li>
									<li>
										<span
											aria-current="page"
											className="page-numbers current"
										>
											{ '1' }
										</span>
									</li>
									<li>
										<span className="page-numbers">
											{ '2' }
										</span>
									</li>
									<li>
										<span className="page-numbers">
											{ '3' }
										</span>
									</li>
									<li>
										<i className="lp-icon-arrow-right"></i>
									</li>
								</ul>
							</nav>
						</div>
					</div>
				) }
			</>
		</>
	);
};

export default Edit;
