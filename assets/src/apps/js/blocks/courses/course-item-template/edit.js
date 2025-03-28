import { InnerBlocks,
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

function PostTemplateBlockPreview(
	{
		blocks,
		blockContextId,
		classList,
		isHidden,
		setActiveBlockContextId,
	}
) {
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
	const url = API.apiCourses;
	const params = '?return_type=json';
	const response = await fetch( url + params, {
		method: 'GET',
		signal,
	} );

	return await response.json();
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
	}, [ context.lpCourseQuery ] );

	const { blocks } = useSelect( ( select ) => {
		const { getBlocks } = select( blockEditorStore );

		return {
			blocks: getBlocks( clientId ),
		};
	}, [ clientId ] );

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
			<InspectorControls>
				<PanelBody title={ __( 'Layout Settings' ) }>
					<RangeControl
						label={ __( 'Columns' ) }
						value={ columns }
						onChange={ ( value ) =>
							setAttributes( { columns: value } )
						}
						min={ 1 }
						max={ 12 }
					/>
				</PanelBody>
			</InspectorControls>
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
		</>
	);
};

export default Edit;
