<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Validator functionality.
 *
 * @package Jam
 * @group   jam.upload
 * @group   jam.upload.file
 */
class Jam_Upload_FileTest extends Unittest_Jam_Upload_TestCase {

	public function data_sanitize()
	{
		return array(
			array('filename.jpg', 'filename.jpg'),
			array('file 1 name.jpg', 'file-1-name.jpg'),
			array('filênàme.jpg', 'filename.jpg'),
			array('филенаме.jpg', 'filename.jpg'),
			array('file- -1.jpg', 'file-1.jpg'),
		);
	}

	/**
	 * @dataProvider data_sanitize
	 */
	public function test_sanitize($filename, $sanitized_filename)
	{
		$this->assertEquals($sanitized_filename, Upload_File::sanitize($filename));
	}

	public function data_filenames_candidates_from_url()
	{
		return array(
			array('http://example.com/logo.png', array('logo.png')),
			array('http://example.com/logo.jpg?test=1', array('1', 'logo.jpg')),
			array('http://example.com/file?test=logo.gif', array('logo.gif', 'file')),
			array('http://example.com/file?test=logo.png&post=get', array('logo.png', 'get', 'file')),
			array('http://example.com/file.php?test=logo.png&post=get', array('logo.png', 'get', 'file.php'))
		);
	}

	/**
	 * @dataProvider data_filenames_candidates_from_url
	 */
	public function test_filenames_candidates_from_url($url, $filename_candidates)
	{
		$this->assertEquals($filename_candidates, Upload_File::filenames_candidates_from_url($url));
	}

	public function data_filename_from_url()
	{
		return array(
			// FROM URL
			array('http://example.com/logo.gif', 'image/gif', '/logo\.gif$/'),
			array('http://example.com/logo.php', 'image/png', '/logo\.png$/'),
			array('http://example.com/logo.jpg?query=param', 'image/jpeg', '/logo\.jpg$/'),
			array('http://example.com/logo?query=test&post=1', 'image/jpg', '/.+\.jpg$/'),
			array('http://example.com/logo?file=logo.json', NULL, '/logo\.json$/'),
			array('http://example.com/logo.php?file=logo.gif', NULL, '/logo\.gif$/'),
			array('http://example.com/test', NULL, '/.+\.jpg$/'),
		);
	}

		/**
	 * @dataProvider data_filename_from_url
	 */
	public function test_filename_from_url($url, $mime, $expected_regexp)
	{
		$filename = Upload_File::filename_from_url($url, $mime);

		$this->assertRegExp($expected_regexp, $filename);
	}


	public function data_combine()
	{
		return array(
			array(array('test', 'test2'), 'test'.DIRECTORY_SEPARATOR.'test2'),
			array(array('test', 'test2'.DIRECTORY_SEPARATOR), 'test'.DIRECTORY_SEPARATOR.'test2'),
			array(array('test'.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR.'test2'), 'test'.DIRECTORY_SEPARATOR.'test2'),
			array(array('test'.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR.'test2'.DIRECTORY_SEPARATOR), 'test'.DIRECTORY_SEPARATOR.'test2'),
			array(array(DIRECTORY_SEPARATOR.'test', DIRECTORY_SEPARATOR.'test2'), DIRECTORY_SEPARATOR.'test'.DIRECTORY_SEPARATOR.'test2'),
			array(array(DIRECTORY_SEPARATOR.'test', DIRECTORY_SEPARATOR.'test2', 'test3'), DIRECTORY_SEPARATOR.'test'.DIRECTORY_SEPARATOR.'test2'.DIRECTORY_SEPARATOR.'test3'),
			array(array(DIRECTORY_SEPARATOR.'test', DIRECTORY_SEPARATOR.'test2', 'test3', 'test4'), DIRECTORY_SEPARATOR.'test'.DIRECTORY_SEPARATOR.'test2'.DIRECTORY_SEPARATOR.'test3'.DIRECTORY_SEPARATOR.'test4'),
		);
	}
	
	/**
	 * @dataProvider data_combine
	 */
	public function test_combine($filename_parts, $combined_filename)
	{
		$this->assertEquals($combined_filename, call_user_func_array('Upload_File::combine', $filename_parts));
	}

	public function data_is_filename()
	{
		return array(
			array('file.png', TRUE),
			array('file.js', TRUE),
			array('/to/file/file.png', TRUE),
			array('http://example.com/file.png', TRUE),
			array('http://example.com/page', FALSE),
			array('/to/file/dir', FALSE),
			array('page', FALSE),
		);
	}


	/**
	 * @dataProvider data_is_filename
	 */
	public function test_is_filename($filename, $is_filename)
	{
		$this->assertEquals($is_filename, Upload_File::is_filename($filename));
	}

	public function data_source_type()
	{
		$test_local = join(DIRECTORY_SEPARATOR, array(dirname(__FILE__), '..', '..', 'test_data', 'test_local')).DIRECTORY_SEPARATOR;

		return array(
			array('http://example.com/file.png', 'url'),
			array('http://sub.example.com/deep/nesting/file.png', 'url'),
			array('php://input', 'stream'),
			array('populate/test_upload_populate.txt', 'temp'),
			array('populate/missing', FALSE),
			array(array('error' => 0, 'name' => 'name', 'type' => 'text/plain', 'tmp_name' => '12345', 'size' => 10), 'upload'),
			array(array('error' => 0, 'name' => 'name', 'type' => 'text/plain', 'tmp_name' => '12345'), FALSE),
			array($test_local.'file'.DIRECTORY_SEPARATOR.'file1.txt', 'file'),
			array($test_local.'file', FALSE),
		);
	}

	/**
	 * @dataProvider data_source_type
	 */
	public function test_source_type($source, $expected_type)
	{
		$upload = new Upload_File('default', 'file');
		$this->assertEquals($expected_type, $upload->guess_source_type($source));
	}

	public function test_source()
	{
		$upload = new Upload_File('default', 'file');

		$this->assertNull($upload->source());
		$this->assertNull($upload->source_type());

		$upload->source('http://example.com/file.png');

		$this->assertEquals('http://example.com/file.png', $upload->source());
		$this->assertEquals('url', $upload->source_type());

		$upload->source('populate/test_upload_populate.txt');

		$this->assertEquals('populate/test_upload_populate.txt', $upload->source());
		$this->assertEquals('temp', $upload->source_type());
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
		$this->assertEquals('file', $upload->source_type());

		$upload->save_to_temp();

		$this->assertFileExists($upload->file());
		$this->assertEquals(0, strpos($upload->file(), $this->test_temp), 'The file should be in the temp folder');

		$upload->clear();

		$this->assertFileNotExists($upload->file());
	}
}