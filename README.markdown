CodeIgniter-WP
================

Fetch data from one or many WordPress installations. This is a very early version and quite rough. It only supports the methods below. More to come.


Requirements
------------

1. PHP 5.3+
2. CodeIgniter 2.0.0 - 2.0.3


Usage
-----

First - open up sparks/config/wp.php and tell the spark where you have your WordPress install root (ie where the wp-config.php is located on the server).
Note that you need both file and mysql access to the WordPress installations.
Next in your controller:

	$this->load->spark('ciwp/0.1.0');
	print_r($this->wp->get_post('blog', 1);
	print_r($this->wp->get_installations());
	print_r($this->wp->wp_get_recent_posts('blog', array()));
	print_r($this->wp->get_post_meta('blog', array()));
	print_r($this->wp->get_comments('blog',array()));
	print_r($this->wp->get_children('blog',array()));
	print_r($this->wp->wp_insert_post('blog',array()));
	print_r($this->wp->wp_insert_comment('blog','array()));
	print_r($this->wp->get_users('blog','array('include' => 1, 'exlude' => 3, 'number' => 5)));

Methods in this library will take the same arguments as the WordPress function in the Codex. 

Wordpress Codex: http://codex.wordpress.org/
