DROP TABLE IF EXISTS `test_elements`;

CREATE TABLE `test_elements` (
`id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`name` VARCHAR( 255 ) NOT NULL ,
`url` VARCHAR( 255 ) NOT NULL ,
`email` VARCHAR( 255 ) NOT NULL ,
`description` VARCHAR( 255 ) NOT NULL ,
`amount` INT( 11 ) DEFAULT NULL,
`test_author_id` INT( 11 ) DEFAULT NULL
) ENGINE = INNODB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `test_blogs`;

CREATE TABLE `test_blogs` (
`id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`name` VARCHAR( 255 ) NOT NULL ,
`url` VARCHAR( 255 ) NOT NULL ,
`test_owner_id` INT( 11 ) DEFAULT NULL,
`test_posts_count` INT( 11 ) DEFAULT NULL
) ENGINE = INNODB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `test_authors`;

CREATE TABLE `test_authors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT '',
  `test_position_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `test_copyrights`;

CREATE TABLE `test_copyrights` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `test_image_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `test_tags`;

CREATE TABLE `test_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `test_post_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `test_blogs_test_tags`;

CREATE TABLE `test_blogs_test_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `test_tag_id` int(11) DEFAULT NULL,
  `test_blog_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `test_images`;

CREATE TABLE `test_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file` varchar(255) NOT NULL,
  `test_holder_id` int(11) DEFAULT NULL,
  `test_holder_model` varchar(255) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `test_videos`;

CREATE TABLE `test_videos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file` varchar(255) NOT NULL,
  `test_holder_id` int(11) DEFAULT NULL,
  `test_holder_type` varchar(255) NULL,
  `deleted` int(11) DEFAULT 0 NOT NULL,
  `position` int(11) DEFAULT 0 NOT NULL,
  `slug` varchar(255) NULL,
  `group` varchar(255) NULL,
  `token` varchar(255) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `test_positions`;

CREATE TABLE `test_positions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `model` varchar(32) NOT NULL,
  `size` varchar(32) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `test_categories`;

CREATE TABLE `test_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL,
  `test_author_id` int(11) DEFAULT NULL,
  `test_blog_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `test_posts`;

CREATE TABLE `test_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `status` enum('published','draft','review') NOT NULL,
  `created` int(11) DEFAULT NULL,
  `updated` int(11) DEFAULT NULL,
  `published` int(11) DEFAULT NULL,
  `test_author_id` int(11) DEFAULT NULL,
  `test_blog_id` int(11) DEFAULT NULL,
  `_approved_by` int(11) DEFAULT NULL,
  `test_tags_count` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `test_categories_test_posts`;

CREATE TABLE `test_categories_test_posts` (
  `test_category_id` int(11) DEFAULT NULL,
  `test_post_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `test_uploads`;

CREATE TABLE `test_uploads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file` varchar(255) NOT NULL,
  `file2` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

