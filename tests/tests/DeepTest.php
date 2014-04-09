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
					5,
				),
				'test_images' => Jam::build('test_image', array(
					'file' => 'file11',
					'test_copyright' => Jam::build('test_copyright', array('name' => 'copy 1'))
				)),
				'test_blog' => array('name' => 'new blog')
			)),
			Jam::build('test_post', array(
				'name' => 'software',
				'test_categories' => array(
					Jam::build('test_category', array('name' => 'cat3', 'test_author' => $author)),
					Jam::build('test_category', array('name' => 'cat4', 'test_author' => $author)),
					'Category Two',
				),
				'test_blog' => array('id' => 1, 'name' => 'new blog title')
			)),
			array(
				'name' => 'new post',
				'test_cover_image' => array(
					'file' => 'new file',
				)
			),
		);

		$this->assertSame($author, $author->test_posts[0]->test_categories[0]->test_author, 'Should be the same object');

		$this->assertInstanceOf('Model_Test_Blog', $author->test_posts[0]->test_blog);
		$this->assertEquals('new blog', $author->test_posts[0]->test_blog->name());
		$this->assertFalse($author->test_posts[0]->test_blog->loaded());

		$this->assertInstanceOf('Model_Test_Blog', $author->test_posts[1]->test_blog);
		$this->assertEquals('new blog title', $author->test_posts[1]->test_blog->name());
		$this->assertEquals(1, $author->test_posts[1]->test_blog->id());
		$this->assertTrue($author->test_posts[1]->test_blog->loaded());

		$this->assertInstanceOf('Model_Test_Post', $author->test_posts[2]);
		$this->assertEquals('new post', $author->test_posts[2]->name());
		$this->assertFalse($author->test_posts[2]->loaded());

		$this->assertInstanceOf('Model_Test_Image', $author->test_posts[2]->test_cover_image);
		$this->assertEquals('new file', (string) $author->test_posts[2]->test_cover_image->file);
		$this->assertFalse($author->test_posts[2]->test_cover_image->loaded());

		$this->assertTrue($author->test_posts[0]->test_categories->has(1));
		$this->assertTrue($author->test_posts[0]->test_categories->has(5));

		$author->save();

		$author = Jam::all('test_author')->where('name', '=', 'Joe')->first();

		$this->assertEquals($author->id(), $author->test_posts[0]->test_categories[0]->test_author->id(), 'Should be the same author');
		$this->assertEquals('cat1', $author->test_posts[0]->test_categories[0]->name(), 'Should have created the test_category');
		$this->assertEquals('Category One', $author->test_posts[0]->test_categories[2]->name(), 'Should have loaded the category based on id');
		$this->assertEquals($author->id(), $author->test_posts[0]->test_categories[1]->test_author->id(), 'Should be the same author');

		$this->assertCount(1, $author->test_posts[0]->test_images);
		$this->assertNotNull($author->test_posts[0]->test_images[0]->test_copyright);
		$this->assertEquals($author->test_posts[0]->test_images[0]->file, 'file11');
		$this->assertEquals($author->test_posts[0]->test_images[0]->test_copyright->name(), 'copy 1');

		$this->assertEquals($author->id(), $author->test_posts[1]->test_categories[0]->test_author->id(), 'Should be the same author');
		$this->assertEquals('cat3', $author->test_posts[1]->test_categories[0]->name, 'Should have created the test_category');
		$this->assertEquals('Category Two', $author->test_posts[1]->test_categories[2]->name(), 'Should have loaded the category based on name');
		$this->assertEquals($author->id(), $author->test_posts[1]->test_categories[1]->test_author->id(), 'Should be the same author');

		$this->assertInstanceOf('Model_Test_Blog', $author->test_posts[0]->test_blog);
		$this->assertEquals('new blog', $author->test_posts[0]->test_blog->name());
		$this->assertTrue($author->test_posts[0]->test_blog->loaded());

		$this->assertInstanceOf('Model_Test_Blog', $author->test_posts[1]->test_blog);
		$this->assertEquals('new blog title', $author->test_posts[1]->test_blog->name());
		$this->assertEquals(1, $author->test_posts[1]->test_blog->id());
		$this->assertTrue($author->test_posts[1]->test_blog->loaded());

		$this->assertInstanceOf('Model_Test_Image', $author->test_posts[2]->test_cover_image);
		$this->assertEquals('new file', (string) $author->test_posts[2]->test_cover_image->file);
		$this->assertTrue($author->test_posts[2]->test_cover_image->loaded());

		$this->assertTrue($author->test_posts[0]->test_categories->has(1));
		$this->assertTrue($author->test_posts[0]->test_categories->has(5));

	}
}
