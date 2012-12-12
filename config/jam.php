<?php defined('SYSPATH') OR die('No direct script access.');

return array(
	'default_form' => 'general',
	'image_generator' => array(
		'type' => IMAGETYPE_JPEG,
		'file' => ':model-:group/:id_:filename_:image.jpg',
		'path_dir' => DOCROOT.'upload/image-generator/',
		'web_dir' => '/upload/image-generator/',
	),
	'upload' => array(
		'temp' => array(
			'path' => DOCROOT.'upload'.DIRECTORY_SEPARATOR.'temp'.DIRECTORY_SEPARATOR, 
			'web' => 'upload/temp/'
		),
		'image_driver' => 'gd',
		'servers' => array(
			'local' => array(
				'type' => 'local',
				'params' => array(
					'path' => DOCROOT.'upload',
					'web' => 'upload',
				)
			)
		)
	)
);