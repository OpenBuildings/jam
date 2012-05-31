<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests HasMany fields.
 *
 * @package Jelly
 * @group   jelly
 * @group   jelly.association
 * @group   jelly.association.has_many
 */
class Jelly_Association_HasManyTest extends Unittest_Jelly_TestCase {

	/**
	 * Provider for test_builder
	 */
	public function provider_builder()
	{
		return array(
			array(array('test_author', 1, 'test_posts'), 2),
			array(array('test_author', 555, 'test_posts'), 0),
			array(array('test_post', 1, 'test_tags'), 4),
			array(array('test_post', 1, 'test_images'), 1),
			array(array('test_post', 1, 'test_tags', 'list_items'), 2),
			array(array('test_post', 1, 'test_tags', 'non_list_items'), 2)
		);
	}
	
	/**
	 * Tests Jelly_Field_HasMany::builder()
	 * 
	 * @dataProvider  provider_builder
	 */
	public function test_builder($args, $count)
	{
		$builder = Jelly::factory($args[0], $args[1])->builder($args[2]);
		if (isset($args[3]))
		{
			$method = $args[3];
			$builder = $builder->$method();
		}
		$this->assertTrue($builder instanceof Jelly_Builder);
		
		// Select the result
		$result = $builder->select();
		
		// Should now be a collection
		$this->assertInstanceOf('Jelly_Collection', $result);
		$this->assertCount($count, $result);
		
		foreach ($result as $row)
		{
			$this->assertGreaterThan(0, $row->id());
			$this->assertTrue($row->loaded());
		}
	}

	public function test_add()
	{
		$post = Jelly::factory('test_post', 1);
		$tags = $post->test_tags;
		$tag = Jelly::create('test_tag', array('name' => 'New Tag', 'test_post' => 20, 'test_blogs' => 1));

		$this->assertCount(4, $tags);

		$tags->add($tag);
		$this->assertCount(5, $tags);
		$this->assertTrue($tags->exists($tag));

		$post->save();

		$new_tags = Jelly::factory('test_post', 1)->test_tags;
		$this->assertCount(5, $new_tags);
		$this->assertTrue($tags->exists($tag));
	}
	
	public function test_add_unsaved()
	{
		$post = Jelly::factory('test_post', 1);
		$tags = $post->test_tags;
		$tag = Jelly::build('test_tag', array('name' => 'New Tag', 'test_post' => 20, 'test_blogs' => 1));

		$this->assertCount(4, $tags, "Jelly_Collection::add should work with not saved objects as well!");

		$tags->add($tag);
		$this->assertCount(5, $tags);
		$this->assertTrue($tags->exists($tag));

		$post->save();

		$new_tags = Jelly::factory('test_post', 1)->test_tags;
		$this->assertCount(5, $new_tags);
		$this->assertTrue($tags->exists($tag));
	}

	public function test_add_array()
	{
		$post = Jelly::factory('test_post', 1);
		$tags = $post->test_tags;

		$new_tags[] = Jelly::create('test_tag', array('name' => 'New Tag 1', 'test_post_id' => 20, 'test_blogs' => 1));
		$new_tags[] = Jelly::create('test_tag', array('name' => 'New Tag 2', 'test_post_id' => 20, 'test_blogs' => 1));

		$this->assertCount(4, $tags);

		$tags->add($new_tags);
		$this->assertCount(6, $tags);
		$this->assertTrue($tags->exists($new_tags[0]));
		$this->assertTrue($tags->exists($new_tags[1]));

		$post->save();

		$new_tags = Jelly::factory('test_post', 1)->test_tags;
		$this->assertCount(6, $tags);
		$this->assertTrue($tags->exists($new_tags[0]));
		$this->assertTrue($tags->exists($new_tags[1]));
	}

	public function test_add_collection()
	{
		$post = Jelly::factory('test_post', 1);
		$tags = $post->test_tags;

		$new_tags[] = Jelly::create('test_tag', array('name' => 'New Tag 1', 'test_post_id' => 20, 'test_blogs' => 1))->id();
		$new_tags[] = Jelly::create('test_tag', array('name' => 'New Tag 2', 'test_post_id' => 20, 'test_blogs' => 1))->id();

		$new_tags = Jelly::query('test_tag')->key($new_tags)->select_all();

		$this->assertCount(4, $tags);

		$tags->add($new_tags);
		$this->assertCount(6, $tags);
		$this->assertTrue($tags->exists($new_tags[0]));
		$this->assertTrue($tags->exists($new_tags[1]));

		$post->save();

		$new_tags = Jelly::factory('test_post', 1)->test_tags;
		$this->assertCount(6, $tags);
		$this->assertTrue($tags->exists($new_tags[0]));
		$this->assertTrue($tags->exists($new_tags[1]));
	}

	public function test_remove()
	{
		$post = Jelly::factory('test_post', 1);
		$tag = Jelly::factory('test_tag', 1);

		$this->assertCount(4, $post->test_tags);

		$post->test_tags->remove($tag);
		$this->assertCount(3, $post->test_tags);

		$this->assertFalse($post->test_tags->exists($tag));
		$post->save();

		$new_tags = Jelly::factory('test_post', 1)->test_tags;
		$this->assertCount(3, $new_tags);
		$this->assertFalse($post->test_tags->exists($tag));
	}

