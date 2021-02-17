<?php

namespace Klakier\AllegroAPI;

class Credentials
{
    public $client_id;
    public $client_secret;

    function __construct(bool $useSandBox)
    {
        //TODO przeniesc do env
        if ($useSandBox) {
            $this->client_id = getenv("CLIENT_ID_SANDBOX");
            $this->client_secret = getenv("CLIENT_SECRET_SANDBOX");
        } else {
            $this->client_id = getenv("CLIENT_ID");
            $this->client_secret = getenv("CLIENT_SECRET");
        }
    }
}
