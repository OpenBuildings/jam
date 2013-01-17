<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Collection.
 *
 * @package Jam
 * @group   jam
 * @group   jam.deep
 */
class Jam_deepTest extends Unittest_Jam_Database_TestCase {

	public function test_association_create()
	{
		$author = Jam::build('test_author', array('name' => 'Joe'));
		$author->test_posts = array(
			Jam::build('test_post', array(
				'name' => 'hardware',
				'test_categories' => array(
					Jam::build('test_category', array('name' => 'cat1', 'test_author' => $author)),
					Jam::build('test_category', array('name' => 'cat2', 'test_author' => $author)),
					array('id' => 1),
				)
			)),
			Jam::build('test_post', array(
				'name' => 'software',
				'test_categories' => array(
					Jam::build('test_category', array('name' => 'cat3', 'test_author' => $author)),
					Jam::build('test_category', array('name' => 'cat4', 'test_author' => $author)),
					'Category Two',
				)
			)),
		);

		$this->assertSame($author, $author->test_posts[0]->test_categories[0]->test_author, 'Should be the same object');

		$author->save();

		$author = Jam::all('test_author')->where('name', '=', 'Joe')->first();

		$this->assertEquals($author->id(), $author->test_posts[0]->test_categories[0]->test_author->id(), 'Should be the same author');
		$this->assertEquals('cat1', $author->test_posts[0]->test_categories[0]->name(), 'Should have created the test_category');
		$this->assertEquals('Category One', $author->test_posts[0]->test_categories[2]->name(), 'Should have loaded the category based on id');
		$this->assertEquals($author->id(), $author->test_posts[0]->test_categories[1]->test_author->id(), 'Should be the same author');


		$this->assertEquals($author->id(), $author->test_posts[1]->test_categories[0]->test_author->id(), 'Should be the same author');
		$this->assertEquals('cat3', $author->test_posts[1]->test_categories[0]->name, 'Should have created the test_category');
		$this->assertEquals('Category Two', $author->test_posts[1]->test_categories[2]->name(), 'Should have loaded the category based on name');
		$this->assertEquals($author->id(), $author->test_posts[1]->test_categories[1]->test_author->id(), 'Should be the same author');
	}
}