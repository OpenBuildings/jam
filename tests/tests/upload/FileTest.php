<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Validator functionality.
 *
 * @package Jam
 * @group   jam.upload
 * @group   jam.upload.file
 */
class Jam_Upload_FileTest extends Testcase_Validate_Upload {

	public $upload;

	public function setUp()
	{
		parent::setUp();
		$this->upload = new Upload_File('default', 'file');
	}

	public function test_constructor()
	{
		$upload = new Upload_File('default', 'file', 'filename1.txt');
		$this->assertInstanceOf('Flex\Storage\Server', $upload->server());
		$this->assertEquals('file', $upload->path());
		$this->assertEquals('filename1.txt', $upload->filename());
	}

	public function test_transformations()
	{
		$this->assertEmpty($this->upload->transformations());

		$this->upload->transformations(array('resize' => array(10, 10)));

		$file = $this->test_temp.DIRECTORY_SEPARATOR.'test'.DIRECTORY_SEPARATOR.'test_logo.jpg';
		if ( ! is_dir(dirname($file)))
		{
			mkdir(dirname($file), 0777, TRUE);
		}
		copy($this->test_local.'name'.DIRECTORY_SEPARATOR.'logo.gif', $file);

		$this->upload->source('test'.DIRECTORY_SEPARATOR.'test_logo.jpg');

		$this->upload->transform();

		list($width, $height) = getimagesize($this->upload->file());

		$this->assertLessThanOrEqual(10, $width);
		$this->assertLessThanOrEqual(10, $height);

		unlink($this->upload->file());
		rmdir(dirname($this->upload->file()));
	}

	public function test_generate_thumbnails()
	{
		$this->upload->thumbnails(array(
			'small' => array('transformations' => array('resize' => array(10, 10))),
		));

		$file = $this->test_temp.DIRECTORY_SEPARATOR.'test'.DIRECTORY_SEPARATOR.'test_logo2.jpg';
		if ( ! is_dir(dirname($file)))
		{
			mkdir(dirname($file), 0777, TRUE);
		}
		copy($this->test_local.'name'.DIRECTORY_SEPARATOR.'logo.gif', $file);

		$this->upload->source('test'.DIRECTORY_SEPARATOR.'test_logo2.jpg');

		$this->upload->generate_thumbnails();
		$this->assertFileExists($this->upload->file('small'));
		list($width, $height) = getimagesize($this->upload->file('small'));

		$this->assertLessThanOrEqual(10, $width);
		$this->assertLessThanOrEqual(10, $height);

		unlink($this->upload->file('small'));
		unlink($this->upload->file());
		rmdir(dirname($this->upload->file('small')));
		rmdir(dirname($this->upload->file()));
	}

	public function test_source()
	{
		$this->assertNull($this->upload->source());

		$this->upload->source('http://example.com/file.png');

		$this->assertEquals('http://example.com/file.png', $this->upload->source()->data());
		$this->assertEquals('url', $this->upload->source()->type());

		$this->upload->source('populate/test_upload_populate.txt');

		$this->assertEquals('populate/test_upload_populate.txt', $this->upload->source()->data());
		$this->assertEquals('temp', $this->upload->source()->type());
		$this->assertEquals('populate', $this->upload->temp()->directory());
	}

	public function test_path()
	{
		$this->upload->filename('file1.txt');

		$this->assertEquals('file', $this->upload->path());

		$this->assertEquals('/upload/file/file1.txt', $this->upload->url());
		$this->assertEquals('/upload/file/thumb/file1.txt', $this->upload->url('thumb'));
		$this->assertFileExists($this->upload->file());
		$this->assertEquals($this->test_local.'file'.DIRECTORY_SEPARATOR.'file1.txt', $this->upload->file());
	}

	public function test_temp_getter()
	{
		$this->assertInstanceOf('Upload_Temp', $this->upload->temp());
		$this->assertNotNull($this->upload->temp()->directory());
	}

	public function test_temp_source()
	{
		$this->assertNull($this->upload->temp_source());
		$this->upload->source('name/file_temp.gif');
		$this->upload->filename('filename12.gif');

		$this->upload->temp_source('name/filename12.gif');
	}

	public function test_save_and_delete()
	{
		$this->upload->thumbnails(array(
			'small' => array('transformations' => array('resize' => array(10, 10))),
		));

		$file = $this->test_temp.DIRECTORY_SEPARATOR.'test'.DIRECTORY_SEPARATOR.'file_temp.gif';
		$thumb = $this->test_temp.DIRECTORY_SEPARATOR.'test'.DIRECTORY_SEPARATOR.'small'.DIRECTORY_SEPARATOR.'file_temp.gif';

		if ( ! file_exists(dirname($thumb)))
		{
			mkdir(dirname($thumb), 0777, TRUE);
		}
		copy($this->test_local.'name'.DIRECTORY_SEPARATOR.'logo.gif', $file);

		$this->upload->source('test/file_temp.gif');

		$this->upload->save();

		$this->assertFileExists($this->upload->file());
		$this->assertFileExists($this->upload->file('small'));
		$this->assertEquals(0, strpos($this->upload->file(), $this->test_local), 'The file should be in the local folder');
		$this->assertEquals(0, strpos($this->upload->file('small'), $this->test_local), 'The file should be in the local folder');

		$this->upload->delete();

		$this->assertFileNotExists($this->upload->file());
		$this->assertFileNotExists($this->upload->file('small'));
	}

	public function test_move_to_server()
	{
		$this->upload->thumbnails(array(
			'small' => array('transformations' => array('resize' => array(10, 10))),
		));

		$file = $this->test_local.'test'.DIRECTORY_SEPARATOR.'file_temp.txt';
		$thumb = $this->test_local.'test'.DIRECTORY_SEPARATOR.'small'.DIRECTORY_SEPARATOR.'file_temp.txt';

		if ( ! file_exists(dirname($thumb)))
		{
			mkdir(dirname($thumb), 0777, TRUE);
		}
		file_put_contents($file, 'temp');
		file_put_contents($thumb, 'temp thumb');

		$this->upload->path('test')->filename('file_temp.txt');

		$this->upload->move_to_server('test_local2');

		$new_file = $this->test_local2.'test'.DIRECTORY_SEPARATOR.'file_temp.txt';
		$new_thumb_file = $this->test_local2.'test'.DIRECTORY_SEPARATOR.'small'.DIRECTORY_SEPARATOR.'file_temp.txt';

		$this->assertFileNotExists($file);
		$this->assertFileNotExists($thumb);
		$this->assertFileExists($new_file);
		$this->assertFileExists($new_thumb_file);

		$this->assertEquals($new_file, $this->upload->file());
		$this->assertEquals($new_thumb_file, $this->upload->file('small'));

		rmdir($this->test_local.'test'.DIRECTORY_SEPARATOR.'small');
		rmdir($this->test_local.'test');
		unlink($new_thumb_file);
		unlink($new_file);
		rmdir($this->test_local2.'test'.DIRECTORY_SEPARATOR.'small');
		rmdir($this->test_local2.'test');
	}

	public function test_save_to_temp()
	{
		$file = $this->test_local.'file'.DIRECTORY_SEPARATOR.'file_temp.txt';

		file_put_contents($file, 'temp');

		$this->upload->source($file);
		$this->assertEquals('file', $this->upload->source()->type());

		$this->upload->save_to_temp();

		$this->assertFileExists($this->upload->file());
		$this->assertEquals(0, strpos($this->upload->file(), $this->test_temp), 'The file should be in the temp folder');

		$this->upload->clear();

		$this->assertFileNotExists($this->upload->file());
	}
}