	public function test_remove_collection()
	{
		$post = Jelly::factory('test_post', 1);
		$tags = $post->test_tags;
		$tag = Jelly::query('test_tag')->key(1)->select_all();

		$this->assertCount(4, $tags);

		$tags->remove($tag);
		$this->assertCount(3, $tags);

		$this->assertFalse($tags->exists($tag[0]));
		$post->save();

		$new_tags = Jelly::factory('test_post', 1)->test_tags;
		$this->assertCount(3, $new_tags);
		$this->assertFalse($tags->exists($tag[0]));
	}

	public function test_remove_array()
	{
		$post = Jelly::factory('test_post', 1);
		$tags = $post->test_tags;
		$tag = array(Jelly::factory('test_tag', 1));

		$this->assertCount(4, $tags);

		$tags->remove($tag);
		$this->assertCount(3, $tags);

		$this->assertFalse($tags->exists($tag[0]));
		$post->save();

		$new_tags = Jelly::factory('test_post', 1)->test_tags;
		$this->assertCount(3, $new_tags);
		$this->assertFalse($tags->exists($tag[0]));
	}


	public function test_inverse()
	{
		$post = Jelly::factory('test_post', 1);

		$this->assertInstanceOf('Jelly_Collection', $post->test_tags);
		$this->assertSame($post, $post->test_tags[0]->test_post);

		$new_tag = $post->test_tags->build(array('name' => 'new'));
		$this->assertSame($post, $new_tag->test_post);

		$new_tag = Jelly::factory('test_tag')->set(array('name' => 'New Tag', 'test_post_id' => 20, 'test_blogs' => 1))->save();

		$post->test_tags[] = $new_tag;
		$this->assertSame($post, $new_tag->test_post);

		$new_tag = Jelly::factory('test_tag')->set(array('name' => 'New Tag', 'test_post_id' => 2, 'test_blogs' => 1));

		$post->test_tags->add($new_tag);
		$this->assertSame($post, $new_tag->test_post);
	}

	public function test_build()
	{
		$author = Jelly::factory('test_author', 1);

		$new_post = $author->test_posts->build(array('name' => 'new', 'slug' => 'new'));
		$this->assertTrue($author->test_posts->exists($new_post));
		
		$author->save();
		$new_post->save();

		$this->assertTrue(Jelly::factory('test_author', 1)->test_posts->exists($new_post));

	}

	public function test_create()
	{
		$author = Jelly::factory('test_author', 1);

		$new_post = $author->test_posts->create(array('name' => 'new'));
		$this->assertTrue($author->test_posts->exists($new_post));
		$author->save();

		$this->assertTrue(Jelly::factory('test_author', 1)->test_posts->exists($new_post));
	}

	public function test_exists()
	{
		$post = Jelly::factory('test_post', 1);
		$tag = Jelly::factory('test_tag', 1);
		$other_tag = Jelly::factory('test_tag', 3);
		$other_post_by_name = Jelly::factory('test_post', 'Second Post 2');

		$this->assertTrue($post->test_tags->exists($tag), 'Should have tag in test_tags collection');
		$this->assertFalse($post->test_tags->exists($other_tag), 'Should not have tag in test_tags collection');

		$this->assertTrue($post->test_tags->exists('red'), 'Should have tag in test_tags collection by name');
		$this->assertFalse($post->test_tags->exists('orange'), 'Should not have tag in test_tags collection by name');
	}

	public function test_delete()
	{
		$test_blog = Jelly::factory('test_blog', 1);
		$test_blog_id = $test_blog->id();
		$test_posts_ids = $test_blog->test_posts->as_array(NULL, 'id');

		$test_blog->delete();
		$this->assertNotExists('test_blog', $test_blog_id);
		$this->assertNotExists('test_post', $test_posts_ids);
	}

	public function test_mass_assignment()
	{
		$post = Jelly::factory('test_post', 1);
		$post->test_tags = array(
			array('name' => 'new name 1', 'test_blogs' => '1'),
			array('name' => 'new name 2', 'test_blogs' => '1'),
			array('id' => 1, 'name' => 'new name 3', 'test_blogs' => '1')
		);

		$this->assertCount(3, $post->test_tags);
		$this->assertEquals('new name 1', $post->test_tags[0]->name);
		$this->assertEquals('new name 2', $post->test_tags[1]->name);
		$this->assertEquals('new name 3', $post->test_tags[2]->name);
		$this->assertEquals(1, $post->test_tags[2]->id());

		$post->save();

		$post = Jelly::factory('test_post', 1);
		
		$this->assertCount(3, $post->test_tags);
		$this->assertEquals('new name 3', $post->test_tags[0]->name);
		$this->assertEquals('new name 1', $post->test_tags[1]->name);
		$this->assertEquals('new name 2', $post->test_tags[2]->name);
		$this->assertEquals(1, $post->test_tags[0]->id());

		$this->assertEquals('new name 3', Jelly::factory('test_tag', 1)->name());
	}

