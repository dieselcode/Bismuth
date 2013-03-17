<?php

namespace Bismuth\Core;

class Response
{
    private $data = '';
    private $headers = array();

    public  $ok = false;

    public function __construct($headers, $data)
    {
        $this->headers  = $headers;
        $this->data     = $data;

        // set the 'ok' variable dynamically
        if ($this->getHeaders('HTTP_CODE') == 200) {
            $this->ok = true;
        }
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