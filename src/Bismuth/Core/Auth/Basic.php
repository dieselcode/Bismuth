<?php

namespace Bismuth\Core\Auth;

use \Bismuth\Core\Auth\Auth;

class Basic extends Auth
{

    protected $user = '';
    protected $pass = '';

    public function __construct($user, $pass)
    {
        $this->setAuthType(Auth::AUTH_BASIC);
        $this->setCredentials(array('user' => $user, 'pass' => $pass));
    }

    public function setCredentials($creds)
    {
        $this->user = $creds['user'];
        $this->pass = $creds['pass'];
    }

}

?>