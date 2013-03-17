<?php

namespace Bismuth\Core\Auth;

use Bismuth\Core\Auth\Auth;

class Oauth extends Auth
{

    protected $accessToken = '';

    public function __construct($accessToken)
    {
        $this->setAuthType(Auth::AUTH_OAUTH);
        $this->setCredentials($accessToken);
    }

    public function setCredentials($creds)
    {
        $this->accessToken = $creds;
    }

}

?>

?>