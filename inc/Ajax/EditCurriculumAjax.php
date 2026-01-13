<?php
/**
 * class EditCurriculumAjax
 *
 * This class handles the AJAX request to edit the curriculum of a course.
 *
 * @since 4.2.8.6
 * @version 1.0.1
 */

namespace LearnPress\Ajax;

use Exception;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\CourseSectionItemModel;
use LearnPress\Models\CourseSectionModel;
use LearnPress\Models\LessonPostModel;
use LearnPress\Models\PostModel;
use LearnPress\TemplateHooks\Course\AdminEditCurriculumTemplate;
use LP_Helper;
use LP_REST_Response;
use LP_Section_Items_DB;
use LP_Section_Items_Filter;
use stdClass;
use Throwable;

class EditCurriculumAjax extends AbstractAjax {
	/**
	 * Check permissions and validate parameters.
	 *
	 * @throws Exception
	 *
	 * @since 4.2.8.6
	 * @version 1.0.1
	 */
	public static function check_valid() {
		$params = wp_unslash( $_REQUEST['data'] ?? '' );
		if ( empty( $params ) ) {
			throw new Exception( 'Error: params invalid!' );
		}

		return LP_Helper::json_decode( $params, true );
	}

	/**
	 * Add section
	 *
	 * JS file edit-section.js: function addSection call this method to update the section description.
	 *
	 * @since 4.2.8.6
	 * @version 1.0.1
	 */
	public static function course_add_section() {
		$response = new LP_REST_Response();

		try {
			$data = self::check_valid();

			$course_id   = $data['course_id'] ?? 0;
			$courseModel = CourseModel::find( $course_id, true );
			if ( ! $courseModel ) {
				throw new Exception( __( 'Course not found', 'learnpress' ) );
			}

			$section_name = trim( $data['section_name'] ?? '' );
			if ( empty( $section_name ) ) {
				throw new Exception( __( 'Section title is required', 'learnpress' ) );
			}

			// Add section to course.
			$coursePostModel = new CoursePostModel( $courseModel );
			$sectionNew      = $coursePostModel->add_section(
				[
					'section_name'        => $section_name,
					'section_description' => $data['section_description'] ?? '',
				]
			);

			$response->data->section = $sectionNew;
			$response->status        = 'success';
			$response->message       = __( 'Section added successfully', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Update section
	 *
	 * JS file edit-section.js: function updateSectionTitle call this method to update the section title.
	 * JS file edit-section.js: function updateSectionDescription call this method to update the section description.
	 *
	 * @since  4.2.8.6
	 * @version 1.0.1
	 */
	public static function course_update_section() {
		$response = new LP_REST_Response();

		try {
			$data       = self::check_valid();
			$course_id  = $data['course_id'] ?? 0;
			$section_id = $data['section_id'] ?? 0;

			$courseModel = CourseModel::find( $course_id, true );
			if ( ! $courseModel ) {
				throw new Exception( __( 'Course not found', 'learnpress' ) );
			}

			$courseSectionModel = CourseSectionModel::find( $section_id, $course_id, true );
			if ( ! $courseSectionModel ) {
				throw new Exception( __( 'Section not found', 'learnpress' ) );
			}

			// Update section of course
			$coursePostModel = new CoursePostModel( $courseModel );
			$coursePostModel->update_section( $courseSectionModel, $data );

			$response->status  = 'success';
			$response->message = __( 'Section updated successfully', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Delete section
	 *
	 * JS file edit-section.js: function deleteSection call this method to update the section title.
	 *
	 * @since 4.2.8.6
	 * @version 1.0.0
	 */
	public static function course_delete_section() {
		$response = new LP_REST_Response();

		try {
			$data       = self::check_valid();
			$course_id  = $data['course_id'] ?? 0;
			$section_id = $data['section_id'] ?? 0;

			$courseModel = CourseModel::find( $course_id, true );
			if ( ! $courseModel ) {
				throw new Exception( __( 'Course not found', 'learnpress' ) );
			}

			$courseSectionModel = CourseSectionModel::find( $section_id, $course_id );
			if ( ! $courseSectionModel ) {
				throw new Exception( __( 'Section not found', 'learnpress' ) );
			}

			$courseSectionModel->delete();

			$response->status  = 'success';
			$response->message = __( 'Section updated successfully', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Update sections position
	 * new_position => list of section id by order
	 *
	 * JS file edit-section.js: function sortAbleSection call this method.
	 *
	 * @since 4.2.8.6
	 * @version 1.0.1
	 */
	public static function course_update_section_position() {
		$response = new LP_REST_Response();

		try {
			$data         = self::check_valid();
			$course_id    = $data['course_id'] ?? 0;
			$new_position = $data['new_position'] ?? [];
			if ( ! is_array( $new_position ) ) {
				throw new Exception( __( 'Invalid section position', 'learnpress' ) );
			}

			$courseModel = CourseModel::find( $course_id, true );
			if ( ! $courseModel ) {
				throw new Exception( __( 'Course not found', 'learnpress' ) );
			}

			// Update all sections position
			$coursePostMoel = new CoursePostModel( $courseModel );
			$coursePostMoel->update_sections_position( [ 'new_position' => $new_position ] );

			$courseModel->sections_items = null;
			$courseModel->save();

			$response->status  = 'success';
			$response->message = __( 'Section updated successfully', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Create item and add to section
	 *
	 * $data['course_id']  => ID of course
	 * $data['section_id'] => ID of section
	 * $data['item_type']   => Type of item (e.g., 'lesson', 'quiz', etc.)
	 * $data['item_title']  => Title of the item
	 *
	 * JS file edit-section-item.js: function addItemToSection call this method.
	 *
	 * @since 4.2.8.6
	 * @version 1.0.1
	 */
	public static function create_item_add_to_section() {
		$response = new LP_REST_Response();

		try {
			$data       = self::check_valid();
			$course_id  = $data['course_id'] ?? 0;
			$section_id = $data['section_id'] ?? 0;

			$courseModel = CourseModel::find( $course_id, true );
			if ( ! $courseModel ) {
				throw new Exception( __( 'Course not found', 'learnpress' ) );
			}

			$courseSectionModel = CourseSectionModel::find( $section_id, $course_id );
			if ( ! $courseSectionModel ) {
				throw new Exception( __( 'Section not found', 'learnpress' ) );
			}

			$courseSectionItemModel = $courseSectionModel->create_item_and_add( $data );

			$response->data->section_item = $courseSectionItemModel;

			/**
			 * @var $itemModel PostModel
			 */
			$itemModel                 = $courseModel->get_item_model(
				$courseSectionItemModel->item_id,
				$courseSectionItemModel->item_type
			);
			$response->data->item_link = $itemModel ? $itemModel->get_edit_link() : '';

			$response->status  = 'success';
			$response->message = __( 'Item added to section successfully', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Add items selected to section
	 *
	 * $data['course_id']  => ID of course
	 * $data['section_id'] => ID of section
	 * $data['items']      => [ item_id => 0, item_type => '' ]
	 *
	 * JS file edit-section-item.js: function addItemsSelectedToSection call this method.
	 *
	 * @since 4.2.8.6
	 * @version 1.0.0
	 */
	public static function add_items_to_section() {
		$response = new LP_REST_Response();

		try {
			$data       = self::check_valid();
			$course_id  = $data['course_id'] ?? 0;
			$section_id = $data['section_id'] ?? 0;

			$courseModel = CourseModel::find( $course_id, true );
			if ( ! $courseModel ) {
				throw new Exception( __( 'Course not found', 'learnpress' ) );
			}

			$courseSectionModel = CourseSectionModel::find( $section_id, $course_id );
			if ( ! $courseSectionModel ) {
				throw new Exception( __( 'Section not found', 'learnpress' ) );
			}

			$courseSectionItems = $courseSectionModel->add_items( $data );
			if ( empty( $courseSectionItems ) ) {
				throw new Exception( __( 'No items were added to the section', 'learnpress' ) );
			}

			$response->data->html = '';
			/**
			 * @var $courseSectionItem CourseSectionItemModel
			 */
			foreach ( $courseSectionItems as $courseSectionItem ) {
				$courseSectionItemAlias        = (object) get_object_vars( $courseSectionItem );
				$itemModel                     = $courseModel->get_item_model(
					$courseSectionItem->item_id,
					$courseSectionItem->item_type
				);
				$courseSectionItemAlias->title = $itemModel ? $itemModel->get_the_title() : '';
				$response->data->html         .= AdminEditCurriculumTemplate::instance()->html_section_item(
					$courseModel,
					$courseSectionItemAlias
				);
			}

			$response->status  = 'success';
			$response->message = __( 'Items added to section successfully', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Delete item from section
	 *
	 * $data['course_id']  => ID of course
	 * $data['section_id'] => ID of section
	 * $data['item_id']    => ID of item to delete
	 *
	 * JS file edit-section-item.js: function deleteItem call this method.
	 */
	public static function delete_item_from_section() {
		$response = new LP_REST_Response();

		try {
			$data       = self::check_valid();
			$course_id  = $data['course_id'] ?? 0;
			$section_id = $data['section_id'] ?? 0;
			$item_id    = $data['item_id'] ?? 0;

			$courseModel = CourseModel::find( $course_id, true );
			if ( ! $courseModel ) {
				throw new Exception( __( 'Course not found', 'learnpress' ) );
			}

			$courseSectionModel = CourseSectionModel::find( $section_id, $course_id );
			if ( ! $courseSectionModel ) {
				throw new Exception( __( 'Section not found', 'learnpress' ) );
			}

			// Find item of section id
			$courseSectionItemModel = CourseSectionItemModel::find( $section_id, $item_id );
			if ( ! $courseSectionItemModel ) {
				throw new Exception( __( 'Item not found in section', 'learnpress' ) );
			}

			// Delete item from section
			$courseSectionItemModel->section_course_id = $course_id;
			$courseSectionItemModel->delete();

			$response->status  = 'success';
			$response->message = __( 'Item deleted from section successfully', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Update item on new section and position
	 *
	 * $data['course_id']              => ID of course
	 * $data['items_position']         => list of item id by order on section new
	 * $data['item_id_change']         => ID of item to change section
	 * $data['section_id_new_of_item'] => ID of new section of item
	 * $data['section_id_old_of_item'] => ID of old section of item
	 *
	 * JS file edit-section-item.js: function sortAbleItem call this method.
	 *
	 * @since 4.2.8.6
	 * @version 1.0.1
	 */
	public static function update_item_section_and_position() {
		$response = new LP_REST_Response();

		try {
			$data      = self::check_valid();
			$course_id = $data['course_id'] ?? 0;

			$courseModel = CourseModel::find( $course_id, true );
			if ( ! $courseModel ) {
				throw new Exception( __( 'Course not found', 'learnpress' ) );
			}

			$coursePostModel = new CoursePostModel( $courseModel );
			$coursePostModel->update_items_position( $data );

			$response->status  = 'success';
			$response->message = __( 'Item position updated successfully', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Update data of item in section
	 *
	 * $data['course_id']      => ID of course
	 * $data['section_id']     => ID of section
	 * $data['item_id']        => ID of item to update
	 * $data['item_type']      => Type of item (e.g., 'lesson', 'quiz', etc.)
	 * $data['item_title']     => New title of the item
	 *
	 * JS file edit-section-item.js: function updateTitle call this method.
	 *
	 * @since 4.2.8.6
	 * @version 1.0.0
	 */
	public static function update_item_of_section() {
		$response = new LP_REST_Response();

		try {
			$data       = self::check_valid();
			$course_id  = $data['course_id'] ?? 0;
			$section_id = $data['section_id'] ?? 0;
			$item_id    = $data['item_id'] ?? 0;
			$item_type  = $data['item_type'] ?? '';
			$item_title = $data['item_title'] ?? '';

			if ( empty( $item_title ) ) {
				throw new Exception( __( 'Item title is required', 'learnpress' ) );
			}

			$courseModel = CourseModel::find( $course_id, true );
			if ( ! $courseModel ) {
				throw new Exception( __( 'Course not found', 'learnpress' ) );
			}

			$courseSectionModel = CourseSectionModel::find( $section_id, $course_id );
			if ( ! $courseSectionModel ) {
				throw new Exception( __( 'Section not found', 'learnpress' ) );
			}

			$sectionItemModel = CourseSectionItemModel::find( $section_id, $item_id );
			if ( ! $sectionItemModel ) {
				throw new Exception( __( 'Item not found in section', 'learnpress' ) );
			}

			/**
			 * @var $itemModel PostModel
			 */
			$itemModel = $courseModel->get_item_model( $item_id, $item_type );
			if ( ! $itemModel ) {
				throw new Exception( __( 'Item not found', 'learnpress' ) );
			}

			$itemModel->post_title = $item_title;
			$itemModel->save();

			$courseModel->sections_items = null;
			$courseModel->save();

			$response->status  = 'success';
			$response->message = __( 'Item updated successfully', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Update item preview.
	 *
	 * JS file edit-section-item.js: function updatePreviewItem call this method.
	 *
	 * @since 4.2.8.6
	 * @version 1.0.2
	 */
	public static function update_item_preview() {
		$response = new LP_REST_Response();

		try {
			$data           = self::check_valid();
			$course_id      = $data['course_id'] ?? 0;
			$item_id        = $data['item_id'] ?? 0;
			$item_type      = $data['item_type'] ?? '';
			$enable_preview = $data['enable_preview'] ?? 0;

			$courseModel = CourseModel::find( $course_id, true );
			if ( ! $courseModel ) {
				throw new Exception( __( 'Course not found', 'learnpress' ) );
			}

			if ( $item_type !== LP_LESSON_CPT ) {
				throw new Exception( __( 'Only lesson can be set preview', 'learnpress' ) );
			}

			/**
			 * @var $itemModel LessonPostModel
			 */
			$itemModel = $courseModel->get_item_model( $item_id, $item_type );
			if ( ! $itemModel ) {
				throw new Exception( __( 'Item not found', 'learnpress' ) );
			}

			$itemModel->set_preview( $enable_preview == 1 );

			$response->status  = 'success';
			$response->message = __( 'Item updated successfully', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}
}
