<?php
/**
 * Abstract Class for manupulating a server
 *
 * @package    Jam
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
abstract class Kohana_Upload_Server
{
	/**
	 * @var  string  default instance name
	 */
	public static $default = 'default';

	/**
	 * @var  array  Database instances
	 */
	public static $instances = array();

	/**
	 * Get a singleton Upload_Server instance. If configuration is not specified,
	 * it will be loaded from the Upload_Server configuration file using the same
	 * group as the name.
	 *
	 *     // Load the default server
	 *     $server = Upload_Server::instance();
	 *
	 *     // Create a custom configured instance
	 *     $server = Upload_Server::instance('custom', $config);
	 *
	 * @param   string   instance name
	 * @param   array    configuration parameters
	 * @return  Database
	 */
	public static function instance($name = NULL, array $config = NULL)
	{
		if ($name === NULL)
		{
			// Use the default instance name
			$name = Upload_Server::$default;
		}

		if ( ! isset(Upload_Server::$instances[$name]))
		{
			if ($config === NULL)
			{
				// Load the configuration for this database
				$config = Arr::get(Kohana::$config->load('jam.upload.servers'), $name);
			}

			$validation = Validation::factory($config)
				->rule('type', 'not_empty')
				->rule('params', 'not_empty')
				->rule('params', 'is_array');

			if ( ! $validation->check())
				throw new Kohana_Exception('Upload server config had errors: :errors',
					array(':errors' => join(', ', $validation->errors('upload_server'))));

			$server = 'server_'.$validation['type'];
			Upload_Server::$instances[$name] = Upload_Server::$server($validation['params']);
		}

		return Upload_Server::$instances[$name];
	}

	public static function server_local(array $params = array())
	{
		$validation = Validation::factory($params)
			->rule('path', 'not_empty')
			->rule('path', 'is_dir')
			->rule('web', 'not_empty')
			->rule('url_type', 'in_array', array(':value', array(Flex\Storage\Server::URL_HTTP, Flex\Storage\Server::URL_SSL, Flex\Storage\Server::URL_STREAMING)));

		if ( ! $validation->check())
			throw new Kohana_Exception('Upload server local params had errors: :errors',
				array(':errors' => join(', ', Arr::flatten($validation->errors()))));

		$server = new Flex\Storage\Server_Local($validation['path'], $validation['web']);

		if (isset($validation['url_type']))
		{
			$server->url_type($validation['url_type']);
		}

		return $server;
	}

	public static function server_rackspace(array $params = array())
	{
		$validation = Validation::factory($params)
			->rule('username', 'not_empty')
			->rule('api_key', 'not_empty')
			->rule('container', 'not_empty')
			->rule('url_type', 'in_array', array(':value', array(Flex\Storage\Server::URL_HTTP, Flex\Storage\Server::URL_SSL, Flex\Storage\Server::URL_STREAMING)))
			->rule('cdn_uri', 'url')
			->rule('cdn_ssl', 'url')
			->rule('cdn_streaming', 'url');

		if ( ! $validation->check())
			throw new Kohana_Exception('Upload server local params had errors: :errors',
				array(':errors' => join(', ', $validation->errors('upload_server'))));

		$server = new Flex\Storage\Server_Rackspace($validation['container'], $validation['region'], array(
			'username' => $validation['username'],
			'apiKey' => $validation['api_key'],
		));

		foreach (array('cdn_uri', 'cdn_ssl', 'cdn_streaming', 'url_type') as $param)
		{
			if (isset($validation[$param]))
			{
				$server->$param($validation[$param]);
			}
		}

		return $server;
	}

	public static function server_local_fallback(array $params = array())
	{
		$validation = Validation::factory($params)
			->rule('path', 'not_empty')
			->rule('fallback', 'not_empty')
			->rule('path', 'is_dir')
			->rule('url_type', 'in_array', array(':value', array(Flex\Storage\Server::URL_HTTP, Flex\Storage\Server::URL_SSL, Flex\Storage\Server::URL_STREAMING)))
			->rule('web', 'not_empty');

		if ( ! $validation->check())
			throw new Kohana_Exception('Upload server local params had errors: :errors',
				array(':errors' => join(', ', $validation->errors('upload_server'))));

		$server = new Flex\Storage\Server_Local_Fallback($validation['path'], $validation['web']);
		$server->fallback(Upload_Server::instance($validation['fallback']));

		if (isset($validation['url_type']))
		{
			$server->url_type($validation['url_type']);
		}

		return $server;
	}

	public static function server_local_placeholdit(array $params = array())
	{
		$validation = Validation::factory($params)
			->rule('path', 'not_empty')
			->rule('path', 'is_dir')
			->rule('placeholder', 'url')
			->rule('url_type', 'in_array', array(':value', array(Flex\Storage\Server::URL_HTTP, Flex\Storage\Server::URL_SSL, Flex\Storage\Server::URL_STREAMING)))
			->rule('web', 'not_empty');

		if ( ! $validation->check())
			throw new Kohana_Exception('Upload server local params had errors: :errors',
				array(':errors' => join(', ', $validation->errors('upload_server'))));

		$server = new Flex\Storage\Server_Local_Placeholdit($validation['path'], $validation['web']);

		if (isset($validation['placeholder'])) {
			$server->placeholder($validation['placeholder']);
		}

		if (isset($validation['url_type']))
		{
			$server->url_type($validation['url_type']);
		}

		return $server;
	}
}
