<?php

namespace Cgit\Twitter;

use Abraham\TwitterOAuth\TwitterOAuth;
use stdClass;

class Feed
{
    /**
     * WordPress database object
     *
     * @var wpdb
     */
    private $database;

    /**
     * Screen name
     *
     * This is the Twitter account user name, referred to as the "screen_name"
     * in the Twitter API v1.1 parameters.
     *
     * @var string
     */
    private $name;

    /**
     * Table name
     *
     * @var string
     */
    private $tableName;

    /**
     * User table name
     *
     * @var string
     */
    private $userTableName;

    /**
     * Constructor
     *
     * Sets the screen name and checks for the required constants. The screen
     * name is required in the constructor so the feed is restricted to a single
     * user account.
     *
     * @param string $name
     * @return void
     */
    public function __construct($name)
    {
        global $wpdb;

        $this->database = $wpdb;
        $this->name = $name;
        $this->tableName = $wpdb->prefix . 'cgit_twitter';
        $this->userTableName = $wpdb->prefix . 'cgit_twitter_users';

        $this->checkConstants();
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
     * Update feed
     *
     * Retrieve tweets via the Twitter API, extract the crucial information, and
     * save them in the database. If the user already has tweets in the
     * database, it only loads the tweets published since the most recent tweet
     * in the database. If they do not, it loads the 100 most recent tweets for
     * that user.
     *
     * @return void
     */
    public function update()
    {
        // Twitter API connection
        $connection = new TwitterOAuth(
            CGIT_TWITTER_KEY,
            CGIT_TWITTER_SECRET,
            CGIT_TWITTER_TOKEN,
            CGIT_TWITTER_TOKEN_SECRET
        );

        // Load tweets
        $items = $connection->get(
            'statuses/user_timeline',
            $this->connectionOptions()
        );

        if (count($items) == 0) {
            return;
        }

        // Update database
        foreach ($items as $item) {
            $this->updateItem($item);
        }

        // Update user in database
        $this->updateUser($item->user);
    }

    /**
     * Return connection options
     *
     * @return array
     */
    private function connectionOptions()
    {
        $options = [
            'screen_name' => $this->name,
            'exclude_replies' => true,
        ];

        $table = $this->tableName;
        $users = $this->userTableName;

        // Get the ID of the most recent tweet for the specified user
        $item_id = $this->database->get_var("
            SELECT $table.id
            FROM $table
            JOIN $users ON $table.user_id = $users.id
            WHERE $users.screen_name = '{$this->name}'
            ORDER BY id DESC
            LIMIT 1
        ");

        // If the database contains tweets by this user, load tweets with an ID
        // greater than that of the most recent tweet. Otherwise, limit the
        // number of tweets to 100.
        if ($item_id) {
            $options['since_id'] = $item_id;
        } else {
            $options['count'] = 100;
        }

        return $options;
    }

    /**
     * Insert or update tweet in database
     *
     * @param stdClass $item
     * @return void
     */
    private function updateItem($item)
    {
        $this->database->replace($this->tableName, [
            'id' => intval($item->id),
            'date' => date('Y-m-d H:i:s', strtotime($item->created_at)),
            'user_id' => $item->user->id,
            'url' => 'https://twitter.com/' . $this->name . '/status/'
                . $item->id,
            'retweet' => $item->retweeted ? 1 : 0,
            'content' => self::extractContent($item),
            'raw' => json_encode($item),
        ]);
    }

    /**
     * Insert or update user in database
     *
     * @param stdClass $item
     * @return void
     */
    private function updateUser($user)
    {
        $this->database->replace($this->userTableName, [
            'id' => $user->id,
            'name' => $user->name,
            'screen_name' => $user->screen_name,
            'url' => 'https://twitter.com/' . $user->screen_name,
            'image' => $user->profile_image_url,
        ]);
    }

    /**
     * Create user
     *
     * If the screen name does not exist in the database, update the user data
     * via the Twitter API.
     *
     * @return bool
     */
    private function createUser()
    {
        $result = $this->database->get_var("
            SELECT screen_name
            FROM {$this->userTableName}
            WHERE screen_name = '{$this->name}'
        ");

        // If the user exists in the database, do nothing
        if ($result) {
            return false;
        }

        // Connect to Twitter to check for user
        $connection = new TwitterOAuth(
            CGIT_TWITTER_KEY,
            CGIT_TWITTER_SECRET,
            CGIT_TWITTER_TOKEN,
            CGIT_TWITTER_TOKEN_SECRET
        );

        $user = $connection->get('users/show', [
            'screen_name' => $this->name,
        ]);

        // If the user does not exist on Twitter, give up
        if (isset($user->errors)) {
            return false;
        }

        // Add user to database and perform first download
        $this->updateUser($user);
        $this->update();

        return true;
    }

    /**
     * Get HTML content
     *
     * Create HTML content for each Tweet with hashtags, URLs, user mentions,
     * and embedded media converted to HTML links.
     *
     * @param stdClass $item
     * @return stdClass
     */
    private static function extractContent($item)
    {
        $content = $item->text;
        $entities = [];

        foreach ($item->entities->hashtags as $obj) {
            $entity = self::createEntity($obj);
            $entity->replace = '<a href="https://twitter.com/search?q=%23'
                . strtolower($obj->text) . '">#' . $obj->text . '</a>';
            $entities[$entity->start] = $entity;
        }

        foreach ($item->entities->urls as $obj) {
            $entity = self::createEntity($obj);
            $entity->replace = '<a href="' . $obj->expanded_url . '">'
                . $obj->display_url . '</a>';
            $entities[$entity->start] = $entity;
        }

        foreach ($item->entities->user_mentions as $obj) {
            $entity = self::createEntity($obj);
            $entity->replace = '<a href="https:/twitter.com/'
                . $obj->screen_name . '">@' . $obj->screen_name . '</a>';
            $entities[$entity->start] = $entity;
        }

        if (property_exists($item->entities, 'media')) {
            foreach ($item->entities->media as $obj) {
                $entity = self::createEntity($obj);
                $entity->replace = '<a href="' . $obj->expanded_url . '">'
                    . $obj->display_url . '</a>';
                $entities[$entity->start] = $entity;
            }
        }

        krsort($entities);

        foreach ($entities as $entity) {
            $content = substr_replace(
                $content,
                $entity->replace,
                $entity->start,
                $entity->length
            );
        }

        return $content;
    }

    /**
     * Create entity
     *
     * Creates an object representing the position of the entity (hashtag, URL,
     * user mention, or media) within the tweet text.
     *
     * @param stdClass $obj
     * @return stdClass
     */
    private static function createEntity($obj)
    {
        $entity = new stdClass();

        $entity->start = $obj->indices[0];
        $entity->end = $obj->indices[1];
        $entity->length = $obj->indices[1] - $obj->indices[0];

        return $entity;
    }

    /**
     * Get feed
     *
     * Return an array of tweets from the database. If no count is specified, it
     * returns the most recent 10 tweets.
     *
     * @param int $count
     * @return array
     */
    public function get($count = 10)
    {
        $table = $this->tableName;
        $users = $this->userTableName;
        $prefix = '_user_';

        // Get tweet data from database
        $items = $this->database->get_results("
            SELECT
                $table.id,
                $table.date,
                $table.id AS {$prefix}id,
                $users.screen_name AS {$prefix}screen_name,
                $users.name AS {$prefix}name,
                $users.url AS {$prefix}url,
                $users.image AS {$prefix}image,
                $table.url,
                $table.retweet,
                $table.content
            FROM $table
            JOIN $users ON $table.user_id = $users.id
            WHERE $users.screen_name = '{$this->name}'
            ORDER BY id DESC
            LIMIT $count
        ");

        // Convert user data to single object
        foreach ($items as $item) {
            $item->user = new stdClass();

            foreach ($item as $property => $value) {
                if (strpos($property, $prefix) !== false) {
                    $name = str_replace($prefix, '', $property);
                    $item->user->$name = $value;

                    unset($item->$property);
                }
            }
        }

        // If there are no results, check the user exists in the database. If it
        // doesn't and it can be accessed via the Twitter API, create an entry
        // in the database, update the feed, and return the array of tweets
        // requested.
        if (!$items) {
            if ($this->createUser()) {
                return $this->get($count);
            }
        }

        return $items;
    }

    /**
     * Return feed as HTML
     *
     * @param int $count
     * @return string
     */
    public function render($count = 10)
    {
        $items = $this->get($count);
        $output = '<ol>';

        foreach ($items as $item) {
            $date = date('G:i \o\n j F Y', strtotime($item->date));

            ob_start();

            ?>
            <li>
                <p class="text"><?= $item->content ?></p>
                <p class="meta">
                    <a href="<?= $item->user->url ?>" class="name"><?=
                        $item->user->name
                    ?></a>
                    <a href="<?= $item->url ?>" class="date"><?=
                        $date
                    ?></a>
                </p>
            </li>
            <?php

            $output .= ob_get_clean();
        }

        $output .= '</ol>';

        return $output;
    }
}