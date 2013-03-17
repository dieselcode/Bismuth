<?php

namespace Bismuth\Core\Auth;

use Bismuth\Core\Auth\Auth;

class None extends Auth
{

    public function __construct()
    {
        $this->setAuthType(Auth::AUTH_NONE);
    }

    public function setCredentials($creds)
    {
    }

}

?>