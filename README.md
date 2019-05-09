# Castlegate IT WP Twitter Redux #

A simple interface for adding Twitter feeds to WordPress sites. It includes a widget and shortcode as well as functions for retrieving feed data. Feed data is stored in the database and will be updated hourly using WP Cron, so this must be configured correctly for the plugin to work.

This plugin replaces the previous [Twitter Feed](https://github.com/castlegateit/cgit-wp-twitter) plugin.

## Constants ##

For the plugin to work, you must define the following constants, using the values for the Twitter App you registered for this site (you did register an app using your Twitter account, right?):

*   `CGIT_TWITTER_KEY`
*   `CGIT_TWITTER_SECRET`
*   `CGIT_TWITTER_TOKEN`
*   `CGIT_TWITTER_TOKEN_SECRET`

## Widget ##

The plugin provides a widget called "Twitter Feed" with settings for the widget title, Twitter screen name, and the number of tweets to display.

## Shortcode ##

You can also use the `[twitter_feed user="example" count="3"]` shortcode to add a Twitter feed anywhere within the content. The `user` attribute is required. If the `count` attribute is missing, 10 tweets will be shown.

## Class and functions ##

Feed data is handled by the `Cgit\Twitter\Feed` class. The constructor requires a Twitter screen name (username) as a parameter:

~~~ php
$feed = new \Cgit\Twitter\Feed('example');
$data = $feed->get(4); // get the latest 4 tweets as an array of PHP objects
$html = $feed->render(4); // get tweets as an HTML list
~~~

If you prefer functions, the plugin provides some for you:

~~~ php
$feed = cgit_twitter_feed($user, $count); // PHP objects
$html = cgit_twitter_feed_html($user, $count); // HTML list
~~~

These provide exactly the same output as the methods above. Using these methods or functions, you should be able to customize the appearance of the feed to suit your plugin or theme.

## Raw JSON ##

Using the `Feed` class you can access the raw JSON data from the Twitter API:

~~~ php
$feed = new \Cgit\Twitter\Feed('example');
$data = $feed->get(4, true);
$raw = json_decode($data->raw);
~~~

Without the second parameter, the `get()` method will not return the original JSON.

## TwitterOAuth library

This plugin includes [Abraham Williams's TwitterOAuth library](https://github.com/abraham/twitteroauth/) as a submodule, so you should clone this repository recursively:

    git clone --recursive git@github.com:castlegateit/cgit-wp-twitter-redux.git

Alternatively, if you have already cloned this repository, update the submodules:

    git submodule update --init

## License

Copyright (c) 2019 Castlegate IT. All rights reserved.

This program is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License along with this program. If not, see <https://www.gnu.org/licenses/>.
