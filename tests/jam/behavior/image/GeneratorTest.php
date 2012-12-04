<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package Jam
 * @group   jam
 * @group   jam.behavior
 * @group   jam.behavior.cascade
 * @group   jam.behavior.image_generator
 */
class Jam_Behavior_Image_GeneratorTest extends Unittest_TestCase {

	public $test_temp;

	public function setUp()
	{
		$this->test_temp = realpath(join(DIRECTORY_SEPARATOR, array(dirname(__FILE__),  '..', '..', '..', 'test_data', 'temp'))).DIRECTORY_SEPARATOR;
		$this->environmentDefault = Arr::merge(
			array(
				'jam.image_generator.path_dir' => $this->test_temp,
				'jam.image_generator.web_dir' => '/temp/',
				'jam.image_generator.file' => ':model-:group/:id/:filename.jpg',
			),
			(array) $this->environmentDefault
		);

		parent::setUp();
	}

	public function test_initialize()
	{
		$meta = Jam::meta('test_gentest');

		$this->assertArrayHasKey('image_generator', $meta->behaviors());

		$this->assertArrayHasKey('image_generator_test1_filename', $meta->fields());
		$this->assertInstanceOf('Jam_Field_String', $meta->field('image_generator_test1_filename'));

		$this->assertArrayHasKey('image_generator_test2_filename', $meta->fields());
		$this->assertInstanceOf('Jam_Field_String', $meta->field('image_generator_test2_filename'));

		$this->assertEquals(Arr::get($meta->behaviors(), 'image_generator')->path_dir(), $this->test_temp);
		$this->assertEquals(Arr::get($meta->behaviors(), 'image_generator')->web_dir(), '/temp/');
	}

	public function test_generated_path()
	{
		$model = Jam::factory('test_gentest')->load_fields(array(
			'id' => 8, 
			'image_generator_test1_filename' => 'file1', 
			'image_generator_test2_filename' => 'file2'
		));

		$this->assertEquals($this->test_temp.'test_gentest-1/8/file1.jpg', $model->generated_path('test1'));
		$this->assertEquals($this->test_temp.'test_gentest-1/8/file2.jpg', $model->generated_path('test2'));
	}

	public function test_generated_url()
	{
		$model = Jam::factory('test_gentest')->load_fields(array(
			'id' => 8, 
			'image_generator_test1_filename' => 'file1', 
			'image_generator_test2_filename' => 'file2'
		));

		$this->assertEquals('/temp/test_gentest-1/8/file1.jpg', $model->generated_url('test1'));
		$this->assertEquals('/temp/test_gentest-1/8/file2.jpg', $model->generated_url('test2'));
	}

	public function test_model_after_save()
	{
		$model = Jam::factory('test_gentest')->load_fields(array(
			'id' => 8, 
			'image_generator_test1_filename' => 'file1', 
			'image_generator_test2_filename' => 'file2'
		));

		if ( ! is_dir(dirname($model->generated_path('test1'))))
		{
			mkdir(dirname($model->generated_path('test1')), 0777, TRUE);
		}

		file_put_contents($model->generated_path('test1'), 'tmp1');
		file_put_contents($model->generated_path('test2'), 'tmp2');

		$model->new_test1 = 'file1';
		$model->new_test2 = 'file-new';

		$model->clear_old_generated_images();

		$this->assertEquals($model->image_generator_test1_filename, 'file1');
		$this->assertEquals($model->image_generator_test2_filename, 'file2');

		$this->assertFileExists($model->generated_path('test1'));
		$this->assertFileNotExists($model->generated_path('test2'));
	}

	public function test_update_generated_image()
	{
		$model = Jam::factory('test_gentest')->load_fields(array(
			'id' => 8, 
			'image_generator_test1_filename' => 'file1', 
		));

		if ( ! is_dir(dirname($model->generated_path('test1'))))
		{
			mkdir(dirname($model->generated_path('test1')), 0777, TRUE);
		}

		if ( ! file_exists($model->generated_path('test1')))
		{
			unlink($model->generated_path('test1'));
		}

		$model->update_generated_image('test1');

		$this->assertFileExists($model->generated_path('test1'));
		list($width, $height, $type) = getimagesize($model->generated_path('test1'));
		$this->assertEquals(100, $width);
		$this->assertEquals(50, $height);
		$this->assertEquals(IMAGETYPE_JPEG, $type);
	}
}