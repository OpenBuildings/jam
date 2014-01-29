<?php

Jam::insert('test_element')
	->columns(array('id', 'name', 'url', 'email', 'description', 'amount', 'test_author_id'))
	->values(
		array(1, 'Part 1', 'http://parts.wordpress.com/', 'staff@example.com', 'Big Part', 20, '1'),
		array(2, 'Part 2', 'http://parts.wordpress.com/', 'staff@example.com', 'Small Part', 10, '1')
	)
	->execute();

/**
 * BLOGS
 */

$blog1 = Jam::create('test_blog', array(
	'id' => 1,
	'name' => 'Flowers blog',
	'url' => 'http://flowers.wordpress.com/',
	'test_posts_count' => '1',
));

$blog2 = Jam::create('test_blog', array(
	'id' => 2,
	'name' => 'Awesome programming',
	'url' => 'http://programming-blog.com',
	'test_posts_count' => '0',
));

$blog3 = Jam::create('test_blog', array(
	'id' => 3,
	'name' => 'Tabless',
	'url' => 'http://bobby-tables-ftw.com',
	'test_posts_count' => '1',
));

/**
 * Positions
 */

$position1 = Jam::create('test_position', array(
	'id' => 1,
	'name' => 'Staff',
));

$position2 = Jam::create('test_position', array(
	'id' => 2,
	'name' => 'Freelancer',
));

$position3 = Jam::create('test_position_big', array(
	'id' => 3,
	'name' => 'Freelancer',
	'size' => 'Huge',
));

/**
 * Authors
 */

$author1 = Jam::create('test_author', array(
	'id' => 1,
	'name' => 'Jonathan Geiger',
	'email' => 'jonathan@jonathan-geiger.com',
	'test_position' => $position1,
	'test_blogs_owned' => array($blog1, $blog3),
));

$author2 = Jam::create('test_author', array(
	'id' => 2,
	'name' => 'Paul Banks',
	'email' => 'paul@banks.com',
));

$author3 = Jam::create('test_author', array(
	'id' => 3,
	'name' => 'Bobby Tables',
	'email' => 'bobby@sql-injection.com',
	'test_position' => $position2,
	'test_blogs_owned' => array($blog2),
));


/**
 * CATEGORIES
 */
Jam::create('test_category', array(
	'id' => 1,
	'name' => 'Category One',
	'test_author' => $author1,
	'test_blog' => $blog1,
	'children' => array(
		array(
			'id' => 3,
			'name' => 'Category Three',
			'test_author' => $author1,
			'test_blog' => $blog2,
			'children' => array(
				array(
					'id' => 5,
					'name' => 'Category Five',
				)
			)
		)
	)
));

Jam::create('test_category', array(
	'id' => 2,
	'name' => 'Category Two',
	'is_featured' => TRUE,
	'test_author' => $author1,
	'test_blog' => $blog1,
));

Jam::create('test_category', array(
	'id' => 4,
	'name' => 'Category Four',
	'is_featured' => TRUE,
	'test_author' => $author1,
	'test_blog' => $blog3,
));



/**
 * POSTS
 */
$post1 = Jam::create('test_post', array(
	'id' => 1,
	'name' => 'First Post',
	'slug' => 'first-post',
	'status' => 'draft',
	'created' => 1264985737,
	'updated' => 1264985737,
	'published' => 1264985737,
	'test_author' => $author1,
	'test_blog' => $blog1,
	'test_categories' => array(1,2,3),
));

$post2 = Jam::create('test_post', array(
 'id' => 2,
 'name' => 'Second Post',
 'slug' => 'first-post',
 'status' => 'review',
 'created' => 1264985737,
 'updated' => 1264985740,
 'published' => 1264985737,
 'test_author' => $author1,
 'test_blog' => $blog3,
 'approved_by' => $author1,
 'test_categories' => array(2),
));

$post3 = Jam::create('test_post', array(
 'id' => 3,
 'name' => 'Third Post',
 'slug' => 'third-post',
 'status' => 'draft',
 'created' => 1264985737,
 'updated' => 1264985740,
 'published' => 1264985737,
));

/**
 * Tags
 */
Jam::create('test_tag', array(
	'id' => 1,
	'name' => 'red',
	'slug' => 'red',
	'test_post' => $post1,
	'test_blogs' => array($blog1),
));

Jam::create('test_tag', array(
	'id' => 2,
	'name' => 'green',
	'slug' => 'green',
	'test_post' => $post1,
	'test_blogs' => array($blog1),
));

Jam::create('test_tag', array(
	'id' => 3,
	'name' => 'orange',
	'slug' => 'orange',
	'test_post' => $post2,
	'test_blogs' => array($blog2),
));

Jam::create('test_tag', array(
	'id' => 4,
	'name' => '--black',
	'slug' => 'black',
	'test_post' => $post1,
	'test_blogs' => array($blog2),
));

Jam::create('test_tag', array(
	'id' => 5,
	'name' => '* List 1',
	'slug' => 'list-1',
	'test_post' => $post1,
	'test_blogs' => array($blog3),
));

Jam::create('test_tag', array(
	'id' => 6,
	'name' => '* List 2',
	'slug' => 'list-2',
	'test_post' => $post1,
	'test_blogs' => array($blog3),
));


/**
 * VIDEOS
 */
Jam::create('test_video', array(
	'id' => 1,
	'file' => 'video.jpg',
	'test_holder' => $post1,
	'position' => 1,
	'slug' => 'video-jpg-1',
	'group' => 'one',
));

Jam::create('test_video', array(
	'id' => 2,
	'file' => 'video2.jpg',
	'slug' => 'video2-jpg-2',
	'position' => 2,
	'group' => 'one',
));

Jam::create('test_video', array(
	'id' => 3,
	'file' => 'video3.jpg',
	'test_holder' => $post1,
	'deleted' => 1,
	'position' => 3,
	'slug' => 'video3-jpg-3',
	'group' => 'two',
));

Jam::create('test_video', array(
	'id' => 4,
	'file' => 'video4.jpg',
	'test_holder' => $post1,
	'position' => 3,
	'slug' => 'video4-jpg-4',
	'group' => 'two',
));

Jam::create('test_video', array(
	'id' => 5,
	'file' => 'video5.jpg',
	'test_holder' => $post1,
	'position' => 3,
	'slug' => 'video5-jpg-4',
	'group' => 'one',
));

/**
 * Images
 */
$image1 = Jam::create('test_image', array(
	'id' => 1,
	'file' => 'file.jpg',
	'test_holder' => $post1,
));

$image2 = Jam::create('test_image', array(
	'id' => 2,
	'file' => 'file2.jpg',
	'test_holder' => $author1,
));

$image3 = Jam::create('test_image', array(
	'id' => 3,
	'file' => 'file3.jpg',
));

/**
 * Copyrights
 */
Jam::create('test_copyright', array(
	'id' => 1,
	'name' => 'My Copyright',
	'test_image' => $image1,
));
