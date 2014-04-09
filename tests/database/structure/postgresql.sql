DROP TABLE IF EXISTS test_elements;

CREATE TABLE test_elements (
  id serial,
  "name" varchar( 255 ) NOT NULL,
  url varchar( 255 ) NOT NULL,
  email varchar( 255 ) NOT NULL,
  description varchar( 255 ) NOT NULL,
  amount bigint NOT NULL,
  test_author_id bigint NOT NULL,
);

DROP TABLE IF EXISTS test_blogs;

CREATE TABLE test_blogs (
  id serial,
  "name" varchar( 255 ) NOT NULL,
  url varchar( 255 ) NOT NULL,
  test_owner_id bigint NOT NULL,
  test_posts_count bigint NOT NULL
);

DROP TABLE IF EXISTS test_authors;

CREATE TABLE test_authors (
  id serial,
  "name" varchar(255) NOT NULL,
  email varchar(255) NOT NULL,
  password varchar(255) DEFAULT '',
  test_position_id bigint NOT NULL,
  PRIMARY KEY (id)
);

DROP TABLE IF EXISTS test_positions;

CREATE TABLE test_positions (
  id serial,
  "name" varchar(255) NOT NULL,
  PRIMARY KEY (id)
);

DROP TABLE IF EXISTS test_categories;

CREATE TABLE test_categories (
  id serial,
  "name" varchar(255) NOT NULL,
  parent_id bigint NOT NULL,
  test_author_id bigint NOT NULL,
  test_blog_id bigint NOT NULL,
  PRIMARY KEY (id)
);

DROP TABLE IF EXISTS test_posts;

CREATE TABLE test_posts (
  id serial,
  "name" varchar(255) NULL,
  slug varchar(255) NULL,
  status varchar(255) NULL,
  created bigint DEFAULT NULL,
  updated bigint DEFAULT NULL,
  published bigint DEFAULT NULL,
  test_author_id bigint DEFAULT NULL,
  test_blog_id bigint DEFAULT NULL,
  approved_by bigint NULL,
  PRIMARY KEY (id),
  CHECK (status IN ('draft', 'review', 'published'))
);

DROP TABLE IF EXISTS test_categories_test_posts;

CREATE TABLE test_categories_test_posts (
  test_category_id bigint NOT NULL,
  test_post_id bigint NOT NULL
);

DROP TABLE IF EXISTS test_uploads;

CREATE TABLE test_uploads (
  id serial,
  "file" varchar(255) NOT NULL,
  "file2" varchar(255) NOT NULL,
  PRIMARY KEY (id)
);
