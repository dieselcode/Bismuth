<?php

namespace Bismuth\Core\Auth;

abstract class Auth
{

    const AUTH_BASIC = 'Basic';
    const AUTH_OAUTH = 'Bearer';
    const AUTH_NONE  = 'None';

    protected $authType = '';


    public function setAuthType($authType)
    {
        $this->authType = $authType;
    }

    public function getAuthString()
    {
        switch ($this->authType) {
            case self::AUTH_BASIC:
                return sprintf('%s %s', self::AUTH_BASIC, base64_encode($this->user . ':' . $this->pass));
                break;
            case self::AUTH_OAUTH:
                return sprintf('%s %s', self::AUTH_OAUTH, $this->accessToken);
                break;
            case self::AUTH_NONE:
                return null;
                break;
        }

        return false;
    }

    abstract public function setCredentials($creds);

}

?>