# Getting Started

## Defining the ORM Models

### Prerequisite Database Table

Creating the database table. To work with the ORM we need a table to actually relate to. You can create a table by a lot of means, the easiest is by downloading [OpenBuildings/timestamped-migrations](https://github.com/OpenBuildings/timestamped-migrations) module and the associated [Kohana-Minion](https://github.com/kohana/minion/tree/k3.2-v1.0).

From there you write

	./minion db:generate --name=create_table_posts

Modifiy the resulting migration file to look like this

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Create_Table_Images extends Migration
{
	public function up()
	{
		$this->create_table('posts', array(
			'name' => 'string',
			'title' => 'string',
			'content' => 'text',
			'created_at' => 'timestamp'
		));
	}

	public function down()
	{
		$this->drop_table('posts');
	}
}
?>
```

Then run the migration with

	./minion db:migrate


### The Model File

Add a file classes/model/posts.php with this content

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Model_Post extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->fields(array(
			'id'              => Jam::field('primary'),
			'name'            => Jam::field('string'),
			'title'           => Jam::field('string'),
			'content'         => Jam::field('text'),
			'created_at'      => Jam::field('timestamp', array(
				'auto_now_create' => TRUE
			)),
		));
	}
}
?>
```

This isn't much of a model but it tells jam about how to store your data in the relating table that we've created earlier. Jam gives you a lot of functionality for free - basic database CRUD (Create, Read, Update, Destroy) operations, data validation, as well as sophisticated search support and the ability to relate multiple models to one another.


### Adding Some Validation

Jam also has a lot of functionality to help you with keeping the data in your database valid. Modify your classes/model/posts.php file accordingly

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Model_Post extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->fields(array(
			'id'              => Jam::field('primary'),
			'name'            => Jam::field('string'),
			'title'           => Jam::field('string'),
			'content'         => Jam::field('text'),
			'created_at'      => Jam::field('timestamp', array(
				'auto_now_create' => TRUE
			)),
		));

		$meta
			->valodator('name', 'title', array('present' => TRUE))
			->validator('title', array('length' => array('minimum' => 5)));
	}
}
?>
```

These changes will ensure that all posts have a name and a title, and that the title is at least five characters long. Jam can validate a variety of conditions in a model, including the presence or uniqueness of columns, their format, and the existence of associated objects. Validations are covered in detail in [Validators](/OpenBuildings/Jam/blob/master/guide/jam/validators.md)


## Controllers and views

To actually use this module in your Kohana application you will probably need a controller action and a corresponding view with the HTML Form in it. Let's start with the controller.


### The Controller

We will place it in classes/controller/posts.php We're using the [Controller_Template](http://kohanaframework.org/3.2/guide/kohana/mvc/controllers) so that we can use the layout functionality for free.

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Controller_Posts extends Controller_Template {

	public function action_new()
	{
		$post = Jam::build('post');

		if ($this->request->method() == Request::POST)
		{
			if ($post->set($this->request->post())->check())
			{
				$post->save();
				$this->redirect_to('posts/show/'.$post->id());
			}
		}
		$this->template->content = View::factory('posts/new', array('post' => $post));
	}
}
?>
```

This is all straight forward - Jam::build loads an empty element of the post model, if the request is a post, we set all the contents of the POST to the model (an associative array) and the check if it's valid. If it is, we save (create) the post and redirect to its view to show it up. Notice the $post->id() method - this method is designed to give you the primary key of the model, however it is defined. There is also a $post->name() method - giving you the text representation of the model.


### The View

The view iteslf is in views/posts/new.php and looks like this:

```php
<?php Form::open() ?>
	<div>
		<label for="name">
			Name:
			<?php Form::input('name', $post->name); ?>
			<?php if ( ! $post->check())echo join(', ', $post->errors('name')) ?>
		</label>
	</div>
	<div>
		<label for="title">
			Name:
			<?php Form::input('title', $post->title); ?>
			<?php if ( ! $post->check())echo join(', ', $post->errors('title')) ?>
		</label>
	</div>
	<div>
		<label for="content">
			Name:
			<?php Form::textarea('content', $post->content); ?>
			<?php if ( ! $post->check())echo join(', ', $post->errors('content')) ?>
		</label>
	</div>
<?php Form::close() ?>
```

With this form in your view you will be able to create new rows in the database. If the data entered does not pass the validation, defined in the model, then the form will be redisplayed with the errors filled in accordingly.


### Showing the post

To be able to display the posts individually you will need the following code in your controller

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Controller_Posts extends Controller_Template {

	public function action_show()
	{
		$post = Jam::find('post', $this->request->param('id'));

		$this->template->content = View::factory('posts/show', array('post' => $post));
	}
}
?>
```

We use the factory method of Jam again, but this time we pass the 'id' from the route as the second argument, and then Jam will find the row with the proper ID.

Having the post object we can easily create a view like this to display it:

```php
<h1><?php echo HTML::chars($post->title) ?></h1>

