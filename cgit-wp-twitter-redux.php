<?php

/*

Plugin Name: Castlegate IT WP Twitter Redux
Plugin URI: http://github.com/castlegateit/cgit-wp-twitter-redux
Description: Simple, flexible Twitter feed plugin.
Version: 1.2
Author: Castlegate IT
Author URI: http://www.castlegateit.co.uk/
License: MIT

*/

use Cgit\Twitter\Plugin;

// Constants
define('CGIT_TWITTER_PLUGIN_FILE', __FILE__);

// Load plugin
require_once __DIR__ . '/twitteroauth/autoload.php';
require_once __DIR__ . '/src/autoload.php';
require_once __DIR__ . '/functions.php';

// Initialization
Plugin::getInstance();
