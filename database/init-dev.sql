-- Sample Development Data for Guestbook and Blog
-- Web 1.0 Portfolio Site

USE guestbook;

-- Clear existing data in development
TRUNCATE TABLE entries;

-- ============================================
-- Blog Sample Data
-- ============================================

-- Clear blog tables (order matters due to foreign keys)
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE blog_post_categories;
TRUNCATE TABLE blog_posts;
TRUNCATE TABLE blog_categories;
TRUNCATE TABLE blog_images;
TRUNCATE TABLE admin_users;
SET FOREIGN_KEY_CHECKS = 1;

-- Admin user (password: devpassword)
-- Generated with: password_hash('devpassword', PASSWORD_DEFAULT)
INSERT INTO admin_users (username, password_hash) VALUES
('admin', '$2y$12$idKw.yzSCc9LBGS4rfaN5OGrjQbSSwRcR4cQ/xvba7HBcdhQd7tIi');

-- Blog Categories
INSERT INTO blog_categories (slug, name, description) VALUES
('tech', 'Technology', 'Posts about programming, Linux, and tech projects'),
('personal', 'Personal', 'Life updates and personal thoughts'),
('projects', 'Projects', 'Project showcases and development updates'),
('homelabbing', 'Homelabbing', 'Home server and self-hosting adventures');

-- Sample Blog Posts
INSERT INTO blog_posts (slug, title, content_markdown, content_html, excerpt, status, published_at) VALUES
('hello-world', 'Hello World!',
'# Welcome to my blog!

This is my first blog post. I finally got around to adding a blog to my Web 1.0 portfolio site.

## What to expect

I will be writing about:

- **Technology** - Programming, Linux, and open source
- **Projects** - Things I am building
- **Personal** - Life updates and random thoughts

Stay tuned for more posts!',
'<h1>Welcome to my blog!</h1>
<p>This is my first blog post. I finally got around to adding a blog to my Web 1.0 portfolio site.</p>
<h2>What to expect</h2>
<p>I will be writing about:</p>
<ul>
<li><strong>Technology</strong> - Programming, Linux, and open source</li>
<li><strong>Projects</strong> - Things I am building</li>
<li><strong>Personal</strong> - Life updates and random thoughts</li>
</ul>
<p>Stay tuned for more posts!</p>',
'This is my first blog post. I finally got around to adding a blog to my Web 1.0 portfolio site.',
'published', NOW()),

('why-web-1-0', 'Why I Built a Web 1.0 Site',
'# Why Web 1.0?

In an era of JavaScript frameworks and single-page applications, why would anyone build a website using tables and `<font>` tags?

## Nostalgia

I grew up in the early 2000s browsing GeoCities sites with tiled backgrounds and MIDI music. There was something magical about those handcrafted pages.

## Simplicity

No build steps. No npm install. Just PHP and HTML. It loads fast and works everywhere.

## Fun

Sometimes you just want to have fun with your personal site. Not everything needs to be optimized for SEO or follow best practices.',
'<h1>Why Web 1.0?</h1>
<p>In an era of JavaScript frameworks and single-page applications, why would anyone build a website using tables and <code>&lt;font&gt;</code> tags?</p>
<h2>Nostalgia</h2>
<p>I grew up in the early 2000s browsing GeoCities sites with tiled backgrounds and MIDI music. There was something magical about those handcrafted pages.</p>
<h2>Simplicity</h2>
<p>No build steps. No npm install. Just PHP and HTML. It loads fast and works everywhere.</p>
<h2>Fun</h2>
<p>Sometimes you just want to have fun with your personal site. Not everything needs to be optimized for SEO or follow best practices.</p>',
'In an era of JavaScript frameworks and single-page applications, why would anyone build a website using tables and font tags?',
'published', DATE_SUB(NOW(), INTERVAL 3 DAY)),

('draft-post', 'Work in Progress',
'# This is a draft

I am still working on this post. It should not be visible to the public.',
'<h1>This is a draft</h1>
<p>I am still working on this post. It should not be visible to the public.</p>',
'This is a draft post for testing.',
'draft', NULL);

-- Link posts to categories
INSERT INTO blog_post_categories (post_id, category_id) VALUES
(1, 1), -- hello-world -> tech
(1, 2), -- hello-world -> personal
(2, 1), -- why-web-1-0 -> tech
(2, 2), -- why-web-1-0 -> personal
(3, 1); -- draft-post -> tech
