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
	RangeControl,
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
	// const [ totalPages, setTotalPages ] = useState( 1 );
	const { columns } = attributes;

	const layoutPagination = context.lpCourseQuery?.pagination_type || 'number';

	// Fetch courses when query parameters change
	useEffect( () => {
		const courseQuery = context.lpCourseQuery ?? {};
		let signal, controller;
		const fetchCourses = async () => {
			try {
				setLoadingAPI( 1 );
				controller = new AbortController();
				signal = controller.signal;

				const response = await fetchLearnPressCourses( courseQuery, signal );
				const { data } = response;
				const { courses, page, total, total_pages } = data;

				setCoursesData( response );
				setListCourses( courses );
				// setTotalPages( total_pages );
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

	function paginationTypeDisplay( type ) {
		switch ( type ) {
			case 'load-more':
				return (
					<button className="courses-btn-load-more">{ __( 'Load More', 'learnpress' ) }</button>
				);
			case 'infinite':
				return '';
			default:
				return (
					<nav className="learnpress-block-pagination navigation pagination">
						<ul className="page-numbers">
							<li>
								<a className="prev page-numbers" href="?paged=1">
									<i className="lp-icon-arrow-left"></i>
								</a>
							</li>
							{ Array.from( { length: 3 }, ( _, index ) => (
								<li key={ index }>
									<a className="page-numbers" href="{index}">
										{ index + 1 }
									</a>
								</li>
							) ) }
						</ul>
					</nav>
				);
		}
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'learnpress' ) }>
					<ToggleGroupControl
						label={ __( 'Layout', 'learnpress' ) }
						value={ attributes.layout || 'gird' }
						onChange={ ( value ) => {
							setAttributes( {
								layout: value || 'gird',
							} );
						} }
						isBlock={ true }
					>
						<ToggleGroupControlOption value="list" label="Stack" />
						<ToggleGroupControlOption value="grid" label="Grid" />
					</ToggleGroupControl>

					{ attributes.layout === 'grid' && (
						<RangeControl
							label={ __( 'Columns', 'learnpress' ) }
							value={ attributes.columns || 3 }
							onChange={ ( value ) => {
								setAttributes( {
									columns: value,
								} );
							} }
							min={ 2 }
							max={ 6 }
							step={ 1 }
							marks={ [
								{ value: 2, label: '2' },
								{ value: 3, label: '3' },
								{ value: 4, label: '4' },
								{ value: 5, label: '5' },
								{ value: 6, label: '6' },
							] }
							withInputField={ true }
						/>
					) }
				</PanelBody>
			</InspectorControls>
			<ul
				className="learn-press-courses wp-block-learn-press-courses"
				data-layout={ attributes.layout ? attributes.layout : 'list' }
				style={ {
					'--columns': attributes.columns || 3,
				} }
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
			{ context.lpCourseQuery?.pagination ? paginationTypeDisplay( layoutPagination ) : null }
		</>
	);
};

export default Edit;
