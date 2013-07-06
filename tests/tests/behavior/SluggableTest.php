<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Builder SELECT functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.behavior
 * @group   jam.behavior.sluggable
 */
class Jam_Behavior_SluggableTest extends Testcase_Functest {

	public function test_set_no_primary_key()
	{
		$tag = Jam::find('test_tag', 1);

		$this->assertNotNull($tag);
		$this->assertNotNull($tag->slug);

		$tag->name = ' new tag j320&lt';
		$tag->save();

		$this->assertEquals('new-tag-j320lt', $tag->slug);
	}

	public function test_select_no_primary_key()
	{
		$this->setExpectedException('Jam_Exception_Notfound');
		Jam::all('test_tag')->find_by_slug_insist('-j320lt');
	}

	public function test_set()
	{
		$video = Jam::find('test_video', 1);

		$this->assertNotNull($video);
		$this->assertNotNull($video->slug);

		$video->name = 'new video.png';

		$slug = $video->build_slug();
		$this->assertEquals('new-videopng-1', $slug, 'Should have a method build_slug() that builds the correct slug');
		
		$video->save();

		$this->assertEquals('new-videopng-1', $video->slug);
	}

	public function test_select()
	{
		$this->setExpectedException('Jam_Exception_Sluggable');
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
	 * @return NULL
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
	 * @return NULL
	 */
	public function test_find($pattern, $correct)
	{
		if ( ! $correct)
		{
			$this->setExpectedException('Kohana_Exception');
		}
		Jam::all('test_video')->where_slug($pattern)->first();
	}

}