	public function test_deep_mass_assignment()
	{
		$author = Jelly::factory('test_author', 1);
		$author->set(array(
			'test_post' => array(
				'name' => 'New Post',
				'test_tags' => array(
					array('name' => 'new name 1', 'test_blogs' => '1'),
					array('name' => 'new name 2', 'test_blogs' => '1'),
					array('id' => 1, 'name' => 'new name 3', 'test_blogs' => '1')
				)
			)
		));

		$this->assertEquals('New Post', $author->test_post->name);
		$this->assertCount(3, $author->test_post->test_tags);
		$this->assertEquals('new name 1', $author->test_post->test_tags[0]->name);
		$this->assertEquals('new name 2', $author->test_post->test_tags[1]->name);
		$this->assertEquals('new name 3', $author->test_post->test_tags[2]->name);
		$this->assertEquals(1, $author->test_post->test_tags[2]->id());

		$author->save();

		$author = Jelly::factory('test_author', 1);
		
		$this->assertEquals('New Post', $author->test_post->name);
		$this->assertCount(3, $author->test_post->test_tags);
		$this->assertEquals('new name 1', $author->test_post->test_tags[1]->name);
		$this->assertEquals('new name 2', $author->test_post->test_tags[2]->name);
		$this->assertEquals('new name 3', $author->test_post->test_tags[0]->name);
		$this->assertEquals(1, $author->test_post->test_tags[0]->id());
	}

	public function test_mass_assoignment_collection()
	{
		$post = Jelly::factory('test_post', 1);
		$new_tags = Jelly::query("test_tag")->limit(2)->select_all();
		$post->test_tags = $new_tags;

		$this->assertCount(2, $post->test_tags);
		$this->assertEquals($new_tags[0]->name(), $post->test_tags[0]->name());
		$this->assertEquals($new_tags[1]->name(), $post->test_tags[1]->name());

		$post->save();

		$post = Jelly::factory('test_post', 1);

		$this->assertCount(2, $post->test_tags);
		$this->assertEquals($new_tags[0]->name(), $post->test_tags[0]->name());
		$this->assertEquals($new_tags[1]->name(), $post->test_tags[1]->name());
	}

	public function test_polymorphic_as()
	{
		$post = Jelly::factory('test_post', 1);
		$image = Jelly::factory('test_image')->set(array('file' => 'new file.file'))->save();
		$post->test_images->add($image);
		$post->save();

		$image = Jelly::factory('test_image', $image->id());

		$this->assertEquals($post->id(), $image->test_holder->id());
		$this->assertTrue(Jelly::factory('test_post', 1)->test_images->exists($image));
	}

	public function test_polymorphic_mass_assignment()
	{
		$test_post = Jelly::factory('test_post', 1);

		$test_post->test_images = array(
			array('test_image' => array('id' => 1, 'file' => 'file3.jpg')),
			array('test_image' => array('file' => 'file1.jpg')),
			array('test_image' => array('file' => 'file2.jpg')),
			'',
			'' => '',
		);
		
		$this->assertInstanceOf('Model_Test_Image', $test_post->test_images[0]);
		$this->assertEquals('file3.jpg', $test_post->test_images[0]->file);
		$this->assertEquals(1, $test_post->test_images[0]->id());

		$this->assertInstanceOf('Model_Test_Image', $test_post->test_images[1]);
		$this->assertEquals('file1.jpg', $test_post->test_images[1]->file);

		$this->assertInstanceOf('Model_Test_Image', $test_post->test_images[2]);
		$this->assertEquals('file2.jpg', $test_post->test_images[2]->file);


		$test_post->save();

		$this->assertCount(3, $test_post->test_images);


		$this->assertInstanceOf('Model_Test_Image', $test_post->test_images[0]);
		$this->assertEquals('file3.jpg', $test_post->test_images[0]->file);
		$this->assertEquals(1, $test_post->test_images[0]->id());

		$this->assertInstanceOf('Model_Test_Image', $test_post->test_images[1]);
		$this->assertEquals('file1.jpg', $test_post->test_images[1]->file);

		$this->assertInstanceOf('Model_Test_Image', $test_post->test_images[2]);
		$this->assertEquals('file2.jpg', $test_post->test_images[2]->file);

		$test_post = Jelly::factory('test_post', 1);

		$this->assertInstanceOf('Model_Test_Image', $test_post->test_images[0]);
		$this->assertEquals('file3.jpg', $test_post->test_images[0]->file);
		$this->assertEquals(1, $test_post->test_images[0]->id());

		$this->assertInstanceOf('Model_Test_Image', $test_post->test_images[1]);
		$this->assertEquals('file1.jpg', $test_post->test_images[1]->file);

		$this->assertInstanceOf('Model_Test_Image', $test_post->test_images[2]);
		$this->assertEquals('file2.jpg', $test_post->test_images[2]->file);
	}

	public function test_polymorphic_join()
	{
		$this->assertEquals(1, Jelly::query('test_post')
			->join_association('test_images')
			->count());
	}
}
