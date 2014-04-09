<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Validator functionality.
 *
 * @package Jam
 * @group   jam.upload
 * @group   jam.upload.util
 */
class Jam_Upload_UtilTest extends Testcase_Validate_Upload {

	public function test_stream_copy_to_file()
	{
		$from = $this->test_local.'file_stream.txt';
		$to = $this->test_local.'file_stream_copy.txt';
		file_put_contents($from, 'test');

		Upload_Util::stream_copy_to_file($from, $to);

		$this->assertFileExists($to);
		$this->assertFileEquals($from, $to);
		unlink($from);
		unlink($to);
	}

	public function data_download()
	{
		return array(
			array('http://721914653bc38b6913f5-e8d9ed0d9bf85920b3a97a5af61edabd.r41.cf3.rackcdn.com/logo.jpg', NULL, 'logo.jpg'),
			array('http://721914653bc38b6913f5-e8d9ed0d9bf85920b3a97a5af61edabd.r41.cf3.rackcdn.com/logo_png?file=filename.jpg', NULL, 'filename.jpg'),
			array('http://721914653bc38b6913f5-e8d9ed0d9bf85920b3a97a5af61edabd.r41.cf3.rackcdn.com/test_txt', NULL, 'downloaded.txt'),
			array('http://721914653bc38b6913f5-e8d9ed0d9bf85920b3a97a5af61edabd.r41.cf3.rackcdn.com/test_txt_not_exists', NULL, NULL),
		);
	}

	/**
	 * @dataProvider data_download
	 */
	public function test_download($url, $filename, $expected_filename)
	{
		if ($expected_filename == NULL)
		{
			$this->setExpectedException('Kohana_Exception');
		}
		Upload_Util::download($url, $this->test_local, $filename);
		$this->assertFileExists($this->test_local.$expected_filename);
		unlink($this->test_local.$expected_filename);
	}

	public function data_sanitize()
	{
		return array(
			array('filename.jpg', 'filename.jpg'),
			array('file 1 name.jpg', 'file-1-name.jpg'),
			array('filênàme.jpg', 'filename.jpg'),
			array('file- -1.jpg', 'file-1.jpg'),
		);
	}

	/**
	 * @dataProvider data_sanitize
	 */
	public function test_sanitize($filename, $sanitized_filename)
	{
		$this->assertEquals($sanitized_filename, Upload_Util::sanitize($filename));
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
		$this->assertEquals($filename_candidates, Upload_Util::filenames_candidates_from_url($url));
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
		$filename = Upload_Util::filename_from_url($url, $mime);

		$this->assertRegExp($expected_regexp, $filename);
	}

	public function data_filename_from_content_disposition()
	{
		return array(
			array('attachement; filename="logo.gif"', 'logo.gif'),
			array('attachement;filename="logo.gif"', 'logo.gif'),
			array('attachement;filename="logo"', 'logo'),
			array('inline; token; filename="logo.gif"', 'logo.gif'),
			array('inline; token="some token"; filename="logo.gif"', 'logo.gif'),
			array('attachement', NULL),
			array('attachement; filename', NULL),
			array('inline;token="filename";filename="logo"', 'logo'),
		);
	}

	/**
	 * @dataProvider data_filename_from_content_disposition
	 */
	public function test_filename_from_content_disposition($content_disposition, $expected_filename)
	{
		$filename = Upload_Util::filename_from_content_disposition($content_disposition);

		$this->assertSame($expected_filename, $filename);
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
		$this->assertEquals($combined_filename, call_user_func_array('Upload_Util::combine', $filename_parts));
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
		$this->assertEquals($is_filename, Upload_Util::is_filename($filename));
	}

}