<span>Name: <?php echo HTML::chars($post->name) ?></span>

<span>Created <?php echo Date::fuzzy_span($post->created_at) ?> ago</span>

<div><?php echo Text::auto_p(HTML::chars($post->content)) ?></div>

<div><?php echo HTML::anchor('posts/delete/'.$post->id(), 'delete') ?></div>
```

Some things to note here - notice that we wrap every user input field with HTML::chars - this insures that the user will not do anything naughty, like entering html tags or javascript for hist title. Additionally we wrap the post content with Text::auto_p - giving it a nice formatting out of the box.


### Deleting the post

Finally we can make the delete link usable by adding this to the controller:

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Controller_Posts extends Controller_Template {

	public function action_delete()
	{
		$post = Jam::find('post', $this->request->param('id'));
		$post->delete();

		$this->redirect('posts/new');
	}
}
?>
```

Which will remove the database row from the table and redirect you back to creating one.


## Adding a second model

Now that you've seen what a model looks like, it's time to add a second model to the application. The second model will handle comments on blog posts.


### The database migration

	./minion db:generate --name=create_table_comments


With this content:

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Create_Table_Comments extends Migration
{
	public function up()
	{
		$this->create_table('posts', array(
			'commenter' => 'string',
			'body' => 'text',
			'post_id' => 'integer',
			'created_at' => 'timestamp',
		));
	}

	public function down()
	{
		$this->drop_table('posts');
	}
}
?>
```

And we run the migration with:

	./minion db:migrate


### Associating of the models

Jam associations let you easily declare the relationship between two models. In the case of comments and posts, you could write out the relationships this way:

* Each comment belongs to one post.
* One post can have many comments.

To turn this into code your comment model should look like that (inside classes/model/comment.php):

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Model_Comment extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->associations(array(
			'post' => Jam::association('belongsto')
		));

		$meta->fields(array(
			'id'                => Jam::field('primary'),
			'commenter'         => Jam::field('string'),
			'body'              => Jam::field('text'),
			'created_at'        => Jam::field('timestamp', array(
				'auto_now_create' => TRUE
			)),
		));
	}
}
?>
```

You'll need to edit the post.php file to add the other side of the association:

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Model_Post extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->associations(array(
			'comments' => Jam::association('hasmany')
		));

		$meta->fields(array(
			'id'              => Jam::field('primary'),
			'name'            => Jam::field('string'),
			'title'           => Jam::field('string'),
			'content'         => Jam::field('text'),
			'created_at'      => Jam::field('timestamp', array(
				'auto_now_create' => TRUE
			)),
		));

		$meta
			->valodator('name', 'title', array('present' => TRUE))
			->validator('title', array('length' => array('minimum' => 5)));


	}
}
?>
```

### Showing the Association

Suppose we want to show all comments of the post in the post page itself, along with a form to add a new comment. We modify the controller to handle this functionality with this code

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Controller_Posts extends Controller_Template {

	public function action_show()
	{
		$post = Jam::find('post', $this->request->param('id'));

		$message = '';

		if ($this->request->method() == Request::POST)
		{
			// Set the post of the comment directly.
			// This relationship will be saved on comment save
			$new_comment
				->set($this->request->post())
				->set('post', $post);

			if ($new_comment->check())
			{
				// We reset the comment after save so we can add another comment
				$new_comment->save()->reset();

				$message = 'Comment created!'
			}
		}
		$this->template->content = View::factory('posts/show', array('post' => $post, 'message' => $message));
	}
}
?>
```

The view to handle all this functionality will look like this:

```php
<h1><?php echo HTML::chars($post->title) ?></h1>

<span>Name: <?php echo HTML::chars($post->name) ?></span>

<span>Created <?php echo Date::fuzzy_span($post->created_at) ?> ago</span>

<div><?php echo Text::auto_p(HTML::chars($post->content)) ?></div>

<div><?php echo HTML::anchor('posts/delete/'.$post->id(), 'delete') ?></div>

<h2>Comments</h2>

<ul>
	<?php foreach ($post->comments as $comment): ?>
		<li>
			<strong><?php echo HTML::chars($comment->creator) ?></strong> said:
			<?php echo HTML::chars($comment->body) ?>
			<span><?php echo Date::fuzzy_span($comment->created_at) ?> ago</span>
		</li>
	<?php endforeach ?>

</ul>
<?php Form::open() ?>
	<label for="name">
		Name:
		<?php Form::input('creator'); ?>
	</label>
	<label for="name">
		Name:
		<?php Form::textarea('body'); ?>
	</label>
<?php Form::close() ?>
```

------

Hopefully that will give you an idea how to use Jam in your projects. Of course it's immensely more powerful but to learn it's full potential you'll have to read the other parts of this guide.
