<?php

namespace Cgit\Twitter;

use Abraham\TwitterOAuth\TwitterOAuth;
use stdClass;

class Connection
{
    /**
     * Establish Twitter API connection with default settings
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct(
            CGIT_TWITTER_KEY,
            CGIT_TWITTER_SECRET,
            CGIT_TWITTER_TOKEN,
            CGIT_TWITTER_TOKEN_SECRET
        );
    }
}
