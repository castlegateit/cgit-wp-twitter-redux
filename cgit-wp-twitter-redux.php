<?php

/*

Plugin Name: Castlegate IT WP Twitter Redux
Plugin URI: http://github.com/castlegateit/cgit-wp-twitter-redux
Description: Simple, flexible Twitter feed plugin.
Version: 1.5.1
Author: Castlegate IT
Author URI: http://www.castlegateit.co.uk/
License: MIT

*/

if (!defined('ABSPATH')) {
    wp_die('Access denied');
}

define('CGIT_TWITTER_REDUX_PLUGIN', __FILE__);

require_once __DIR__ . '/lib/twitteroauth/autoload.php';
require_once __DIR__ . '/classes/autoload.php';
require_once __DIR__ . '/functions.php';

$plugin = new \Cgit\Twitter\Plugin();

do_action('cgit_twitter_redux_loaded');
