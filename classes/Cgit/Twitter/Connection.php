<?php

namespace Cgit\Twitter;

use Abraham\TwitterOAuth\TwitterOAuth;
use stdClass;

class Connection extends TwitterOAuth
{
    /**
     * Connection established?
     *
     * @var bool
     */
    private $established = false;

    /**
     * Establish Twitter API connection with default settings
     *
     * @return void
     */
    public function __construct()
    {
        if (!$this->constantsDefined()) {
            trigger_error('Twitter Redux constants not defined.');

            return;
        }

        parent::__construct(
            CGIT_TWITTER_KEY,
            CGIT_TWITTER_SECRET,
            CGIT_TWITTER_TOKEN,
            CGIT_TWITTER_TOKEN_SECRET
        );

        $this->established = true;
    }

    /**
     * Required constants defined?
     *
     * @return bool
     */
    private function constantsDefined()
    {
        $constants = [
            'CGIT_TWITTER_KEY',
            'CGIT_TWITTER_SECRET',
            'CGIT_TWITTER_TOKEN',
            'CGIT_TWITTER_TOKEN_SECRET',
        ];

        foreach ($constants as $constant) {
            if (!defined($constant)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Connection established?
     *
     * @return bool
     */
    public function established()
    {
        return $this->established;
    }
}
