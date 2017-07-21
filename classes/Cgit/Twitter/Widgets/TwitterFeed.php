<?php

namespace Cgit\Twitter\Widgets;

use Cgit\Twitter\Feed;
use WP_Widget;

class TwitterFeed extends WP_Widget
{
    /**
     * Register widget
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct('cgit_twitter_feed', 'Twitter Feed');
    }

    /**
     * Display widget content
     *
     * @param array $args Widget parameters
     * @param array $instance Widget instance parameters
     * @return void
     */
    public function widget($args, $instance)
    {
        $feed = new Feed($instance['name']);

        echo $args['before_widget'];

        if (!empty($instance['title'])) {
            echo $args['before_title']
                . apply_filters('widget_title', $instance['title'])
                . $args['after_title'];
        }

        echo $feed->render($instance['count']);
        echo $args['after_widget'];
    }

    /**
     * Display widget settings
     *
     * @param array $instance Widget instance parameters
     * @return void
     */
    public function form($instance)
    {
        $defaults = [
            'title' => 'Twitter Feed',
            'name' => 'twitter',
            'count' => 10,
        ];

        $instance = wp_parse_args($instance, $defaults);
        $title = sanitize_text_field($instance['title']);
        $name = sanitize_text_field($instance['name']);
        $count = sanitize_text_field($instance['count']);

        ?>
        <p>
            <label for="<?= $this->get_field_id('title') ?>">
                <?= __('Title:') ?>
            </label>
            <input
                type="text"
                name="<?= $this->get_field_name('title') ?>"
                id="<?= $this->get_field_id('title') ?>"
                value="<?= esc_attr($title) ?>"
                class="widefat" />
        </p>
        <p>
            <label for="<?= $this->get_field_id('name') ?>">
                <?= __('Screen name:') ?>
            </label>
            <input
                type="text"
                name="<?= $this->get_field_name('name') ?>"
                id="<?= $this->get_field_id('name') ?>"
                value="<?= esc_attr($name) ?>"
                class="widefat" />
        </p>
        <p>
            <label for="<?= $this->get_field_id('count') ?>">
                <?= __('Feed length:') ?>
            </label>
            <input
                type="text"
                name="<?= $this->get_field_name('count') ?>"
                id="<?= $this->get_field_id('count') ?>"
                value="<?= esc_attr($count) ?>"
                class="widefat" />
        </p>
        <?php
    }
}
