## Jam Events

In the lifetime of each model(and builder) there are several events being triggered.

### Available events

* model.before_construct
* model.after_construct
* model.before_load
* model.after_load
* model.before_create
* model.before_update
* model.before_save
* model.after_create
* model.after_update
* model.after_save
* model.before_check
* model.after_check
* model.before_delete
* model.after_delete

* meta.before_finalize
* meta.after_finalize

* builder.before_select

`before_construct` and `after_construct` are called when a new Jam_Model object is constructed.

On model save, `before_create` and `after_create` are called for newly created models, `before_update` and `after_update` are triggered for loaded model that have already been previously saved to the database, and `before_save` and `after_save` are called in both cases.

```php
<?php
$new_client = Jam::build('client');
$new_client->save();                                       // before_create, before_save, after_create after_save events triggered

$old_client = Jam::find('client', 1);
$old_client->save();                                       // before_update, before_save, after_update after_save events triggered
?>
```
`before_check` and `after_check` are triggered when `check()` is called

```php
<?php
$old_client = Jam::find('client', 1);
$old_client->check();                                      // before_check, after_check events triggered
?>
```
`before_delete` and `after_delete` are triggered when `delete()` is called

```php
<?php
$old_client = Jam::find('client', 1);
$old_client->delete();                                     // before_delete, after_delete events triggered
?>
```
`before_finalize` and `after_finalize` are triggered when `initialize()` method is called that creates the Jam_Meta. This is executed only once for each model.

`before_select` is triggered on every builder select performed

### Binding events

To tap into that functionality you'll have to bind events to your custom functions that perform one task or another. This is usually done in the initialize phase of the model

```php
<?php
class Model_Order extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		// ...
		$meta->events()->bind('model.after_save', 'Model_Order::after_save');
	}
}
?>
```

## Jam behaviors

Jam comes with some behaviors already built for you - this is functionality that can be cherry picked and added to your models as you need it. Existing behaviors are:

* nested
* paranoid
* sluggable
* sortable
* uploadable

### Nested Behavior

Specify this behavior if you want to model a tree structure by providing a parent association and a children association. This behavior requires that you have a foreign key column, which by default is called parent_id.

Example :


```php
<?php
class Model_Order extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		// ...
		$meta->behaviors(array(
			'nested' => Jam::behavior('nested', array('field' => 'parent_id'))
		));
	}
}
?>
```

<pre>
root
 \_ child1
      \_ subchild1
      \_ subchild2
</pre>

Create the nested tree:

```php
<?php
$root      = Jam::find('order', 'root');
$child1    = Jam::find('child1', 'child1');
$subchild1 = Jam::find('subchild1', 'subchild1');

// Get all root elements
Jam::all('order')->root(); // Jam_Arr_Association: Model_Order(1)

// Check if element is root
$root->is_root();                                          // TRUE

// Get parent element
$root->parent->loaded();                                   // FALSE
$child->parent->name;                                      // 'root'
$subchild->parent->name;                                   // 'child1'

// Get children elements
$root->children;                                           // Jam_Arr_Association: Model_Order(1)

// Get the root element
$subchild->root()->name;                                   // 'root'
$child->root()->name;                                      // 'root'

// Get a chain of parents
$subchild->parents();                                      // Jam_Arr_Association: Model_Order(2)
?>
```

### Paranoid Behavior

When you want to keep deleted object inside the database and when you call `delete()` only to mark a flag as is_deleted to TRUE and then exclude those objects from the general selects, you can use `paranoid` behavior. This behavior requires that you have a boolean column, which by default is called is_deleted.

```php
<?php
class Model_Order extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		// ...
		$meta->behaviors(array(
			'paranoid' => Jam::behavior('paranoid', array('field' => 'is_deleted'))
		));
	}
}

$order = Jam::find('order', 1);
echo Jam::all('order')->count_all();                     // 5

$order->delete();
echo Jam::all('order')->count_all();                     // 4

// Get the count of all the objects (deleted or no)
echo Jam::all('order')->deleted('all')->count_all();     // 5

// Get the count of deleted objects
echo Jam::all('order')->deleted('deleted')->count_all(); // 1


// Get deleted object
$order = Jam::all('order')->where_key(1)->deleted('deleted')->first();
$order->restore_delete();

echo Jam::all('order')->count_all();                     // 5

// Permanently delete an object
$order->real_delete();
echo Jam::all('order')->count_all();                     // 4
echo Jam::all('order')->deleted('all')->count_all();     // 4
?>
```

