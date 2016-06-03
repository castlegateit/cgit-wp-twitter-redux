<?php

/**
 * Get Twitter feed in PHP format
 *
 * @param string $user
 * @param int $count
 * @return string
 */
function cgit_twitter_feed($user, $count) {
    return (new \Cgit\Twitter\Feed($user))->get($count);
}

/**
 * Get Twitter feed in HTML format
 *
 * @param string $user
 * @param int $count
 * @return string
 */
function cgit_twitter_feed_html($user, $count) {
    return (new \Cgit\Twitter\Feed($user))->render($count);
}
