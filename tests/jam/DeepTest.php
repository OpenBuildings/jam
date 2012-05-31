<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Collection.
 *
 * @package Jam
 * @group   jam
 * @group   jam.deep
 */
class Jam_deepTest extends Unittest_Jam_TestCase {

	public function test_association_create()
	{
		$author = Jam::build('test_author', array('name' => 'Joe'));
		$author->test_posts = array(
			Jam::build('test_post', array(
				'name' => 'hardware',
				'test_categories' => array(
					Jam::build('test_category', array('name' => 'cat1', 'test_author' => $author)),
					Jam::build('test_category', array('name' => 'cat2', 'test_author' => $author)),
				)
			)),
			Jam::build('test_post', array(
				'name' => 'software',
				'test_categories' => array(
					Jam::build('test_category', array('name' => 'cat1', 'test_author' => $author)),
					Jam::build('test_category', array('name' => 'cat2', 'test_author' => $author)),
				)
			)),
		);

		$this->assertSame($author, $author->test_posts[0]->test_categories[0]->test_author, 'Should be the same object');

		$author->save();
		
		$author = Jam::query('test_author')->where('name', '=', 'Joe')->find();

		$this->assertEquals($author->id(), $author->test_posts[0]->test_categories[0]->test_author->id(), 'Should be the same author');
		$this->assertEquals($author->id(), $author->test_posts[0]->test_categories[1]->test_author->id(), 'Should be the same author');
		$this->assertEquals($author->id(), $author->test_posts[1]->test_categories[0]->test_author->id(), 'Should be the same author');
		$this->assertEquals($author->id(), $author->test_posts[1]->test_categories[1]->test_author->id(), 'Should be the same author');

	}
}