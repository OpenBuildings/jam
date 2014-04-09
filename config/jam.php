<?php defined('SYSPATH') OR die('No direct script access.');

return array(
	'default_form' => 'general',
	'upload' => array(
		'temp' => array(
			'path' => DOCROOT.'upload'.DIRECTORY_SEPARATOR.'temp'.DIRECTORY_SEPARATOR,
			'web' => '/upload/temp'
		),
		'image_driver' => 'GD',
		'servers' => array(
			'local' => array(
				'type' => 'local',
				'params' => array(
					'path' => DOCROOT.'upload',
					'web' => '/upload',
				)
			)
		)
	)
);
