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

	public function data_pick_filename()
	{
		return array(
			array('/var/logo.gif', NULL, 'logo.gif'),
			array('/var/logo.png', NULL, 'logo.png'),
			array('/var/logo.tiff', 'http://example.com/logo.png', 'logo.png'),
			array('/var/logo.tiff', 'http://example.com/logo.jpg?test=1', 'logo.jpg'),
			array('/var/logo.tiff', 'http://example.com/file?test=logo.gif', 'logo.gif'),
			array('/var/logo.tiff', 'http://example.com/file?test=logo.png&post=get', 'logo.png'),
			array('/var/logo.tiff', 'http://example.com/file.php?test=logo.png&post=get', 'logo.png'),
			array('/var/logo.tiff', 'http://example.com/file', 'logo.tiff'),
			array('/var/logo.gif', 'http://example.com/file.gif', 'file.gif'),
		);
	}

	/**
	 * @dataProvider data_pick_filename
	 */
	public function test_pick_filename($file, $url, $filename)
	{
		$this->assertEquals($filename, Upload_File::pick_filename($file, $url));
	}

	public function data_normalize_extension()
	{
		return array(
			// FROM FILE
			array('name/logo.gif', NULL, NULL, 'logo.gif'),
			array('name/logo_gif', NULL, NULL, 'logo-gif.gif'),
			array('name/logo.png', NULL, NULL, 'logo.png'),
			array('name/logo_png', NULL, NULL, 'logo-png.png'),
			array('name/logo.jpg', NULL, NULL, 'logo.jpg'),
			array('name/logo_jpg', NULL, NULL, 'logo-jpg.jpg'),
			array('name/test.txt', NULL, NULL, 'test.txt'),
			array('name/test_txt', NULL, NULL, 'test-txt.txt'),

			// FROM MIME
			array('logo', 'image/gif', NULL, 'logo.gif'),
			array('logo', 'image/png', NULL, 'logo.png'),
			array('logo', 'image/jpeg', NULL, 'logo.jpg'),
			array('logo', 'image/jpg', NULL, 'logo.jpg'),
			array('logo', 'text/plain', NULL, 'logo.txt'),
			array('logo', 'image/gif', 'http://example.com/logo.php?query=param', 'logo.gif'),

			// FROM URL
			array(NULL, NULL, 'http://example.com/logo.gif', 'logo.gif'),
			array(NULL, NULL, 'http://example.com/logo.png', 'logo.png'),
			array(NULL, NULL, 'http://example.com/logo.jpg?query=param', 'logo.jpg'),
			array(NULL, NULL, 'http://example.com/logo?query=test&post=1', 'logo.jpg'),
			array(NULL, NULL, 'http://example.com/logo?file=logo.json', 'logo.json'),
			array(NULL, NULL, 'http://example.com/logo.php?file=logo.gif', 'logo.gif'),
			array(NULL, NULL, 'http://example.com/test', 'test.jpg'),
		);
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

	/**
	 * @dataProvider data_normalize_extension
	 */
	public function test_normalize_extension($file, $mime, $url, $expected_filename)
	{
		$file = $file ? $this->test_local.$file : NULL;
		$filename = Upload_File::normalize_Extension($file, $mime, $url);

		$this->assertEquals($expected_filename, $filename);
	}

	public function test_width_height_aspect()
	{
		$upload = new Upload_File('default', 'file');

		$this->assertNull($upload->aspect());
		$upload->set_size(100, 200);

		$this->assertEquals(100, $upload->width());
		$this->assertEquals(200, $upload->height());
		$this->assertEquals(array(100, 200), $upload->aspect()->dims());
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

		$upload->cleanup();

		$this->assertFileNotExists($upload->file());
	}

}