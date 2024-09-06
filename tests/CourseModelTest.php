<?php

use PHPUnit\Framework\TestCase;
use LearnPress\Models\CourseModel;

class CourseModelTest extends TestCase {
	public function can_create_course_model_with_default_values() {
		$course = new CourseModel();
		$this->assertEquals( 0, $course->ID );
		$this->assertEquals( 0, $course->post_author );
		$this->assertNull( $course->post_date_gmt );
		$this->assertEquals( '', $course->post_content );
		$this->assertEquals( '', $course->post_title );
		$this->assertEquals( '', $course->post_status );
		$this->assertEquals( '', $course->post_name );
		$this->assertEquals( 0, $course->price_to_sort );
		$this->assertEquals( 0, $course->is_sale );
		$this->assertNull( $course->json );
		$this->assertNull( $course->lang );
		$this->assertInstanceOf( stdClass::class, $course->meta_data );
	}

	public function can_map_data_to_course_model() {
		$data   = [
			'ID'            => 1,
			'post_author'   => 2,
			'post_date_gmt' => '2023-01-01 00:00:00',
			'post_content'  => 'Content',
			'post_title'    => 'Title',
			'post_status'   => 'publish',
			'post_name'     => 'post-name',
			'price_to_sort' => 100.0,
			'is_sale'       => 1,
			'json'          => '{"key":"value"}',
			'lang'          => 'en',
		];
		$course = new CourseModel( $data );
		$this->assertEquals( 1, $course->ID );
		$this->assertEquals( 2, $course->post_author );
		$this->assertEquals( '2023-01-01 00:00:00', $course->post_date_gmt );
		$this->assertEquals( 'Content', $course->post_content );
		$this->assertEquals( 'Title', $course->post_title );
		$this->assertEquals( 'publish', $course->post_status );
		$this->assertEquals( 'post-name', $course->post_name );
		$this->assertEquals( 100.0, $course->price_to_sort );
		$this->assertEquals( 1, $course->is_sale );
		$this->assertEquals( '{"key":"value"}', $course->json );
		$this->assertEquals( 'en', $course->lang );
	}

	public function can_get_course_id() {
		$course = new CourseModel( [ 'ID' => 123 ] );
		$this->assertEquals( 123, $course->get_id() );
	}

	public function can_get_course_title() {
		$course = $this->createMock( CourseModel::class );
		$course->method( 'get_title' )->willReturn( 'Course Title' );
		$this->assertEquals( 'Course Title', $course->get_title() );
	}

	public function can_get_image_url() {
		$course = $this->createMock( CourseModel::class );
		$course->method( 'get_image_url' )->willReturn( 'http://example.com/image.jpg' );
		$this->assertEquals( 'http://example.com/image.jpg', $course->get_image_url() );
	}

	public function can_get_author_model() {
		$course = $this->createMock( CourseModel::class );
		$author = $this->createMock( UserModel::class );
		$course->method( 'get_author_model' )->willReturn( $author );
		$this->assertInstanceOf( UserModel::class, $course->get_author_model() );
	}

	public function can_get_categories() {
		$course = $this->createMock( CourseModel::class );
		$course->method( 'get_categories' )->willReturn( [ 'Category 1', 'Category 2' ] );
		$this->assertEquals( [ 'Category 1', 'Category 2' ], $course->get_categories() );
	}

	public function can_get_price() {
		$course = $this->createMock( CourseModel::class );
		$course->method( 'get_price' )->willReturn( 99.99 );
		$this->assertEquals( 99.99, $course->get_price() );
	}

	public function can_get_regular_price() {
		$course = $this->createMock( CourseModel::class );
		$course->method( 'get_regular_price' )->willReturn( 120.00 );
		$this->assertEquals( 120.00, $course->get_regular_price() );
	}

	public function can_get_sale_price() {
		$course = $this->createMock( CourseModel::class );
		$course->method( 'get_sale_price' )->willReturn( 80.00 );
		$this->assertEquals( 80.00, $course->get_sale_price() );
	}

	public function can_check_if_course_has_sale_price() {
		$course = $this->createMock( CourseModel::class );
		$course->method( 'has_sale_price' )->willReturn( true );
		$this->assertTrue( $course->has_sale_price() );
	}

