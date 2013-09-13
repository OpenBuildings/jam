
DROP TABLE IF EXISTS test_blogs;

CREATE TABLE test_blogs (
  id INTEGER PRIMARY KEY,
  name TEXT NOT NULL,
  url TEXT NOT NULL,
  email TEXT NOT NULL,
  description TEXT NOT NULL,
  amount INTEGER NOT NULL,
  test_author_id INTEGER NOT NULL,
);

DROP TABLE IF EXISTS test_blogs;

CREATE TABLE test_blogs (
  id INTEGER PRIMARY KEY,
  name TEXT NOT NULL,
  url TEXT NOT NULL,
  test_owner_id INTEGER NOT NULL,
  test_posts_count INTEGER NOT NULL
);

DROP TABLE IF EXISTS test_authors;

CREATE TABLE test_authors (
  id INTEGER PRIMARY KEY,
  name TEXT NOT NULL,
  email TEXT NOT NULL,
  password TEXT DEFAULT '',
  test_position_id INTEGER NOT NULL
);

DROP TABLE IF EXISTS test_positions;

CREATE TABLE test_positions (
  id INTEGER PRIMARY KEY,
  name TEXT NOT NULL
);

DROP TABLE IF EXISTS test_categories;

CREATE TABLE test_categories (
  id INTEGER PRIMARY KEY,
  name TEXT NOT NULL,
  parent_id INTEGER NOT NULL,
  test_author_id INTEGER NOT NULL,
  test_blog_id INTEGER NOT NULL
);

DROP TABLE IF EXISTS test_posts;

CREATE TABLE test_posts (
  id INTEGER PRIMARY KEY,
  name TEXT NULL,
  slug TEXT NULL,
  status TEXT NULL,
  created INTEGER NULL,
  updated INTEGER NULL,
  published INTEGER NULL,
  test_author_id INTEGER NULL,
  test_blog_id INTEGER NULL,
  approved_by INTEGER NULL
);

DROP TABLE IF EXISTS test_categories_test_posts;

CREATE TABLE test_categories_test_posts (
  test_category_id INTEGER NOT NULL,
  test_post_id INTEGER NOT NULL
);

DROP TABLE IF EXISTS test_uploads;

CREATE TABLE test_uploads (
  id INTEGER PRIMARY KEY,
  file TEXT NULL,
  file2 TEXT NULL,
);

