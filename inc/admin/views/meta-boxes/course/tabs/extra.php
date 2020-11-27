<?php
/**
 * Course general data panel.
 *
 * @author ThimPress <nhamdv>
 */

defined( 'ABSPATH' ) || exit;

$requirements     = get_post_meta( $thepostid, '_lp_requirements', true );
$target_audiences = get_post_meta( $thepostid, '_lp_target_audiences', true );
$key_features     = get_post_meta( $thepostid, '_lp_key_features', true );
$faqs             = get_post_meta( $thepostid, '_lp_faqs', true );
?>

<div id="extra_course_data" class="lp-meta-box-course-panels">

	<?php do_action( 'learnpress/course-settings/before-extra' ); ?>

	<div class="form-field lp_course_extra_meta_box">
		<label for="_lp_requirements"><?php esc_html_e( 'Requirements', 'learnpress' ); ?></label>
		<div class="lp_course_extra_meta_box__content">
			<div class="lp_course_extra_meta_box__fields">
				<?php if ( ! empty( $requirements[0][0] ) ) : ?>
					<?php foreach ( $requirements as $requirement ) : ?>
						<div class="lp_course_extra_meta_box__field">
							<span class="sort"></span>
							<input name="_lp_requirements[]" value="<?php echo $requirement; ?>" type="text" class="lp_course_extra_meta_box__input">
							<a href="#" class="delete"></a>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<a href="#" class="button button-primary lp_course_extra_meta_box__add" data-add="<?php echo esc_attr( '<div class="lp_course_extra_meta_box__field"><span class="sort"></span><input name="_lp_requirements[]" value="" type="text" class="lp_course_extra_meta_box__input"><a href="#" class="delete"></a></div>' ); ?>">
				<?php esc_html_e( '+ Add more', 'learnpress' ); ?>
			</a>
		</div>
	</div>

	<div class="form-field lp_course_extra_meta_box">
		<label for="_lp_target_audiences"><?php esc_html_e( 'Target Audience', 'learnpress' ); ?></label>
		<div class="lp_course_extra_meta_box__content">
			<div class="lp_course_extra_meta_box__fields">
				<?php if ( ! empty( $target_audiences[0][0] ) ) : ?>
					<?php foreach ( $target_audiences as $target_audience ) : ?>
						<div class="lp_course_extra_meta_box__field">
							<span class="sort"></span>
							<input name="_lp_target_audiences[]" value="<?php echo $target_audience; ?>" type="text" class="lp_course_extra_meta_box__input">
							<a href="#" class="delete"></a>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<a href="#" class="button button-primary lp_course_extra_meta_box__add" data-add="<?php echo esc_attr( '<div class="lp_course_extra_meta_box__field"><span class="sort"></span></a><input name="_lp_target_audiences[]" value="" type="text" class="lp_course_extra_meta_box__input"><a href="#" class="delete"></a></div>' ); ?>">
				<?php esc_html_e( '+ Add more', 'learnpress' ); ?>
			</a>
		</div>
	</div>

	<div class="form-field lp_course_extra_meta_box">
		<label for="_lp_key_features"><?php esc_html_e( 'Key Features', 'learnpress' ); ?></label>
		<div class="lp_course_extra_meta_box__content">
			<div class="lp_course_extra_meta_box__fields">
				<?php if ( ! empty( $key_features[0][0] ) ) : ?>
					<?php foreach ( $key_features as $key_feature ) : ?>
						<div class="lp_course_extra_meta_box__field">
							<span class="sort"></span>
							<input name="_lp_key_features[]" value="<?php echo $key_feature; ?>" type="text" class="lp_course_extra_meta_box__input">
							<a href="#" class="delete"></a>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<a href="#" class="button button-primary lp_course_extra_meta_box__add" data-add="<?php echo esc_attr( '<div class="lp_course_extra_meta_box__field"><span class="sort"></span><input name="_lp_key_features[]" value="" type="text" class="lp_course_extra_meta_box__input"><a href="#" class="delete"></a></div>' ); ?>">
				<?php esc_html_e( '+ Add more', 'learnpress' ); ?>
			</a>
		</div>
	</div>

	<div class="form-field lp_course_faq_meta_box">
		<label for="_lp_key_features"><?php esc_html_e( 'FAQs', 'learnpress' ); ?></label>
		<div class="lp_course_faq_meta_box__content">
			<div class="lp_course_faq_meta_box__fields">
				<?php if ( ! empty( $faqs[0][0] ) ) : ?>
					<?php foreach ( $faqs as $key => $faq ) : ?>
						<div class="lp_course_faq_meta_box__field">
							<label>
								<span><?php esc_attr_e( 'Title', 'learnpress' ); ?></span>
								<input type="text" name="_lp_faqs_question[]" value="<?php echo $faq[0]; ?>">
							</label>
							<label>
								<span><?php esc_attr_e( 'Content', 'learnpress' ); ?></span>
								<textarea name="_lp_faqs_answer[]"><?php echo $faq[1]; ?></textarea>
							</label>
							<a href="#" class="delete"></a>
							<span class="sort"></span>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<a href="#" class="button button-primary lp_course_faq_meta_box__add"
				data-add="
				<?php
				echo esc_attr(
					'<div class="lp_course_faq_meta_box__field">
						<label>
							<span>' . esc_attr__( 'Title', 'learnpress' ) . '</span>
							<input type="text" name="_lp_faqs_question[]" value="">
						</label>
						<label>
							<span>' . esc_attr__( 'Content', 'learnpress' ) . '</span>
							<textarea name="_lp_faqs_answer[]"></textarea>
						</label>
						<a href="#" class="delete"></a>
						<span class="sort"></span>
					</div>'
				);
				?>
				"><?php esc_html_e( '+ Add more', 'learnpress' ); ?>
			</a>
		</div>
	</div>

	<?php do_action( 'learnpress/course-settings/after-extra' ); ?>

</div>
