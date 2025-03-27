import { InnerBlocks,
	useBlockProps,
	BlockContextProvider,
	useInnerBlocksProps,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalUseBlockPreview as useBlockPreview,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { memo, useMemo, useState, useEffect } from '@wordpress/element';

const TEMPLATE = [
	[ 'learnpress/course-title' ],
];

function PostTemplateInnerBlocks( { classList } ) {
	const innerBlocksProps = useInnerBlocksProps(
		{ template: TEMPLATE }
	);
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

const MemoizedPostTemplateBlockPreview = memo( PostTemplateBlockPreview );

const Edit = ( { clientId, context } ) => {
	const blockProps = useBlockProps();
	const [ activeBlockContextId, setActiveBlockContextId ] = useState();
	const lpCoursesData = context.lpCoursesData ?? undefined;

	const coursesData = useMemo(
		() => ( lpCoursesData ? lpCoursesData.data.courses : [] ),
		[ lpCoursesData ]
	);

	const { blocks } = useSelect( ( select ) => {
		const { getBlocks } = select( blockEditorStore );

		return {
			blocks: getBlocks( clientId ),
		};
	}, [ clientId ] );

	const blockContexts = useMemo(
		() =>
			lpCoursesData?.data?.courses?.map( ( post ) => ( {
				lpCourseData: post,
				postId: post.ID,
				postTitle: post.post_title,
			} ) ),
		[ lpCoursesData ]
	);

	return (
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
	);
};

export default Edit;
