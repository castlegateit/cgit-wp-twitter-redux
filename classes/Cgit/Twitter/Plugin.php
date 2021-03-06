<?php

namespace Cgit\Twitter;

class Plugin
{
    /**
     * Constructor
     *
     * Register functions to run on plugin activation and create the feed update
     * action. Register shortcodes and widgets.
     *
     * @return void
     */
    public function __construct()
    {
        // Make everything work
        register_activation_hook(CGIT_TWITTER_REDUX_PLUGIN, [$this, 'activate']);
        add_action('cgit_twitter_update', [$this, 'update']);
        add_action('widgets_init', [$this, 'widget']);
        add_shortcode('twitter_feed', [$this, 'shortcode']);
    }

    /**
     * Activate plugin
     *
     * @return void
     */
    public function activate()
    {
        $this->createDatabaseTables();
        $this->scheduleFeedUpdate();
    }

    /**
     * Perform feed update for all users
     *
     * @return void
     */
    public function update()
    {
        global $wpdb;

        $users = $wpdb->get_col('SELECT screen_name FROM ' . $wpdb->base_prefix
            . 'cgit_twitter_users');

        foreach ($users as $user) {
            $feed = new Feed($user);
            $feed->update();
        }

        // Limit database cache size
        $this->cleanDatabaseCache();
    }

    /**
     * Create database tables
     *
     * This should only be run on activation. It creates database tables to hold
     * all tweets downloaded from Twitter and the Twitter user details.
     *
     * @return void
     */
    private function createDatabaseTables()
    {
        global $wpdb;

        // Create feed table
        $wpdb->query('CREATE TABLE IF NOT EXISTS ' . $wpdb->base_prefix
            . 'cgit_twitter (
            id BIGINT PRIMARY KEY,
            date DATETIME,
            user_id BIGINT,
            url VARCHAR(2048),
            retweet TINYINT,
            content TEXT,
            raw TEXT
        )');

        // Create user table
        $wpdb->query('CREATE TABLE IF NOT EXISTS ' . $wpdb->base_prefix
            . 'cgit_twitter_users (
            id BIGINT PRIMARY KEY,
            name VARCHAR(128),
            screen_name VARCHAR(128),
            url VARCHAR(2048),
            image VARCHAR(2048)
        )');
    }

    /**
     * Schedule feed update
     *
     * @return void
     */
    private function scheduleFeedUpdate()
    {
        wp_schedule_event(
            time(),
            'hourly',
            'cgit_twitter_update'
        );
    }

    /**
     * Add widget
     *
     * @return void
     */
    public function widget()
    {
        register_widget('Cgit\Twitter\Widgets\TwitterFeed');
    }

    /**
     * Add feed shortcode
     *
     * @param array $atts
     * @return string
     */
    public function shortcode($atts)
    {
        $atts = shortcode_atts([
            'user' => null,
            'count' => 10,
        ], $atts);

        if (is_null($atts['user'])) {
            return trigger_error('Please enter a Twitter screen name');
        }

        $count = intval($atts['count']);
        $feed = new Feed($atts['user']);

        return $feed->render($count);
    }

    /**
     * Clean database cache
     *
     * Restrict the number of tweets stored for each user to prevent the table
     * from becoming too large.
     *
     * @return void
     */
    private function cleanDatabaseCache()
    {
        global $wpdb;

        $table = $wpdb->base_prefix . 'cgit_twitter';
        $user_ids = $wpdb->get_col("SELECT DISTINCT user_id FROM $table");
        $limit = apply_filters('cgit_twitter_cache_limit', 100);

        if (!$user_ids) {
            return;
        }

        // Remove all except the most recent n rows for each user that appears
        // in the table.
        foreach ($user_ids as $user_id) {
            if (!$user_id) {
                continue;
            }

            $wpdb->query("DELETE FROM $table
                WHERE user_id = $user_id
                AND id NOT IN
                    (SELECT id FROM
                        (SELECT id FROM $table
                            WHERE user_id = $user_id
                            ORDER BY date DESC
                            LIMIT $limit) x)");
        }
    }
}
