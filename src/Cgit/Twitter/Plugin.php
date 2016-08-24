<?php

namespace Cgit\Twitter;

class Plugin
{
    /**
     * Singleton class instance
     *
     * @var Plugin
     */
    private static $instance;

    /**
     * Private constructor
     *
     * Register functions to run on plugin activation and create the feed update
     * action. Register shortcodes and widgets.
     *
     * @return void
     */
    private function __construct()
    {
        // Check required constants are defined
        $this->checkConstants();

        // Make everything work
        register_activation_hook(CGIT_TWITTER_PLUGIN_FILE, [$this, 'activate']);
        add_action('cgit_twitter_update', [$this, 'update']);
        add_action('widgets_init', [$this, 'widget']);
        add_shortcode('twitter_feed', [$this, 'shortcode']);
    }

    /**
     * Return the singleton class instance
     *
     * @return Plugin
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Check for required constants
     *
     * The Twitter OAuth credentials must be set in wp-config.php. If they are
     * missing, we cannot update the feed.
     *
     * @return void
     */
    private function checkConstants()
    {
        $constants = [
            'CGIT_TWITTER_KEY',
            'CGIT_TWITTER_SECRET',
            'CGIT_TWITTER_TOKEN',
            'CGIT_TWITTER_TOKEN_SECRET',
        ];

        $missing = [];

        foreach ($constants as $constant) {
            if (!defined($constant)) {
                $missing[] = $constant;
            }
        }

        if ($missing) {
            trigger_error(
                'Missing constant(s): ' . implode(', ', $missing),
                E_USER_ERROR
            );
        }
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

        $users = $wpdb->get_col('SELECT screen_name FROM ' . $wpdb->prefix
            . 'cgit_twitter_users');

        foreach ($users as $user) {
            $feed = new Feed($user);
            $feed->update();
        }
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
        $wpdb->query('CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix
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
        $wpdb->query('CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix
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
}