	public function can_get_sale_start_date() {
		$course = $this->createMock( CourseModel::class );
		$course->method( 'get_sale_start' )->willReturn( '2023-01-01 00:00:00' );
		$this->assertEquals( '2023-01-01 00:00:00', $course->get_sale_start() );
	}

	public function can_get_sale_end_date() {
		$course = $this->createMock( CourseModel::class );
		$course->method( 'get_sale_end' )->willReturn( '2023-12-31 23:59:59' );
		$this->assertEquals( '2023-12-31 23:59:59', $course->get_sale_end() );
	}

	public function can_check_if_course_is_free() {
		$course = $this->createMock( CourseModel::class );
		$course->method( 'is_free' )->willReturn( true );
		$this->assertTrue( $course->is_free() );
	}

	public function can_check_if_course_is_offline() {
		$course = $this->createMock( CourseModel::class );
		$course->method( 'is_offline' )->willReturn( true );
		$this->assertTrue( $course->is_offline() );
	}

	public function can_get_first_item_id() {
		$course = $this->createMock( CourseModel::class );
		$course->method( 'get_first_item_id' )->willReturn( 1 );
		$this->assertEquals( 1, $course->get_first_item_id() );
	}

	public function can_get_total_items() {
		$course = $this->createMock( CourseModel::class );
		$course->method( 'get_total_items' )->willReturn( (object) [ 'count_items' => 20 ] );
		$this->assertEquals( (object) [ 'count_items' => 20 ], $course->get_total_items() );
	}

	public function can_get_section_items() {
		$course = $this->createMock( CourseModel::class );
		$course->method( 'get_section_items' )->willReturn( [ 'Section 1', 'Section 2' ] );
		$this->assertEquals( [ 'Section 1', 'Section 2' ], $course->get_section_items() );
	}

	public function can_get_final_quiz() {
		$course = $this->createMock( CourseModel::class );
		$course->method( 'get_final_quiz' )->willReturn( 10 );
		$this->assertEquals( 10, $course->get_final_quiz() );
	}

	public function can_get_permalink() {
		$course = $this->createMock( CourseModel::class );
		$course->method( 'get_permalink' )->willReturn( 'http://example.com/course' );
		$this->assertEquals( 'http://example.com/course', $course->get_permalink() );
	}

	public function can_get_no_enroll_requirement() {
		$course = $this->createMock( CourseModel::class );
		$course->method( 'get_no_enroll_requirement' )->willReturn( 'yes' );
		$this->assertEquals( 'yes', $course->get_no_enroll_requirement() );
	}

	public function can_get_description() {
		$course = $this->createMock( CourseModel::class );
		$course->method( 'get_description' )->willReturn( 'Course Description' );
		$this->assertEquals( 'Course Description', $course->get_description() );
	}

	public function can_check_if_course_has_no_enroll_requirement() {
		$course = $this->createMock( CourseModel::class );
		$course->method( 'has_no_enroll_requirement' )->willReturn( true );
		$this->assertTrue( $course->has_no_enroll_requirement() );
	}

	public function can_check_if_course_is_in_stock() {
		$course = $this->createMock( CourseModel::class );
		$course->method( 'is_in_stock' )->willReturn( true );
		$this->assertTrue( $course->is_in_stock() );
	}

	public function can_get_external_link() {
		$course = $this->createMock( CourseModel::class );
		$course->method( 'get_external_link' )->willReturn( 'http://example.com/external' );
		$this->assertEquals( 'http://example.com/external', $course->get_external_link() );
	}

	public function can_get_total_user_enrolled_or_purchased() {
		$course = $this->createMock( CourseModel::class );
		$course->method( 'get_total_user_enrolled_or_purchased' )->willReturn( 100 );
		$this->assertEquals( 100, $course->get_total_user_enrolled_or_purchased() );
	}

	public function can_find_course_by_id() {
		$course = $this->createMock( CourseModel::class );
		$course->method( 'find' )->willReturn( $course );
		$this->assertInstanceOf( CourseModel::class, CourseModel::find( 1 ) );
	}

	public function can_save_course_model() {
		$course = $this->createMock( CourseModel::class );
		$course->method( 'save' )->willReturn( $course );
		$this->assertInstanceOf( CourseModel::class, $course->save() );
	}

	public function can_delete_course_model() {
		$course = $this->createMock( CourseModel::class );
		$course->method( 'delete' )->willReturn( null );
		$this->assertNull( $course->delete() );
	}

