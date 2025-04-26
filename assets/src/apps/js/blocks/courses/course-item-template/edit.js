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
import classnames from 'classnames';
import { useSelect } from '@wordpress/data';
import { memo, useMemo, useState, useEffect } from '@wordpress/element';
import {
	PanelBody,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import API from '../../../../../js/api.js';
const TEMPLATE_DEFAULT = [
	[ 'learnpress/course-image' ],
	[ 'learnpress/course-title' ],
	[ 'learnpress/course-price' ],
];

function PostTemplateInnerBlocks( { classList } ) {
	const innerBlocksProps = useInnerBlocksProps(
		{ className: classnames( 'wp-block-learnpress-course-item-template' ) },
		{ template: TEMPLATE_DEFAULT }
	);
	return (
		<li className="course">
			<div { ...innerBlocksProps }></div>
		</li>
	);
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
			className="course"
			tabIndex={ 0 }
			// eslint-disable-next-line jsx-a11y/no-noninteractive-element-to-interactive-role
			role="button"
			onClick={ handleOnClick }
			onKeyPress={ handleOnClick }
			style={ style }
		>
			<div { ...blockPreviewProps } className="wp-block-learnpress-course-item-template"></div>
		</li>
	);
}

const fetchLearnPressCourses = async ( courseQuery, signal ) => {
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
};

const MemoizedPostTemplateBlockPreview = memo( PostTemplateBlockPreview );

const Edit = ( { clientId, context, attributes, setAttributes } ) => {
	const blockProps = useBlockProps( {
		className: classnames( 'learn-press-courses' ),
	} );
	const [ activeBlockContextId, setActiveBlockContextId ] = useState();
	const [ coursesData, setCoursesData ] = useState();
	const [ listCourses, setListCourses ] = useState( [] );
	const [ loadingAPI, setLoadingAPI ] = useState( 0 );
	const { columns } = attributes;

	// Fetch courses when query parameters change
	useEffect( () => {
		const courseQuery = context.lpCourseQuery ?? {};
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
		[ clientId ]
	);

	const blockContexts = useMemo(
		() =>
			listCourses?.map( ( course ) => ( {
				lpCourseData: course,
				courseId: course?.ID,
			} ) ),
		[ listCourses ]
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
			<ul
				className="learn-press-courses wp-block-learn-press-courses"
				data-layout={ attributes.layout ? attributes.layout : 'list' }
			>
				{ blockContexts &&
					blockContexts.map( ( blockContext ) => (
						<BlockContextProvider key={ blockContext.courseId } value={ blockContext }>
							{ blockContext.courseId ===
							( activeBlockContextId || blockContexts[ 0 ]?.courseId ) ? (
								<PostTemplateInnerBlocks classList={ blockContext.classList } />
							) : null }
							<MemoizedPostTemplateBlockPreview
								blocks={ blocks }
								blockContextId={ blockContext.courseId }
								classList={ blockContext.classList }
								setActiveBlockContextId={ setActiveBlockContextId }
								isHidden={
									blockContext.courseId === ( activeBlockContextId || blockContexts[ 0 ]?.courseId )
								}
							/>
						</BlockContextProvider>
					) ) }
			</ul>
			{ context.lpCourseQuery?.pagination && (
				<nav className="learnpress-block-pagination navigation pagination">
					<ul className="page-numbers">
						<li>
							<span aria-current="page" className="page-numbers current">
								1
							</span>
						</li>
						<li>
							<a className="page-numbers">2</a>
						</li>
						<li>
							<a className="page-numbers">3</a>
						</li>
						<li>
							<a className="next page-numbers">
								<i className="lp-icon-arrow-right"></i>
							</a>
						</li>
					</ul>
				</nav>
			) }
		</>
	);
};

export default Edit;
