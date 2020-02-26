<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package Jam
 * @group   jam
 * @group   jam.deep
 */
class Jam_deepTest extends Testcase_Database {

	public function test_hasone_deep()
	{
		$author = Jam::find('test_author', 1);

		$this->assertInstanceOf('Model_Test_Post', $author->test_post);
		$this->assertEquals(1, $author->test_post->id());
		$author->test_post = array('id' => 2, 'name' => 'changed post');

		$author->save();

		$this->assertInstanceOf('Model_Test_Post', $author->test_post);
		$this->assertEquals(2, $author->test_post->id());
		$this->assertEquals('changed post', $author->test_post->name());

		$author = Jam::find('test_author', 1);

		$this->assertInstanceOf('Model_Test_Post', $author->test_post);
		$this->assertEquals(2, $author->test_post->id());
		$author->test_post = array('id' => 3, 'name' => 'changed post');

		$author->save();

		$this->assertInstanceOf('Model_Test_Post', $author->test_post);
		$this->assertEquals(3, $author->test_post->id());
		$this->assertEquals('changed post', $author->test_post->name());
	}
}
