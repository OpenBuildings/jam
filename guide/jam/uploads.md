# Jam Upload Fields

In order to use Jam::field('upload'), you first have to configure where to save the information. This is done by configuring "servers" where to store the information, but a server can also be "local" so to store the files locally. After you configure those servers, all of the `upload` fields will use them will use them.

The default configuration file:

```php
<?php defined('SYSPATH') OR die('No direct script access.');

return array(
	'jam' => array(
		'upload' => array(
			'temp' => array(
				'path' => DOCROOT.'upload'.DIRECTORY_SEPARATOR.'temp'.DIRECTORY_SEPARATOR,
				'web' => 'upload/temp/'
			),
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
	)
);
?>
```

It is important to configure the temp folder location to be publicly available as it will be used to store files that have not yet been validated, allowing you to show them to the user after the validation has failed or even use ajax upload schemes to populate it before actually submitting the form.

There are three servers at the moment, but the interface for them is simple enough so other backends can be implemented quickly.

Whatever you choose those files will first be moved to the temp folder and only then be moved to whatever server you've configured. This intermidiate step is useful as you now can safely not validate a form submission and the user will not be forced to reupload the file, also you can immideatley display thumbnails from the temp folder.


### Upload_Server_Local

This is the general local file system store should be used in most cases (the default server)

__Params__:

- __path__ : the actual file path to the root directory
- __web__ : the publicly visible directory must correspond to the __path__ parameter on the server

### Upload_Server_Ftp

Allows uploading files to an FTP server. If you must also make it publicly visible for general http requests. This Server has not been tested and is a work in progress

__Params__:

- __host__ : the host name to used connect
- __user__ : if the ftp server requires password use this paramter
- __password__ : if the ftp server requires password use this paramter
- __path__ : the actual file path to the root directory defaults to '/'
- __web__ : the publicly visible directory must correspond to the __path__ parameter on the server

### Upload_Server_Rackspace

Use rackspace's Cloudfiles API to upload files to the cloud.

__Params__:

- __server__ : the server to which to connect, defaults to the US server
- __user__ : the useename, used for authentication
- __key__ : the API key
- __container__ : the cloudfiles container
- __cdn__ : the cdn used to publicly access the files

### Jam_Field_Upload

__Options__:

- __server__ : the server where the files will end up, required.
- __path__ : the default path is ":model/:id" directory inside your server path. :column, :model, :name, :id and :{custom} are used. Where custom can be any column from the model.
- __types__ : an array of file types allowed to be uploaded, defaults to allow all
- __delete_file__ : if set to false, keeps all the uplaoded files
- __transformations__ : an array of transformations by the Kohana Image class
- __driver__ : the driver for the Image class
- __thumbnails__ : an array of options (transformations and quality allowed), each of which is applied to generate the thumbnail. The array key is used as a folder where to put the thumbnail

### Jam_Behavior_Uploadable

This allows you to specify the 'save_size' => TRUE so that it automatically saves the width / height of the image prior to uploading it to the server.

Example:

	$meta->behaviors(array(
	 	'cover_image' => Jam::behavior('uploadable', array(
	 		'save_size' => true,
	 		'server' => 'local',
			'path' => ':model/:model:id',
            'thumbnails' => array(
			    'small' => array(
			      'transformations' => array(
			        'resize' => array(100, 100)
			      )
			    ),
			    'different' => array(
			      'transformations' => array(
			        'resize' => array(200, 200)
			      )
			    )
			)
		))
		......
	));

## Forms

If the value of the field is "" then the file gets deleted as well. You can easily create a control like this

	<input type="file" id="cover_image" name="cover_image">
	<input type="checkbox" id="cover_image_delete" name="cover_image" value="">

Which will delete the image when the checkbox is saved

If istead of a file field a string is passed with {directory}/{filename} that exists inside the temp directory, it will be used and moved to the server if validation passes. That way you can populate the temporary directory with whatever method you chose (e.g. ajax upload) and then this file will be used. The temporary folder will be deleted.

Example:

	<input type="file" id="cover_image" name="cover_image">
	<input type="hidden" name="cover_image" value="23u402394u0/penetrometer-86.png">
	<input type="checkbox" id="cover_image_delete" name="cover_image" value="">

	<script type="text/javascript">

	$('input[type="file"]').change(function(e){
		if($(this).val())
		{
			$(this).prev().attr("disabled", 'disabled');
		}
		else
		{
			$(this).prev().removeAttr("disabled");
		}
	});
	</script>

