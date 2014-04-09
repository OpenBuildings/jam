<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * This class is what the upload field accually returns
 * and has all the nesessary info and manipulation abilities to save / delete / validate itself
 *
 * @package    Jam
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
*/
class Kohana_Upload_Util {

	public static function download($url, $directory, $filename = NULL)
	{
		$url = str_replace(' ', '%20', $url);

		if ( ! Valid::url($url))
			return FALSE;

		$curl = curl_init($url);
		$file = Upload_Util::combine($directory, uniqid());

		$handle = fopen($file, 'w');
		$headers = new HTTP_Header();

		curl_setopt($curl, CURLOPT_FILE, $handle);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($curl, CURLOPT_HEADERFUNCTION, array($headers, 'parse_header_string'));

		if (curl_exec($curl) === FALSE OR curl_getinfo($curl, CURLINFO_HTTP_CODE) !== 200)
		{
			fclose($handle);
			unlink($file);

			throw new Kohana_Exception('Curl: Download Error: :error, status :status on url :url', array(':url' => $url, ':status' => curl_getinfo($curl, CURLINFO_HTTP_CODE), ':error' => curl_error($curl)));
		}

		fclose($handle);

		if ($filename === NULL)
		{
			if ( ! isset($headers['content-disposition'])
			 OR ! ($filename = Upload_Util::filename_from_content_disposition($headers['content-disposition'])))
			{
				$mime_type = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
				$url = urldecode(curl_getinfo($curl, CURLINFO_EFFECTIVE_URL));

				$filename = Upload_Util::filename_from_url($url, $mime_type);
			}
		}

		$filename = substr(pathinfo($filename, PATHINFO_FILENAME), 0, 60).'.'.pathinfo($filename, PATHINFO_EXTENSION);

		$result_file = Upload_Util::combine($directory, $filename);

		rename($file, $result_file);

		return is_file($result_file) ? $filename : FALSE;
	}

	/**
	 * Move the contents of the stream to a specified directory with a given name
	 *
	 * @param  string $stream
	 * @param  string $directory
	 * @param  string $filename
	 */
	public static function stream_copy_to_file($stream, $file)
	{
		$stream_handle = fopen($stream, "r");
		$result_handle = fopen($file, 'w');

		$transfered_bytes = stream_copy_to_stream($stream_handle,  $result_handle);

		if ( (int) $transfered_bytes <= 0)
			throw new Kohana_Exception('No data was transfered from :stream to :file ', array(':stream' => $stream, ':file' => Debug::path($file)));

		fclose($stream_handle);
		fclose($result_handle);
	}

	/**
	 * recursively delete directory
	 *
	 * @param  string  $directory
	 * @return boolean
	 */
	public static function rmdir($directory)
	{
		if ( ! is_dir($directory))
			return FALSE;

		$files = array_diff(scandir($directory), array('.', '..'));

		foreach ($files as $file)
		{
			$current = $directory.DIRECTORY_SEPARATOR.$file;

			if (is_dir($current))
			{
				Upload_Util::rmdir($current);
			}
			else
			{
				unlink($current);
			}
		}
		return rmdir($directory);
	}

	/**
	 * Method to make a filename safe for writing on the filesystem, removing all strange characters
	 * @param  string $filename
	 * @return string
	 */
	static public function sanitize($filename, $separator = '-')
	{
		// Transliterate strange chars
		$filename = UTF8::transliterate_to_ascii($filename);

		// Sanitize the filename
		$filename = preg_replace('/[^a-z0-9-\.]/', $separator, strtolower($filename));

		// Remove spaces
		$filename = preg_replace('/\s+/u', $separator, $filename);

		// Strip multiple dashes
		$filename = preg_replace('/-{2,}/', $separator, $filename);

		return $filename;
	}

	/**
	 * Check if a file looks like a filename ("file.ext")
	 * @param  string  $filename
	 * @return boolean
	 */
	public static function is_filename($filename)
	{
		return (bool) pathinfo($filename, PATHINFO_EXTENSION);
	}

	/**
	 * Return possible filenames from a given url.
	 * Filenames can be in the query or the url of the file itself
	 *
	 * @param  string $url
	 * @return array
	 */
	public static function filenames_candidates_from_url($url)
	{
		$query = parse_url($url, PHP_URL_QUERY);
		parse_str($query, $query);

		$filename_candidates = array_values( (array) $query);

		$url_filename = basename(parse_url($url, PHP_URL_PATH));

		$filename_candidates[] = $url_filename;

		return $filename_candidates;
	}

	/**
	 * Create a filename path from function arguments with / based on the operating system
	 * @code
	 * $filename = file::combine('usr','local','bin'); // will be "user/local/bin"
	 * @endcode
	 * @return string
	 * @author Ivan Kerin
	 */
	public static function combine()
	{
		$args = func_get_args();

		foreach ($args as $i => & $arg)
		{
			$arg = $i == 0 ? rtrim($arg, DIRECTORY_SEPARATOR) : trim($arg, DIRECTORY_SEPARATOR);
		}

		return join(DIRECTORY_SEPARATOR, array_filter($args));
	}

	/**
	 * Detirmine the filename from the url
	 * @param  string $url
	 * @param  string $mime_type
	 * @return string
	 */
	public static function filename_from_url($url, $mime_type = NULL)
	{
		$filename_candidates = Upload_Util::filenames_candidates_from_url($url);
		$filename_candidates = array_filter($filename_candidates, 'Upload_Util::is_filename');
		$file = count($filename_candidates) ? reset($filename_candidates) : uniqid();
		$extensions = File::exts_by_mime($mime_type);

		$extension_candiates = array(
			(is_array($extensions) ? end($extensions) : $extensions),
			pathinfo($file, PATHINFO_EXTENSION),
			'jpg',
		);
		$extension_candiates = array_filter($extension_candiates);
		$extension = reset($extension_candiates);

		return Upload_Util::sanitize(pathinfo($file, PATHINFO_FILENAME)).'.'.$extension;
	}

	public static function filename_from_content_disposition($content_disposition)
	{
		if (preg_match('/filename="?(.*?)"?$/', $content_disposition, $matches))
			return $matches[1];

		return NULL;
	}

	/**
	 * Perform transformations on an image and store it at a different location (or overwrite existing)
	 *
	 * @param  string $from
	 * @param  string $to
	 * @param  array  $transformations
	 */
	public static function transform_image($from, $to, array $transformations = array())
	{
		$image = Image::factory($from, Kohana::$config->load('jam.upload.image_driver'));

		// Process tranformations
		foreach ($transformations as $transformation => $params)
		{
			if ( ! in_array($transformation, array('factory', 'save', 'render')))
			{
				// Call the method excluding the factory, save and render methods
				call_user_func_array(array($image, $transformation), $params);
			}
		}

		if ( ! file_exists(dirname($to)))
		{
			mkdir(dirname($to), 0777, TRUE);
		}

		$image->save($to, 95);
	}
}
