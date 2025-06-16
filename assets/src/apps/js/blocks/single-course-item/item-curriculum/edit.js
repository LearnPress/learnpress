import { __ } from '@wordpress/i18n';
import { Placeholder } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';

const Edit = ( props ) => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<div class="lp-course-curriculum">
				<div class="course-curriculum">
					<ul class="course-sections">
						<li class="course-section">
							<div class="course-section-header">
								<div class="section-toggle">
									<i class="lp-icon-angle-down"></i>
									<i class="lp-icon-angle-up"></i>
								</div>
								<div class="course-section-info">
									<div class="course-section__title">Section 1</div>
								</div>
								<div class="section-count-items">10</div>
							</div>
							<ul class="course-section__items">
								<li
									class="course-item "
									data-item-id="763"
									data-item-order="1"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">1.1</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 1</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="764"
									data-item-order="2"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">1.2</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 2</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="765"
									data-item-order="3"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">1.3</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 3</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="766"
									data-item-order="4"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">1.4</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 4</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="767"
									data-item-order="5"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">1.5</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 5</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="768"
									data-item-order="6"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">1.6</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 6</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="769"
									data-item-order="7"
									data-item-type="lp_lesson"
								>
									<a
										href="http://lp.local/courses/zab/lessons/lesson-7-5/"
										class="course-item__link"
									>
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">1.7</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 7</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="770"
									data-item-order="8"
									data-item-type="lp_lesson"
								>
									<a
										href="http://lp.local/courses/zab/lessons/lesson-8-5/"
										class="course-item__link"
									>
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">1.8</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 8</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="771"
									data-item-order="9"
									data-item-type="lp_lesson"
								>
									<a
										href="http://lp.local/courses/zab/lessons/lesson-9-5/"
										class="course-item__link"
									>
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">1.9</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 9</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="772"
									data-item-order="10"
									data-item-type="lp_quiz"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_quiz"></span>
											<span class="course-item-order lp-hidden">1.10</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Quiz 1</div>
											</div>
											<div class="course-item__right">
												<span class="duration">40 Minutes</span>
												<span class="question-count">15 Questions</span>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
							</ul>
						</li>
						<li class="course-section lp-collapse">
							<div class="course-section-header">
								<div class="section-toggle">
									<i class="lp-icon-angle-down"></i>
									<i class="lp-icon-angle-up"></i>
								</div>
								<div class="course-section-info">
									<div class="course-section__title">Section 2</div>
								</div>
								<div class="section-count-items">14</div>
							</div>
							<ul class="course-section__items">
								<li
									class="course-item "
									data-item-id="788"
									data-item-order="1"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">2.1</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 10</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="789"
									data-item-order="2"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">2.2</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 11</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="790"
									data-item-order="3"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">2.3</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 12</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="791"
									data-item-order="4"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">2.4</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 13</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="792"
									data-item-order="5"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">2.5</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 14</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="793"
									data-item-order="6"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">2.6</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 15</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="794"
									data-item-order="7"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">2.7</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 16</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="795"
									data-item-order="8"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">2.8</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 17</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="796"
									data-item-order="9"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">2.9</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 18</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="797"
									data-item-order="10"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">2.10</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 19</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="798"
									data-item-order="11"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">2.11</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 20</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="799"
									data-item-order="12"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">2.12</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 21</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="800"
									data-item-order="13"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">2.13</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 22</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="801"
									data-item-order="14"
									data-item-type="lp_quiz"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_quiz"></span>
											<span class="course-item-order lp-hidden">2.14</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Quiz 2</div>
											</div>
											<div class="course-item__right">
												<span class="duration">40 Minutes</span>
												<span class="question-count">15 Questions</span>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
							</ul>
						</li>
						<li class="course-section lp-collapse">
							<div class="course-section-header">
								<div class="section-toggle">
									<i class="lp-icon-angle-down"></i>
									<i class="lp-icon-angle-up"></i>
								</div>
								<div class="course-section-info">
									<div class="course-section__title">Section 3</div>
								</div>
								<div class="section-count-items">10</div>
							</div>
							<ul class="course-section__items">
								<li
									class="course-item "
									data-item-id="817"
									data-item-order="1"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">3.1</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 23</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="818"
									data-item-order="2"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">3.2</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 24</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="819"
									data-item-order="3"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">3.3</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 25</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="820"
									data-item-order="4"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">3.4</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 26</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="821"
									data-item-order="5"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">3.5</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 27</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="822"
									data-item-order="6"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">3.6</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 28</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="823"
									data-item-order="7"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">3.7</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 29</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="824"
									data-item-order="8"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">3.8</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 30</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="825"
									data-item-order="9"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">3.9</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 31</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="826"
									data-item-order="10"
									data-item-type="lp_quiz"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_quiz"></span>
											<span class="course-item-order lp-hidden">3.10</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Quiz 3</div>
											</div>
											<div class="course-item__right">
												<span class="duration">30 Minutes</span>
												<span class="question-count">10 Questions</span>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
							</ul>
						</li>
						<li class="course-section lp-collapse">
							<div class="course-section-header">
								<div class="section-toggle">
									<i class="lp-icon-angle-down"></i>
									<i class="lp-icon-angle-up"></i>
								</div>
								<div class="course-section-info">
									<div class="course-section__title">Section 4</div>
								</div>
								<div class="section-count-items">13</div>
							</div>
							<ul class="course-section__items">
								<li
									class="course-item "
									data-item-id="837"
									data-item-order="1"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">4.1</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 32</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="838"
									data-item-order="2"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">4.2</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 33</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="839"
									data-item-order="3"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">4.3</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 34</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="840"
									data-item-order="4"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">4.4</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 35</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="841"
									data-item-order="5"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">4.5</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 36</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="842"
									data-item-order="6"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">4.6</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 37</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="843"
									data-item-order="7"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">4.7</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 38</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="844"
									data-item-order="8"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">4.8</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 39</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="845"
									data-item-order="9"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">4.9</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 40</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="846"
									data-item-order="10"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">4.10</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 41</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="847"
									data-item-order="11"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">4.11</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 42</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="848"
									data-item-order="12"
									data-item-type="lp_lesson"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_lesson"></span>
											<span class="course-item-order lp-hidden">4.12</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Lesson 43</div>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
								<li
									class="course-item "
									data-item-id="849"
									data-item-order="13"
									data-item-type="lp_quiz"
								>
									<a class="course-item__link">
										<div class="course-item__info">
											<span class="course-item-ico lp_quiz"></span>
											<span class="course-item-order lp-hidden">4.13</span>
										</div>
										<div class="course-item__content">
											<div class="course-item__left">
												<div class="course-item-title">Quiz 4</div>
											</div>
											<div class="course-item__right">
												<span class="duration">10 Minutes</span>
												<span class="question-count">10 Questions</span>
											</div>
										</div>
										<div class="course-item__status">
											<span class="course-item-ico in-progress"></span>
										</div>
									</a>
								</li>
							</ul>
						</li>
					</ul>
				</div>
			</div>
		</div>
	);
};

export default Edit;
