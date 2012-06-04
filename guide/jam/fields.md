**Table of Contents**  *generated with [DocToc](http://doctoc.herokuapp.com/)*

- [Jam Fields](#jam-fields)
	- [Meta::fields, Meta::field](#meta::fields-meta::field)
	- [Global properties](#global-properties)
	- [Validation properties](#validation-properties)
	- [Field Types](#field-types)
			- [Jam::field('boolean')](#jam::field'boolean')
			- [Jam::field('enum')](#jam::field'enum')
			- [Jam::field('float')](#jam::field'float')
			- [Jam::field('integer')](#jam::field'integer')
			- [Jam::field('primary')](#jam::field'primary')
			- [Jam::field('string')](#jam::field'string')
			- [Jam::field('text')](#jam::field'text')
			- [Jam::field('timestamp')](#jam::field'timestamp')
			- [Jam::field('email')](#jam::field'email')
			- [Jam::field('expression')](#jam::field'expression')
			- [Jam::field('file')](#jam::field'file')
			- [Jam::field('image')](#jam::field'image')
			- [Jam::field('upload'), Jam::field('upload_image')](#jam::field'upload'-jam::field'upload_image')
			- [Jam::field('password')](#jam::field'password')
			- [Jam::field('serialized')](#jam::field'serialized')
			- [Jam::field('slug')](#jam::field'slug')
			- [Jam::field('weblink')](#jam::field'weblink')
	- [Custom fields](#custom-fields)

# Jam Fields

Jam comes with many common field types defined as objects with suitable logic for retrieving and formatting them for the database.

Take this model for example:

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Model_Post extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->fields(array(
			'id'              => Jam::field('primary'),
			'title'           => Jam::field('string', array(
				'unique' => TRUE,
				'rules' => array(
					array('not_empty')
				),
			)),
			'content'         => Jam::field('text'),
		));
	}
}
?>
```

## Meta::fields, Meta::field

In order for the Jam model to map your database fields accurately you'll need to define each field in your model yourself. This is done in hte static "initialize" method of the model, and is executed only once for each model class. You do this with one of the two methods - `field()` or `fields()` - `fields()` is just a convenience method to assign multipule fields with an array. 

Here's how all of this looks in practice:

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Model_Post extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->fields(array(
			'id'              => Jam::field('primary'),
			'title'           => Jam::field('string', array(
				'unique' => TRUE,
				'rules' => array(
					array('not_empty')
				),
			)),
		));

		$meta->field('content', Jam::field('text'));
	}
}
?>
```

Have in mind that you cannot modify the fields of the model after it has been created, and if you try to do so using the Jam_Meta object, an exception will be thrown. 

Each field allows you to pass an array to its constructor to easily configure it. All parameters are optional.

## Global properties

The following properties apply to nearly all fields.

`in_db` — Whether or not the field represents an actual column in the table.

`default` — A default value for the field.

`allow_null` — Whether or not `NULL` values can be set on the field. This defaults to `TRUE` for most fields, except for the string-based fields, in which case it defaults to `FALSE`.

 * If this is `FALSE`, most fields will convert the `NULL` to the field's `default` value. 
 * If this is `TRUE` the field's `default` value will be changed to `NULL` (unless you set the default value yourself).

`convert_empty` — If set to `TRUE` any `empty()` values passed to the field will be converted to whatever is set for `empty_value`. This also sets `allow_null` to `TRUE` if `empty_value` is `NULL`.

`empty_value` — This is the value that `empty()` values are converted to if `convert_empty` is `TRUE`. The default for this is `NULL`.

`column` — The name of the database column to use for this field. If this isn't given, the field name will be used.

## Validation properties

The following properties are available to all of the field types and mostly relate to validation. There is a more in-depth discussion of these properties on [Validations](/OpenBuildings/Jam/blob/master/guide/jam/validations.md).

`unique` — A shortcut property for validating that the field's data is unique in the database.

`label` — The label to use for the field when validating.

`filters` — Filters to apply to data before validating it.

`rules` — Rules to use to validate the data with.


## Field Types

Here are all the Jam Fields available out of the box. 

#### Jam::field('boolean')

Represents a boolean. In the database, it is usually represented by a `tinyint`.

 * `true` — What to save `TRUE` as in the database. This defaults to 1, but you may want to have `TRUE` values saved as 'Yes', or 'TRUE'.
 * `false` - What to save `FALSE` as in the database.

> __Be careful__ An exception will be thrown if you try to set `convert_empty` to `TRUE` on this field.

#### Jam::field('enum')

Represents an enumerated list. Keep in mind that this field accepts any value passed to it, and it is not until you `validate()` the model that you will know whether or not the value is valid or not.

If you `allow_null` on this field, `NULL` will be added to the choices array if it isn't currently in it. Similarly, if `NULL` is in the choices array `allow_null` will be set to `TRUE`.

 * `choices` — An array of valid choices.

Example:

```php
<?php
	'field' => Jam::field('enum', array(
		'choices' => array('big', 'small', 'medium'),
	))
?>
```

#### Jam::field('float')

Represents an integer. `NULL` values are allowed by default on integer fields.

 * `places` — Set to an integer to automatically round the value to the proper number of places.

#### Jam::field('integer')

Represents an integer. `NULL` values are allowed by default on integer fields.

#### Jam::field('primary')

Represents a primary key. Each model can only have one primary key.

#### Jam::field('string')

Represents a string of any length. `NULL` values are not allowed by default on this field and are simply converted to an empty string.

#### Jam::field('text')

Currently, this field behaves exactly the same as Jam::field('String').

#### Jam::field('timestamp')

Represents a timestamp. This field always returns its value as a UNIX timestamp, however you can choose to save it as any type of value you'd like by setting the `format` property.

 * `format` — By default, this field is saved as a UNIX timestamp, however you can set this to any valid `date()` format and it will be converted to that format when saving.
 * `auto_now_create` — If TRUE, the value will save `now()` whenever INSERTing.
 * `auto_now_update` — If TRUE, the field will save `now()` whenever UPDATEing.

The timestamp can be used to store date / datetime columns in the database

```php
<?php
	'field' => Jam::field('timestamp', array(
		'format' => "Y-m-d h:i:s",
	))
?>
``` 

#### Jam::field('email')

Represents an email. This automatically sets a validation rule that verifies it is a valid email address.

#### Jam::field('expression')

This field is a rather abstract type that allows you to pull a database expression back on SELECTs. Simply set your `column` to any `DB::expr()`.

For example, if you always wanted the field to return a concatenation of two columns in the database, you can do this:

```php
<?php
	'field' => Jam::field('expression', array(
		'column' => DB::expr("CONCAT(`first_name`, ' ', `last_name`)"),
	))
?>
```

It is possible to cast the returned value using a Jam field.
This should be defined in the `cast` property:

```php
<?php
	'field' => Jam::field('expression', array(
		'cast'   => 'integer', // This will cast the field using Jam::field('integer')
		'column' => DB::expr("CONCAT(`first_name`, ' ', `last_name`)"),
	))
?>
```

> __Be careful__ Keep in mind that aliasing breaks down in Database_Expressions.

#### Jam::field('file')

Represents a file upload. Pass a valid file upload to this and it will be saved automatically in the location you specify.

In the database, the filename is saved, which you can use in your application logic.

To make this field required use the `Upload::not_empty` rule instead of the simple `not_empty`.

You must be careful not to pass `NULL` or some other value to this field if you do not want the current filename to be overwritten.

 * `path` — This must point to a valid, writable directory to save the file to.
 * `delete_file` — If this value is `TRUE` file is automatically deleted upon deletion. The default is `FALSE`.
 * `delete_old_file` — Whether or not to delete the old file when a new one is successfully uploaded. Defaults to `FALSE`.
 * `types` — Valid file extensions that the file may have.

#### Jam::field('image')

Represents an image upload. This behaves almost exactly the same as Jam::field('File') except it allows to transform the original image and create unlimited number of different thumbnails.

To make this field required use the `Upload::not_empty` rule instead of the simple `not_empty`.

Here is an example where you resize the original image and create a thumbnail.

```php
<?php 
Jam::field('image', array(
	// where to save the original image
	'path'			  => 'upload/images/',
	// transformations for the original image, refer to the Image module on available methods
	'transformations' => array(
		'resize' => array(1600, 1600, Image::AUTO),  // width, height, master dimension
	),
	// desired quality of the saved image, default 100
	'quality'		  => 75,
	// define your thumbnails here, if saving the thumbnail to the original's directory don't forget to set a prefix
	'thumbnails'      => array (
		// 1st thumbnail
		array(
			// where to save the thumbnail
			'path'   => 'upload/images/thumbs/',
			// prefix for the thumbnail filename
			'prefix' => 'thumb_',
			// transformations for the thumbnail, refer to the Image module on available methods
			'transformations' => array(
				'resize' => array(500, 500, Image::AUTO),  // width, height, master dimension
				'crop'   => array(100, 100, NULL, NULL),   // width, height, offset_x, offset_y
			),
			// desired quality of the saved thumbnail, default 100
			'quality' => 50,
		),
		// 2nd thumbnail
		array(
			// ...
		),
	)
));
?>
```

> __Be careful__ The transformation steps will be performed in the order you specify them in the array.

 * `path` — This must point to a valid, writable directory to save the original image to.
 * `transformations` — Transformations for the image, refer to the Image module on available methods.
 * `quality` — desired quality of the saved image between 0 and 100, defaults to 100.
 * `driver` — image driver to use, default is `NULL` which will result in using [Image::$default_driver](../api/Image#property:default_driver)
 * `delete_old_file` — Whether or not to delete the old files when a new image is successfully uploaded. Defaults to `FALSE`.
 * `delete_file` — If this value is `TRUE` image and thumbnails are automatically deleted upon deletion. The default is `FALSE`.
 * `types` — Valid file extensions that the file may have. Defaults to allowing JPEGs, GIFs, and PNGs.


#### Jam::field('upload'), Jam::field('upload_image')

Those are the same as as file / image but have a more sophisticated functionality - Non-local upload locations (FTP, Rackspace), automatically save dimensions in the database. Can survive a failed validation even on object that have not been saved in the database. 

You can read more about it in [Uploads](/OpenBuildings/Jam/blob/master/guide/jam/upload.md) section.

#### Jam::field('password')

Represents an password. This automatically sets a validation callback that hashes the password after it's validated. That password is hashed only when it has changed.

 * `hash_with` — A valid PHP callback to use for hashing the password. Defaults to `sha1`.

#### Jam::field('serialized')

Represents any serialized data. Any serialized data in the database is unserialized before it's retrieved. Likewise, any data set on the field is serialized before it's saved.

#### Jam::field('slug')

Represents a slug, commonly used in URLs. Any value passed to this will be converted to a lowercase string, will have spaces, dashes, and underscores converted to dashes, and will be stripped of any non-alphanumeric characters (other than dashes).

Jam has another way of defining slugs that handles more use cases but as it requires more functionality it is implemented with the Sluggable Behavior.

#### Jam::field('weblink')

Represents an HTTP URI. It extends Jam_Field_String, but add a rule for `Valid::url` and automatically adds `http://` before the URI if it is missing.


## Custom fields

Any custom field behavior can be added by defining your own field objects that
extend from `Jam_Field` or one of it's derivatives.
