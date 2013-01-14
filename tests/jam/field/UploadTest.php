<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for core Jam methods.
 * @group jam
 * @group jam.field
 * @group jam.field.upload
 * @package Jam
 */
class Jam_Field_UploadTest extends Unittest_Jam_Upload_TestCase {

	public $field;
	public $model;

	public function setUp()
	{
		parent::setUp();

		$this->model = Jam::factory('test_image', 1);
		$this->field = $this->model->meta()->field('file');
	}

	public function test_attribute_before_check()
	{
		$upload = $this->getMock('Upload_File', array('save_to_temp'), array('default', 'file'));
		$upload->expects($this->once())->method('save_to_temp');
		$upload->source('http://example.com/test.png');
		$this->model->file = $upload;
		$this->field->model_before_check($this->model);
	}

	public function test_attribute_set()
	{
		$upload = $this->field->set($this->model, 'file1.png', FALSE);

		$this->assertInstanceOf('Upload_File', $upload);
		$this->assertEquals('file1.png', $upload->filename());
		$this->assertNull($upload->source());

		$upload = $this->field->set($this->model, 'http://example.com/test.png', TRUE);

		$this->assertInstanceOf('Upload_File', $upload);
		$this->assertEquals('http://example.com/test.png', $upload->source());
		$this->assertEquals('http://example.com/test.png', $upload->filename());
	}

	public function test_save()
	{
		$image = Jam::factory('test_image');

		$image->file = Upload_File::combine($this->test_local, 'source', 'logo.gif');
		$image->save();

		$this->assertFileExists($image->file->file());
		
		unlink($image->file->file());
		$image->delete();
	}

}