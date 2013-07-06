<?php defined('SYSPATH') OR die('No direct script access.');

return array(
	'type' => 'Must provide :type, e.g. rackspace, local or local_fallback',
	'params' => array(
		'not_empty' => 'Must provide :field as array for the server configuration',
		'is_array' => ':field must be an array of parameters',
	),
	'url_type' => array(
		'in_array' => ':field can only be HTTP, SSL or STREAMING',
	),
	'path' => array(
		'not_empty' => 'Must provide :field: local file path to the directory',
		'is_file' => ':field must be a valid directory'
	),
	'web'           => 'Must provide :field: prefix for base url to access the files e.g. upload/files/',
	'username'      => 'Must provide rackspace :field',
	'api_key'       => 'Must provide rackspace :field',
	'container'     => 'Must provide rackspace :field',
	'cdn_uri'       => 'Must provide rackspace :field',
	'cdn_ssl'       => 'Must provide rackspace :field',
	'cdn_streaming' => 'Must provide rackspace :field',
	'fallback'      => 'Must provide :field server',
);