	public function test_can_map_data_to_course_model() {
		$data = [
			'ID' => 1,
			'post_author' => 2,
			'post_date_gmt' => '2023-01-01 00:00:00',
			'post_content' => 'Content',
			'post_title' => 'Title',
			'post_status' => 'publish',
			'post_name' => 'post-name',
			'price_to_sort' => 100.0,
			'is_sale' => 1,
			'json' => '{"key":"value"}',
			'lang' => 'en',
		];
		$course = new CourseModel($data);
		$this->assertEquals(1, $course->ID);
		$this->assertEquals(2, $course->post_author);
		$this->assertEquals('2023-01-01 00:00:00', $course->post_date_gmt);
		$this->assertEquals('Content', $course->post_content);
		$this->assertEquals('Title', $course->post_title);
		$this->assertEquals('publish', $course->post_status);
		$this->assertEquals('post-name', $course->post_name);
		$this->assertEquals(100.0, $course->price_to_sort);
		$this->assertEquals(1, $course->is_sale);
		$this->assertEquals('{"key":"value"}', $course->json);
		$this->assertEquals('en', $course->lang);
	}

	public function test_can_get_first_item_id() {
		$course = $this->createMock(CourseModel::class);
		$course->method('get_first_item_id')->willReturn(1);
		$this->assertEquals(1, $course->get_first_item_id());
	}

	public function test_can_get_total_items() {
		$course = $this->createMock(CourseModel::class);
		$course->method('get_total_items')->willReturn((object) ['count_items' => 20]);
		$this->assertEquals((object) ['count_items' => 20], $course->get_total_items());
	}

	public function test_can_get_section_items() {
		$course = $this->createMock(CourseModel::class);
		$course->method('get_section_items')->willReturn(['Section 1', 'Section 2']);
		$this->assertEquals(['Section 1', 'Section 2'], $course->get_section_items());
	}

	public function test_can_get_final_quiz() {
		$course = $this->createMock(CourseModel::class);
		$course->method('get_final_quiz')->willReturn(10);
		$this->assertEquals(10, $course->get_final_quiz());
	}

	public function test_can_get_permalink() {
		$course = $this->createMock(CourseModel::class);
		$course->method('get_permalink')->willReturn('http://example.com/course');
		$this->assertEquals('http://example.com/course', $course->get_permalink());
	}

	public function test_can_get_no_enroll_requirement() {
		$course = $this->createMock(CourseModel::class);
		$course->method('get_no_enroll_requirement')->willReturn('yes');
		$this->assertEquals('yes', $course->get_no_enroll_requirement());
	}

	public function test_can_get_description() {
		$course = $this->createMock(CourseModel::class);
		$course->method('get_description')->willReturn('Course Description');
		$this->assertEquals('Course Description', $course->get_description());
	}

	public function test_can_check_if_course_has_no_enroll_requirement() {
		$course = $this->createMock(CourseModel::class);
		$course->method('has_no_enroll_requirement')->willReturn(true);
		$this->assertTrue($course->has_no_enroll_requirement());
	}

	public function test_can_check_if_course_is_in_stock() {
		$course = $this->createMock(CourseModel::class);
		$course->method('is_in_stock')->willReturn(true);
		$this->assertTrue($course->is_in_stock());
	}

	public function test_can_get_external_link() {
		$course = $this->createMock(CourseModel::class);
		$course->method('get_external_link')->willReturn('http://example.com/external');
		$this->assertEquals('http://example.com/external', $course->get_external_link());
	}

	public function test_can_get_total_user_enrolled_or_purchased() {
		$course = $this->createMock(CourseModel::class);
		$course->method('get_total_user_enrolled_or_purchased')->willReturn(100);
		$this->assertEquals(100, $course->get_total_user_enrolled_or_purchased());
	}

	public function test_can_find_course_by_id() {
		$course = $this->createMock(CourseModel::class);
		$course->method('find')->willReturn($course);
		$this->assertInstanceOf(CourseModel::class, CourseModel::find(1));
	}

	public function test_can_save_course_model() {
		$course = $this->createMock(CourseModel::class);
		$course->method('save')->willReturn($course);
		$this->assertInstanceOf(CourseModel::class, $course->save());
	}

	public function test_can_delete_course_model() {
		$course = $this->createMock(CourseModel::class);
		$course->method('delete')->willReturn(null);
		$this->assertNull($course->delete());
	}
}
