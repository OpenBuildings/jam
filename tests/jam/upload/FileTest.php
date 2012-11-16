<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Validator functionality.
 *
 * @package Jam
 * @group   jam.upload
 * @group   jam.upload.file
 */
class Jam_Upload_FileTest extends Unittest_Jam_Upload_TestCase {

	
	public function test_source()
	{
		$upload = new Upload_File('default', 'file');

		$this->assertNull($upload->source());

		$upload->source('http://example.com/file.png');

		$this->assertEquals('http://example.com/file.png', $upload->source()->data());
		$this->assertEquals('url', $upload->source()->type());

		$upload->source('populate/test_upload_populate.txt');

		$this->assertEquals('populate/test_upload_populate.txt', $upload->source()->data());
		$this->assertEquals('temp', $upload->source()->type());
		$this->assertEquals('populate', $upload->temp()->directory());
	}

	public function test_path()
	{
		$upload = new Upload_File('default', 'file');
		$upload->filename('file1.txt');

		$this->assertEquals('file', $upload->path());

		$this->assertEquals('/upload/file/file1.txt', $upload->url());
		$this->assertEquals('/upload/file/thumb/file1.txt', $upload->url('thumb'));
		$this->assertFileExists($upload->file());
		$this->assertEquals($this->test_local.'file'.DIRECTORY_SEPARATOR.'file1.txt', $upload->file());
	}

	public function test_temp_getter()
	{
		$upload = new Upload_File('default', 'file');

		$this->assertInstanceOf('Upload_Temp', $upload->temp());
		$this->assertNotNull($upload->temp()->directory());
	}

	public function test_save_and_delete()
	{
		$upload = new Upload_File('default', 'file');
		$file = $this->test_temp.DIRECTORY_SEPARATOR.'test'.DIRECTORY_SEPARATOR.'file_temp.txt';
		
		if ( ! file_exists(dirname($file)))
		{
			mkdir(dirname($file), 0777);
		}
		file_put_contents($file, 'temp');

		$upload->source('test/file_temp.txt');

		$upload->save();

		$this->assertFileExists($upload->file());
		$this->assertEquals(0, strpos($upload->file(), $this->test_local), 'The file should be in the local folder');

		$upload->delete();

		$this->assertFileNotExists($upload->file());
	}

	public function test_move_to_server()
	{
		$upload = new Upload_File('test_local', 'file');
		$file = $this->test_local.'test'.DIRECTORY_SEPARATOR.'file_temp.txt';
		
		if ( ! file_exists(dirname($file)))
		{
			mkdir(dirname($file), 0777);
		}
		file_put_contents($file, 'temp');

		$upload->path('test')->filename('file_temp.txt');

		$upload->move_to_server('test_local2');

		$new_file = $this->test_local2.'test'.DIRECTORY_SEPARATOR.'file_temp.txt';

		$this->assertFileNotExists($file);
		$this->assertFileExists($new_file);

		$this->assertEquals($new_file, $upload->file());
		
		rmdir($this->test_local.'test');
		unlink($new_file);
		rmdir($this->test_local2.'test');
	}

	public function test_save_to_temp()
	{
		$upload = new Upload_File('default', 'file');
		$file = $this->test_local.'file'.DIRECTORY_SEPARATOR.'file_temp.txt';

		file_put_contents($file, 'temp');

		$upload->source($file);
		$this->assertEquals('file', $upload->source()->type());

		$upload->save_to_temp();

		$this->assertFileExists($upload->file());
		$this->assertEquals(0, strpos($upload->file(), $this->test_temp), 'The file should be in the temp folder');

		$upload->clear();

		$this->assertFileNotExists($upload->file());
	}
}