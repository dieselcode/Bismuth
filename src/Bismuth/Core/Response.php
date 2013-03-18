<?php

namespace Bismuth\Core;

class Response
{
    public $data    = '';
    public $headers = array();
    public $ok      = false;
    public $cached  = false;

    public function __construct($headers, $data)
    {
        $this->headers  = $headers;
        $this->data     = $data;

        // set the 'ok' variable dynamically
        if ($this->getHeaders('HTTP_CODE') == 200) {
            $this->ok = true;
        }
    }

    public function setCached($isCached)
    {
        $this->cached = $isCached;
    }

    public function isCached()
    {
        return $this->cached;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getHeaders($header = null)
    {
        if (!empty($header)) {
            if (array_key_exists($header, $this->headers)) {
                return $this->headers[$header];
            }
        }

        return $this->headers;
    }

}

?>