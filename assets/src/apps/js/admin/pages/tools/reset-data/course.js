/**
 * Reset course progress.
 *
 * @since 4.0.5.
 * @author Nhamdv - Code choi choi in Physcode.
 */
const { __ } = wp.i18n;
const { TextControl, Button, Spinner, CheckboxControl, Notice } = wp.components;
const { useState, useEffect } = wp.element;
const { addQueryArgs } = wp.url;

const ResetCourse = () => {
	const [ loading, setLoading ] = useState( false );
	const [ search, setSearch ] = useState( '' );
	const [ data, setData ] = useState( [] );
	const [ checkData, setCheckData ] = useState( [] );
	const [ message, setMessage ] = useState( [] );
	const [ loadingReset, setLoadingReset ] = useState( false );

	useEffect( () => {
		responsiveData( search );
	}, [ search ] );

	const responsiveData = async ( s ) => {
		try {
			if ( ! s || loading ) {
				setMessage( [] );
				setData( [] );
				return;
			}

			if ( s.length < 3 ) {
				setMessage( [ { status: 'error', message: 'Please enter at least 3 characters to searching course.' } ] );
				setData( [] );
				return;
			}

			setLoading( true );

			const response = await wp.apiFetch( {
				path: addQueryArgs( 'lp/v1/admin/tools/reset-data/search-courses', {
					s,
				} ),
				method: 'GET',
			} );

			const { status, data } = response;

			setLoading( false );

			if ( status === 'success' ) {
				setData( data );
				setMessage( [] );
			} else {
				setMessage( [ { status: 'error', message: response.message || 'LearnPress: Search Course Fail!' } ] );
				setData( [] );
			}
		} catch ( error ) {
			console.log( error.message );
		}
	};

	function checkItems( id ) {
		const datas = [ ...checkData ];

		if ( datas.includes( id ) ) {
			const index = datas.indexOf( id );

			if ( index > -1 ) {
				datas.splice( index, 1 );
			}
		} else {
			datas.push( id );
		}

		setCheckData( datas );
	}

	const resetCourse = async () => {
		if ( checkData.length === 0 ) {
			setMessage( [ { status: 'error', message: 'Please chooce Course for reset data!' } ] );
			return;
		}

		// eslint-disable-next-line no-alert
		if ( ! confirm( 'Are you sure to reset course progress of all users enrolled this course?' ) ) {
			return;
		}

		const notice = [];

		try {
			setLoadingReset( true );

			for ( const courseId of checkData ) {
				const response = await wp.apiFetch( {
					path: addQueryArgs( 'lp/v1/admin/tools/reset-data/reset-courses', {
						courseId,
					} ),
					method: 'GET',
				} );

				const { status, data, message } = response;

				notice.push( { status, message: message || `Course #${ courseId } reset successfully!` } );
			}

			setLoadingReset( false );
		} catch ( error ) {
			notice.push( { status: 'error', message: error.message || `LearnPress Error: Reset Course Data.` } );
		}

		setMessage( notice );
	};

	return (
		<>
			<h2>{ __( 'Reset course progress', 'learnpress' ) }</h2>
			<div className="description">
				<p>{ __( 'This action will reset progress of a course for all users have enrolled.', 'learnpress' ) }</p>
				<p>{ __( 'Search results only show course have user data.', 'learnpress' ) }</p>
				<div>
					<TextControl
						placeholder={ __( 'Search course by name', 'learnpress' ) }
						value={ search }
						onChange={ ( value ) => setSearch( value ) }
						style={ { width: 300 } }
					/>
				</div>
			</div>

			{ loading && <Spinner /> }

			{ data.length > 0 && (
				<>
					<div className="lp-reset-course_progress" style={ {
						border: '1px solid #eee',
					} }>
						<div>
							<div style={ { background: '#eee' } }>
								<div>
									<CheckboxControl
										checked={ checkData.length === data.length }
										onChange={ () => {
											if ( checkData.length === data.length ) {
												setCheckData( [] );
											} else {
												setCheckData( data.map( ( dt ) => dt.id ) );
											}
										} }
										style={ { margin: 0 } }
									/>
								</div>
								<div>{ __( 'ID', 'learnpress' ) }</div>
								<div>{ __( 'Name', 'learnpress' ) }</div>
								<div>{ __( 'Students', 'learnpress' ) }</div>
							</div>
						</div>

						<div style={ { height: '100%', maxHeight: 200, overflow: 'auto' } }>
							{ data.map( ( dt ) => {
								return (
									<div style={ { borderTop: '1px solid #eee' } } key={ dt.id }>
										<div>
											<CheckboxControl
												checked={ checkData.includes( dt.id ) }
												onChange={ () => checkItems( dt.id ) }
											/>
										</div>
										<div>#{ dt.id }</div>
										<div>{ dt.title }</div>
										<div>{ dt.students }</div>
									</div>
								);
							} ) }
						</div>
					</div>

					{ loadingReset ? <Spinner /> : (
						<Button
							isPrimary
							onClick={ () => resetCourse() }
							style={ { marginTop: 10, height: 30 } }
						>
							{ __( 'Reset now', 'learnpress' ) }
						</Button>
					) }
				</>
			) }

			{ message.length > 0 && message.map( ( mess, index ) => {
				return (
					<Notice status={ mess.status } key={ index } isDismissible={ false }>
						{ mess.message }
					</Notice>
				);
			} ) }

			<style>
				{ '\
				.lp-reset-course_progress .components-base-control__field {\
					margin: 0;\
				}\
				.components-notice{\
					margin-left: 0;\
				}\
				.lp-reset-course_progress > div > div{\
					display: grid;\
					grid-template-columns: 80px 50px 1fr 80px;\
					align-items: center;\
				}\
				.lp-reset-course_progress > div > div > div{\
					maegin: 0;\
					padding: 8px 10px;\
				}\
				' }

			</style>
		</>
	);
};
export default ResetCourse;