## Sluggable Behavior

The sluggable behavior is used for the use case where you want to have a human readable urls, that are automatically generated from your titles. You add a special field, called "slug" that gets automatically populated with the title, stripped down of all the undesirable characters that will not show well in the URL, and letters get converted to ASCII whenever possible. Additionally the ID of the object gets added, so you can then search by ID and handle the case of changing URLS.

```php
<?php
class Model_Order extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		// ...
		$meta->field('title', Jam::field('name'));
		$meta->name_key('title');
		$meta->behaviors(array(
			'sluggable' => Jam::behavior('sluggable')
		));
	}
}

$order->title = 'new title';

// Get the right slug before the model is saved
echo $order->build_slug();                                 // 'new-title-1';

$order->save();
echo $order->slug;                                         // 'new-title-1';

Jam::all('order')->where_slug('new-title-1')->first();
Jam::all('order')->find_by_slug('new-title-1');
Jam::all('order')->find_by_slug_insist('new-title-1');
?>
```

The last 3 rows need a more deeper explanation. If the id (1) at the end of the string matches an order id, and the whole string matches the slug in the database, then we'll return the order with that slug / id. However if the id does match, but the slug is different, then a special exception Jam_Exception_Slugmismatch will be thrown, containing the slug and the object that's been found, but that has different slug. This will allow you to implement auto redirecting URLs that go to the correct address even if the user has changed the title / slug for the link.

If the ID does not match, `find_by_slug_insist()` will throw the normal Jam_Exception_Notfound.

This behavior has some options:

* uses_primary_key
* unique
* pattern
* slug

#### uses_primary_key, default TRUE

If you set `uses_primary_key` to FALSE then it will not add the id at the end of the string and the special functionality for throwing `Jam_Exception_Slugmismatch` exceptions will not be available. Only the name key will be used for the slug. In addition the slug would be generated earlier during the `model.before_check` event.

#### unique, default TRUE

By default the slug field, added to the model will have a `unique` option set to TRUE, you can control this option directly from the behavior option `unique`

#### pattern

If you want to really do a custom slug, you can manually set the regex that matches the slug in where_slug through the `pattern` option

#### slug

If you want a custom slug, based on different fields of the model you can use the `slug` option - this has to be a callable function that will be given the model as the first argument and will construct the source for the string. After that the string goes through transliteration, lowercase conversion and trimming so don't worry about this stuff in this function

```php
<?php
class Model_Order extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->behaviors(array(
			'sluggable' => Jam::behavior('sluggable', array('uses_primary_key' => FALSE, 'slug' => 'Model_Test_Tag::_slug'))
		));

		// Set fields
		$meta->fields(array(
			'id'              => Jam::field('primary'),
			'name'            => Jam::field('string'),
			'date'            => Jam::field('timestamp'),
		));
	}

	public static function _slug(Jam_Model $model)
	{
		return $model->name().'-'.date('Y-m', $model->date);
	}
}
?>
```

### Sortable Behavior

When you want to have a user-ordered list of items you can use the `sortbale` behavior. You will need a position field in your database (configurable with the `field` option). And any new objects will be added at the end of the list.

```php
<?php
class Model_Order extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		// ...

		$meta->behaviors(array(
			'sortable' => Jam::behavior('sortable', array('field' => 'position'))
		));
	}
}
// Get all the orders, ordered by position
$orders = Jam::all('order');

// Add element to the end of the list (position is last inserted ID)
$orders = Jam::update('order')->set('name', 'new order')->execute();
?>
```

### Uploadable Behavior

This is discussed in depth in [Uploads](/OpenBuildings/Jam/blob/master/guide/jam/uploads.md)
