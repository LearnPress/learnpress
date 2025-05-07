import { __ } from '@wordpress/i18n';
import { Placeholder } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';

const Edit = ( props ) => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<div id="learn-press-content-item">
				<div class="content-item-scrollable">
					<div class="content-item-wrap">
						<div class="content-item-summary">
							<h1 class="course-item-title lesson-title">Lesson 2</h1>

							<div class="content-item-description lesson-description">
								<p>
									Vidisse advesperascit mediocribus adulter everti fiant redderet sequens discimus
									hinc licentiam lectulis levares
								</p>
								<p>
									Acupenserem dicat spelunca opifices cuiusque oculis iniquus platonis littera
									summis miseriarum testimonium dicat hominem inventus
								</p>
								<p>
									Relinquet debuerunt tuae animoque verbis prima vitiose negotii inchoatum don
									sapiens postulo luci illustris amitti falli consul propter proferebas possent
								</p>
								<p>
									Necopinato crimen dempta soletis evolare democritus doleas secusne brevem
									pertinacem levem hieronymi periculo diem tuum
								</p>
								<p>
									Primos singulis persecuti exponere modicum aculeis relinquunt atque evolare
									phaedro detractus refert spectare squilla odio
								</p>
								<p>
									Concordiae mene sensim vivunt postulet quaeram quali cupit optatius praeterita
									disseretur potius eam augendae praesidii erunt pollicentur recusant hortandus
								</p>
								<p>
									Multarum consequentia confecimus miserrimus statui quidque liceat occultissimarum
									intervalla longinquum iisque
								</p>
								<p>
									Sanguine zeno artem usum summum dissensione eorum alias attinet prosunt appellamus
									sensitque vestra consuetudo commodaita
								</p>
								<p>
									Serpere vitae labefactare commovebat quanto dies subito qualis impedit ingredimur
									incontentae aderit etenim rationis malitiam audax amicitia quodsi malitiam
								</p>
								<p>
									Pietatem quibus gravior ita mentio essentne putaverunt habere nominati gravis
									vocant legem iocari sequens duxisse viros simonides
								</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
};

export default Edit;
