<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Builder SELECT functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.behavior
 * @group   jam.behavior.sluggable
 */
class Jam_Behavior_SluggableTest extends Testcase_Database {

	/**
	 * @covers Jam_Behavior_Sluggable::model_call_build_slug
	 * @covers Jam_Behavior_Sluggable::model_before_check
	 */
	public function test_set_no_primary_key()
	{
		$tag = Jam::find('test_tag', 1);

		$this->assertNotNull($tag);
		$this->assertNotNull($tag->slug);

		$tag->name = ' new tag j320&lt';
		$tag->check();

		$this->assertEquals('new-tag-j320lt', $tag->slug);
	}

	/**
	 * @covers Jam_Behavior_Sluggable::model_call_build_slug
	 * @covers Jam_Behavior_Sluggable::model_after_save
	 */
	public function test_save_with_primary_key()
	{
		$video = Jam::find('test_video', 1);

		$this->assertNotNull($video);
		$this->assertNotNull($video->slug);

		$video->file = ' new video j320&lt';
		$video->save();

		$this->assertEquals('new-video-j320lt-1', $video->slug);
	}

	/**
	 * @covers Jam_Behavior_Sluggable::builder_call_find_by_slug_insist
	 */
	public function test_select_no_primary_key()
	{
		$this->setExpectedException('Jam_Exception_Notfound');
		Jam::all('test_tag')->find_by_slug_insist('-j320lt');
	}

	/**
	 * @covers Jam_Behavior_Sluggable::model_call_build_slug
	 * @covers Jam_Behavior_Sluggable::model_after_save
	 */
	public function test_set()
	{
		$video = Jam::find('test_video', 1);

		$this->assertNotNull($video);
		$this->assertNotNull($video->slug);

		$video->file = 'new video.png';

		$slug = $video->build_slug();
		$this->assertEquals('new-videopng-1', $slug, 'Should have a method build_slug() that builds the correct slug');

		$video->save();

		$this->assertEquals('new-videopng-1', $video->slug);
	}

	/**
	 * @covers Jam_Behavior_Sluggable::builder_call_find_by_slug_insist
	 */
	public function test_slug_mismatch()
	{
		$this->setExpectedException('Jam_Exception_Slugmismatch');
		Jam::all('test_video')->find_by_slug_insist('video-jp2g-1');
	}

	public function provider_pattern()
	{
		return array(
			array('video-', FALSE),
			array('', FALSE),
			array('video', FALSE),
			array('pv3m-34-43mg', FALSE),
			array('video-1', TRUE),
		);
	}

	/**
	 * @dataProvider provider_pattern
	 * @covers Jam_Behavior_Sluggable::builder_call_where_slug
	 */
	public function test_where_pattern($pattern, $correct)
	{
		if ( ! $correct)
		{
			$this->setExpectedException('Kohana_Exception');
		}
		Jam::all('test_video')->where_slug($pattern);
	}

	/**
	 * @dataProvider provider_pattern
	 * @covers Jam_Behavior_Sluggable::builder_call_find_by_slug
	 */
	public function test_find($pattern, $correct)
	{
		if ( ! $correct)
		{
			$this->setExpectedException('Kohana_Exception');
		}
		Jam::all('test_video')->find_by_slug($pattern);
	}

	/**
	 * Test setting a slug explicitly when the auto_save option is set.
	 * @covers Jam_Behavior_Sluggable::model_after_save
	 */
	public function test_set_explicit_slug()
	{
		$video = Jam::find('test_video', 1);

		$video->set(array(
			'file' => 'viiideeoo.jpg',
			'slug' => 'some-other-video-1'
		));
		$this->assertSame('some-other-video-1', $video->slug);

		$video->save();
		$this->assertSame('some-other-video-1', $video->slug);

		$video = Jam::find('test_video', 1);
		$this->assertSame('some-other-video-1', $video->slug);
	}

	public function data_matches_slug()
	{
		return array(
			array(
				'test_video',
				'abcde',
				'abcde',
				TRUE
			),
			array(
				'test_video',
				'abcde',
				'abcdeeew',
				FALSE
			),
			array(
				'test_video',
				'',
				'abcdeeew',
				TRUE
			),
			array(
				'test_video',
				'',
				'',
				TRUE
			),
			array(
				'test_tag',
				'abcde',
				'abcde',
				TRUE
			),
			array(
				'test_tag',
				'abcde',
				'abcdeeew',
				TRUE
			),
			array(
				'test_tag',
				'',
				'abcdeeew',
				TRUE
			),
			array(
				'test_tag',
				'',
				'',
				TRUE
			),
		);
	}

	/**
	 * @dataProvider data_matches_slug
	 * @covers Jam_Behavior_Sluggable::model_call_matches_slug
	 */
	public function test_matches_slug($model_name, $model_slug, $slug, $expected)
	{
		$model = Jam::build($model_name);
		$model->slug = $model_slug;
		$this->assertSame($expected, $model->matches_slug($slug));
	}

	public function data_matches_slug_insist()
	{
		return array(
			array(
				'abcde',
				TRUE,
				NULL,
				TRUE
			),
			array(
				'abcde',
				FALSE,
				'Jam_Exception_Slugmismatch',
				NULL
			),
			array(
				'abcde',
				NULL,
				'Jam_Exception_Slugmismatch',
				NULL
			),
		);
	}

	/**
	 * @dataProvider data_matches_slug_insist
	 * @covers Jam_Behavior_Sluggable::model_call_matches_slug_insist
	 * @covers Jam_Exception_Slugmismatch::__construct
	 */
	public function test_matches_slug_insist($slug, $return_value, $expected_exception, $expected_result)
	{
		$model = $this->getMock('Model_Test_Video', array('matches_slug'), array('test_video'), '', TRUE, TRUE, TRUE, FALSE, TRUE);

		$model
			->expects($this->once())
			->method('matches_slug')
			->with($this->equalTo($slug))
			->will($this->returnValue($return_value));

		if ($expected_exception)
		{
			$this->setExpectedException($expected_exception);
		}

		$actual_result = $model->matches_slug_insist($slug);

		$this->assertSame($expected_result, $actual_result);
	}

	public function test_create_and_auto_build_slug()
	{
		$model = Jam::build('test_video');
		$model->file = 'abcde';
		$model->save();
		$this->assertEquals('abcde-'.$model->id(), $model->slug);
	}

	public function data_where_slug()
	{
		return array(
			array(
				'video-jpg-1',
				1
			),
			array(
				'video-jpg-2',
				2
			),
			array(
				'blabla-bla-2',
				2
			),
			array(
				'3-blabla-bla-2',
				2
			),
			array(
				'300-blabla-bla-2',
				2
			),
			array(
				'4',
				4
			),
		);
	}

	/**
	 * @dataProvider data_where_slug
	 * @covers Kohana_Jam_Behavior_Sluggable::builder_call_where_slug
	 */
	public function test_where_slug($slug, $expected_primary_key)
	{
		$model = Jam::all('test_video')
			->where_slug($slug)
			->first();

		$this->assertNotNull($model);
		$this->assertSame($expected_primary_key, $model->id());
	}
}
