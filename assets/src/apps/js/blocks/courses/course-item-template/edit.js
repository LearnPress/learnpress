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
	try {
		const url = 'http://lp.test/wp-json/lp/v1/courses/archive-course';
		const params = '?return_type=json';
		const response = await fetch( url + params, {
			method: 'GET',
			signal,
		} );

		return await response.json();
	} catch ( error ) {
		console.error( 'Error fetching LearnPress courses:', error );
	}
};

const MemoizedPostTemplateBlockPreview = memo( PostTemplateBlockPreview );

const Edit = ( { clientId, context, attributes, setAttributes } ) => {
	const blockProps = useBlockProps();
	const [ activeBlockContextId, setActiveBlockContextId ] = useState();
	const [ coursesData, setCoursesData ] = useState();
	const [ listCourses, setListCourses ] = useState( [] );
	const { columns } = attributes;

	// Fetch courses when query parameters change
	useEffect( () => {
		const courseQuery = context.lpCourseQuery ?? {};
		let signal, controller;
		const fetchCourses = async () => {
			try {
				if ( undefined !== controller ) {
					controller.abort();
				}

				controller = new AbortController();
				signal = controller.signal;

				const data = await fetchLearnPressCourses( courseQuery, signal );
				setCoursesData( data );
				setListCourses( data.data.courses );
			} catch ( error ) {
				console.error( 'Failed to fetch courses:', error );
			} finally {
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
			listCourses?.map( ( post ) => ( {
				lpCourseData: post,
				postId: post.ID,
				postTitle: post.post_title,
			} ) ),
		[ listCourses ]
	);

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
						key={ blockContext.postId }
						value={ blockContext }
					>
						{ blockContext.postId ===
						( activeBlockContextId ||
							blockContexts[ 0 ]?.postId ) ? (
								<PostTemplateInnerBlocks
									classList={ blockContext.classList }
								/>
							) : null }
						<MemoizedPostTemplateBlockPreview
							blocks={ blocks }
							blockContextId={ blockContext.postId }
							classList={ blockContext.classList }
							setActiveBlockContextId={
								setActiveBlockContextId
							}
							isHidden={
								blockContext.postId ===
								( activeBlockContextId ||
									blockContexts[ 0 ]?.postId )
							}
						/>
					</BlockContextProvider>
				) ) }
			</ul>
		</>
	);
};

export default Edit;
